import { Router, Request, Response } from "express";
import { ApplicationService } from "../services/application.service";
import { authMiddleware, requirePermission } from "../middleware/auth";
import { ApplicationStatus } from "@prisma/client";

const router = Router();

/**
 * GET /api/applications
 * Получить список заявок (с фильтрацией)
 * Query: status, assignedToId, category, limit, offset, sortBy
 */
router.get(
  "/",
  authMiddleware,
  requirePermission("can_view_applications"),
  async (req: Request, res: Response) => {
    try {
      const { status, assignedToId, category, limit, offset, sortBy } =
        req.query;

      const result = await ApplicationService.getApplications({
        status: status as ApplicationStatus,
        assignedToId: assignedToId === "null" ? null : (assignedToId as string),
        categoryId: category as string,
        limit: limit ? parseInt(limit as string) : 50,
        offset: offset ? parseInt(offset as string) : 0,
        sortBy: sortBy as string,
      });

      res.json(result);
    } catch (error: any) {
      res.status(500).json({ error: error.message });
    }
  }
);

/**
 * GET /api/applications/:id
 * Получить заявку по ID
 */
router.get(
  "/:id",
  authMiddleware,
  requirePermission("can_view_applications"),
  async (req: Request, res: Response) => {
    try {
      const app = await ApplicationService.getApplicationById(req.params.id);
      res.json(app);
    } catch (error: any) {
      res.status(404).json({ error: error.message });
    }
  }
);

/**
 * POST /api/applications
 * Создать новую заявку (публичный API, без авторизации)
 */
router.post("/", async (req: Request, res: Response) => {
  try {
    const {
      clientName,
      clientPhone,
      clientEmail,
      category,
      description,
      attachmentUrl,
      priority,
    } = req.body;

    if (!clientName || !clientPhone || !clientEmail || !category || !description) {
      return res.status(400).json({ error: "Missing required fields" });
    }

    const app = await ApplicationService.createApplication({
      clientName,
      clientPhone,
      clientEmail,
      category,
      description,
      attachmentUrl,
      priority,
    });

    res.status(201).json(app);
  } catch (error: any) {
    res.status(500).json({ error: error.message });
  }
});

/**
 * POST /api/applications/:id/take
 * Взять заявку в работу
 */
router.post(
  "/:id/take",
  authMiddleware,
  requirePermission("can_take_own_applications"),
  async (req: Request, res: Response) => {
    try {
      if (!req.user) {
        return res.status(401).json({ error: "Unauthorized" });
      }

      const { version } = req.body;

      if (version === undefined) {
        return res.status(400).json({ error: "Version required" });
      }

      const app = await ApplicationService.takeApplication(
        req.params.id,
        req.user.id,
        version
      );

      res.json(app);
    } catch (error: any) {
      res.status(400).json({ error: error.message });
    }
  }
);

/**
 * POST /api/applications/:id/release
 * Освободить заявку
 */
router.post(
  "/:id/release",
  authMiddleware,
  requirePermission("can_release_applications"),
  async (req: Request, res: Response) => {
    try {
      if (!req.user) {
        return res.status(401).json({ error: "Unauthorized" });
      }

      const app = await ApplicationService.releaseApplication(
        req.params.id,
        req.user.id
      );

      res.json(app);
    } catch (error: any) {
      res.status(400).json({ error: error.message });
    }
  }
);

/**
 * PATCH /api/applications/:id/status
 * Изменить статус заявки
 */
router.patch(
  "/:id/status",
  authMiddleware,
  requirePermission("can_change_status"),
  async (req: Request, res: Response) => {
    try {
      if (!req.user) {
        return res.status(401).json({ error: "Unauthorized" });
      }

      const { newStatus, reason } = req.body;

      if (!newStatus) {
        return res.status(400).json({ error: "Status required" });
      }

      const app = await ApplicationService.changeStatus(
        req.params.id,
        newStatus as ApplicationStatus,
        req.user.id,
        reason
      );

      res.json(app);
    } catch (error: any) {
      res.status(400).json({ error: error.message });
    }
  }
);

/**
 * GET /api/applications/changes?since=timestamp
 * Получить изменения заявок с определенного времени (для синхронизации)
 */
router.get(
  "/changes",
  authMiddleware,
  async (req: Request, res: Response) => {
    try {
      const { since, userId } = req.query;

      if (!since) {
        return res.status(400).json({ error: "Since parameter required" });
      }

      const timestamp = new Date(since as string);
      const changes = await ApplicationService.getChangesSince(
        timestamp,
        userId as string
      );

      res.json({ changes });
    } catch (error: any) {
      res.status(500).json({ error: error.message });
    }
  }
);

export default router;
