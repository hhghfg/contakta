import { Request, Response, NextFunction } from "express";
import jwt from "jsonwebtoken";
import { PrismaClient } from "@prisma/client";
import { JwtPayload } from "../types";

declare global {
  namespace Express {
    interface Request {
      user?: {
        id: string;
        username: string;
        roleId: string;
        role: any;
      };
    }
  }
}

const prisma = new PrismaClient();

/**
 * Проверка JWT токена
 */
export const authMiddleware = async (
  req: Request,
  res: Response,
  next: NextFunction
) => {
  try {
    const authHeader = req.headers.authorization;
    if (!authHeader?.startsWith("Bearer ")) {
      return res.status(401).json({ error: "Unauthorized" });
    }

    const token = authHeader.substring(7);
    const decoded = jwt.verify(token, process.env.JWT_SECRET!) as JwtPayload;

    const user = await prisma.user.findUnique({
      where: { id: decoded.userId },
      include: {
        role: {
          include: {
            permissions: {
              include: { permission: true },
            },
          },
        },
      },
    });

    if (!user || user.status !== "ACTIVE") {
      return res.status(401).json({ error: "User not found or inactive" });
    }

    req.user = {
      id: user.id,
      username: user.username,
      roleId: user.roleId || "",
      role: user.role,
    };

    next();
  } catch (error) {
    return res.status(401).json({ error: "Invalid token" });
  }
};

/**
 * Проверка прав доступа на основе пермиссий
 */
export const requirePermission = (permission: string) => {
  return async (req: Request, res: Response, next: NextFunction) => {
    if (!req.user) {
      return res.status(401).json({ error: "Unauthorized" });
    }

    const userRole = await prisma.role.findUnique({
      where: { id: req.user.roleId },
      include: {
        permissions: {
          include: { permission: true },
        },
      },
    });

    if (!userRole) {
      return res.status(403).json({ error: "Role not found" });
    }

    const hasPermission = userRole.permissions.some(
      (rp) => rp.permission.name === permission
    );

    if (!hasPermission) {
      return res.status(403).json({ error: "Insufficient permissions" });
    }

    next();
  };
};

/**
 * Проверка роли по иерархии
 * maxHierarchy = 0 (только супер-админ), 1 (админ+), 2 (модератор+), 3 (оператор)
 */
export const requireRoleLevel = (maxHierarchy: number) => {
  return async (req: Request, res: Response, next: NextFunction) => {
    if (!req.user) {
      return res.status(401).json({ error: "Unauthorized" });
    }

    const role = await prisma.role.findUnique({
      where: { id: req.user.roleId },
    });

    if (!role || role.hierarchy > maxHierarchy) {
      return res
        .status(403)
        .json({ error: "Insufficient role level required" });
    }

    next();
  };
};

/**
 * Логирование действий (audit log)
 */
export const auditLog = async (
  userId: string,
  roleId: string | null,
  action: string,
  resource: string,
  resourceId: string | null,
  changes: any,
  ipAddress: string,
  userAgent: string | undefined
) => {
  try {
    await prisma.auditLog.create({
      data: {
        userId,
        roleId,
        action,
        resource,
        resourceId,
        changes,
        ipAddress,
        userAgent,
      },
    });
  } catch (error) {
    console.error("Audit log error:", error);
  }
};

/**
 * Middleware для автоматического логирования
 */
export const auditLogMiddleware = (req: Request, res: Response, next: NextFunction) => {
  const originalSend = res.send;

  res.send = function (data: any) {
    if (req.user && req.method !== "GET") {
      const statusCode = res.statusCode;
      if (statusCode >= 200 && statusCode < 400) {
        auditLog(
          req.user.id,
          req.user.roleId,
          `${req.method}_${req.path}`,
          req.path.split("/")[2] || "unknown",
          null,
          { path: req.path, method: req.method },
          req.ip || "",
          req.get("user-agent")
        );
      }
    }
    return originalSend.call(this, data);
  };

  next();
};
