import { PrismaClient, ApplicationStatus } from "@prisma/client";
import { ApplicationResponse, TakeApplicationPayload } from "../types";

const prisma = new PrismaClient();

export class ApplicationService {
  /**
   * Получить все заявки с фильтрацией
   */
  static async getApplications(filters: {
    status?: ApplicationStatus;
    assignedToId?: string | null;
    categoryId?: string;
    limit?: number;
    offset?: number;
    sortBy?: string;
  }): Promise<{ applications: ApplicationResponse[]; total: number }> {
    const where: any = {};

    if (filters.status) where.status = filters.status;
    if (filters.assignedToId !== undefined) {
      where.assignedToId = filters.assignedToId;
    }
    if (filters.categoryId) where.category = filters.categoryId;

    const [applications, total] = await Promise.all([
      prisma.application.findMany({
        where,
        include: {
          assignedTo: true,
        },
        orderBy: filters.sortBy === "priority" ? { priority: "desc" } : { createdAt: "desc" },
        take: filters.limit || 50,
        skip: filters.offset || 0,
      }),
      prisma.application.count({ where }),
    ]);

    return {
      applications: applications.map((app) =>
        ApplicationService.formatApplication(app)
      ),
      total,
    };
  }

  /**
   * Получить заявку по ID
   */
  static async getApplicationById(id: string): Promise<ApplicationResponse> {
    const app = await prisma.application.findUnique({
      where: { id },
      include: {
        assignedTo: true,
        comments: {
          include: { author: true },
          orderBy: { createdAt: "desc" },
        },
        statusHistory: {
          include: { changedBy: true },
          orderBy: { createdAt: "desc" },
        },
      },
    });

    if (!app) {
      throw new Error("Application not found");
    }

    return ApplicationService.formatApplication(app);
  }

  /**
   * Создать новую заявку (публичный API)
   */
  static async createApplication(data: {
    clientName: string;
    clientPhone: string;
    clientEmail: string;
    category: string;
    description: string;
    attachmentUrl?: string;
    priority?: number;
  }): Promise<ApplicationResponse> {
    const app = await prisma.application.create({
      data: {
        clientName: data.clientName,
        clientPhone: data.clientPhone,
        clientEmail: data.clientEmail,
        category: data.category,
        description: data.description,
        attachmentUrl: data.attachmentUrl,
        priority: data.priority || 0,
        status: "NEW",
      },
      include: {
        assignedTo: true,
      },
    });

    return ApplicationService.formatApplication(app);
  }

  /**
   * Взять заявку в работу (пессимистичная блокировка)
   */
  static async takeApplication(
    applicationId: string,
    userId: string,
    version: number
  ): Promise<ApplicationResponse> {
    // Пессимистичная блокировка: SELECT FOR UPDATE
    const app = await prisma.$executeRaw`
      SELECT id FROM applications 
      WHERE id = ${applicationId} AND version = ${version} AND "assignedToId" IS NULL
      FOR UPDATE
    `;

    // Если заявка уже кем-то занята или версия не совпадает
    if (!app || (app as any).length === 0) {
      throw new Error(
        "Application is already assigned or version mismatch"
      );
    }

    // Обновить заявку
    const updated = await prisma.application.update({
      where: { id: applicationId },
      data: {
        assignedToId: userId,
        assignedAt: new Date(),
        status: "IN_PROGRESS",
        version: { increment: 1 },
      },
      include: {
        assignedTo: true,
      },
    });

    // Создать запись в истории
    await prisma.applicationStatusHistory.create({
      data: {
        applicationId,
        changedById: userId,
        oldStatus: "NEW",
        newStatus: "IN_PROGRESS",
      },
    });

    return ApplicationService.formatApplication(updated);
  }

  /**
   * Освободить заявку
   */
  static async releaseApplication(
    applicationId: string,
    userId: string
  ): Promise<ApplicationResponse> {
    const app = await prisma.application.findUnique({
      where: { id: applicationId },
    });

    if (!app) {
      throw new Error("Application not found");
    }

    // Только тот, кто занял, или админ может освободить
    if (app.assignedToId !== userId) {
      const user = await prisma.user.findUnique({
        where: { id: userId },
        include: { role: true },
      });
      if (user?.role?.hierarchy !== 0 && user?.role?.hierarchy !== 1) {
        throw new Error("Only assigned operator or admin can release");
      }
    }

    const updated = await prisma.application.update({
      where: { id: applicationId },
      data: {
        assignedToId: null,
        assignedAt: null,
        status: "NEW",
        version: { increment: 1 },
      },
      include: {
        assignedTo: true,
      },
    });

    // Создать запись в истории
    await prisma.applicationStatusHistory.create({
      data: {
        applicationId,
        changedById: userId,
        oldStatus: app.status as ApplicationStatus,
        newStatus: "NEW",
      },
    });

    return ApplicationService.formatApplication(updated);
  }

  /**
   * Изменить статус заявки
   */
  static async changeStatus(
    applicationId: string,
    newStatus: ApplicationStatus,
    userId: string,
    reason?: string
  ): Promise<ApplicationResponse> {
    const app = await prisma.application.findUnique({
      where: { id: applicationId },
    });

    if (!app) {
      throw new Error("Application not found");
    }

    const oldStatus = app.status;

    const updated = await prisma.application.update({
      where: { id: applicationId },
      data: {
        status: newStatus,
        resolvedAt:
          newStatus === "RESOLVED" || newStatus === "CLOSED"
            ? new Date()
            : null,
        version: { increment: 1 },
      },
      include: {
        assignedTo: true,
      },
    });

    // Создать запись в истории
    await prisma.applicationStatusHistory.create({
      data: {
        applicationId,
        changedById: userId,
        oldStatus: oldStatus as ApplicationStatus,
        newStatus,
        reason,
      },
    });

    return ApplicationService.formatApplication(updated);
  }

  /**
   * Получить изменения заявок с определенного времени (для синхронизации)
   */
  static async getChangesSince(timestamp: Date, userId?: string): Promise<any[]> {
    const changes = await prisma.application.findMany({
      where: {
        updatedAt: { gte: timestamp },
        ...(userId ? { assignedToId: userId } : {}),
      },
      include: {
        assignedTo: true,
        statusHistory: {
          where: { createdAt: { gte: timestamp } },
          include: { changedBy: true },
        },
      },
      orderBy: { updatedAt: "desc" },
    });

    return changes.map((app) => ({
      ...ApplicationService.formatApplication(app),
      recentChanges: app.statusHistory,
    }));
  }

  /**
   * Форматирование заявки для ответа
   */
  private static formatApplication(app: any): ApplicationResponse {
    return {
      id: app.id,
      clientName: app.clientName,
      clientPhone: app.clientPhone,
      clientEmail: app.clientEmail,
      category: app.category,
      description: app.description,
      status: app.status,
      priority: app.priority,
      assignedTo: app.assignedTo
        ? {
            id: app.assignedTo.id,
            username: app.assignedTo.username,
            email: app.assignedTo.email,
            fullName: app.assignedTo.fullName,
            role: null as any,
            status: app.assignedTo.status,
            themePreference: app.assignedTo.themePreference,
            createdAt: app.assignedTo.createdAt.toISOString(),
            lastLoginAt: app.assignedTo.lastLoginAt?.toISOString() || null,
          }
        : null,
      createdAt: app.createdAt.toISOString(),
      updatedAt: app.updatedAt.toISOString(),
      resolvedAt: app.resolvedAt?.toISOString() || null,
      version: app.version,
    };
  }
}
