import { PrismaClient } from "@prisma/client";
import bcrypt from "bcryptjs";
import jwt from "jsonwebtoken";
import { AuthRequest, AuthResponse, UserResponse } from "../types";

const prisma = new PrismaClient();

export class AuthService {
  /**
   * Вход пользователя
   */
  static async login(req: AuthRequest): Promise<AuthResponse> {
    const user = await prisma.user.findUnique({
      where: { username: req.username },
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

    if (!user) {
      throw new Error("Invalid credentials");
    }

    if (user.status !== "ACTIVE") {
      throw new Error("User account is blocked or deleted");
    }

    const passwordValid = await bcrypt.compare(req.password, user.passwordHash);
    if (!passwordValid) {
      throw new Error("Invalid credentials");
    }

    const accessToken = jwt.sign(
      {
        userId: user.id,
        username: user.username,
        roleId: user.roleId,
      },
      process.env.JWT_SECRET!,
      { expiresIn: process.env.JWT_EXPIRES_IN || "15m" }
    );

    const refreshToken = jwt.sign(
      {
        userId: user.id,
      },
      process.env.JWT_REFRESH_SECRET!,
      { expiresIn: process.env.JWT_REFRESH_EXPIRES_IN || "7d" }
    );

    // Обновить время последнего входа
    await prisma.user.update({
      where: { id: user.id },
      data: {
        lastLoginAt: new Date(),
      },
    });

    return {
      accessToken,
      refreshToken,
      user: AuthService.formatUser(user),
    };
  }

  /**
   * Обновление access токена через refresh токен
   */
  static async refreshToken(refreshToken: string): Promise<AuthResponse> {
    try {
      const decoded = jwt.verify(
        refreshToken,
        process.env.JWT_REFRESH_SECRET!
      ) as any;

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
        throw new Error("User not found or inactive");
      }

      const accessToken = jwt.sign(
        {
          userId: user.id,
          username: user.username,
          roleId: user.roleId,
        },
        process.env.JWT_SECRET!,
        { expiresIn: process.env.JWT_EXPIRES_IN || "15m" }
      );

      return {
        accessToken,
        user: AuthService.formatUser(user),
      };
    } catch (error) {
      throw new Error("Invalid refresh token");
    }
  }

  /**
   * Получение информации о текущем пользователе
   */
  static async getCurrentUser(userId: string): Promise<UserResponse> {
    const user = await prisma.user.findUnique({
      where: { id: userId },
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

    if (!user) {
      throw new Error("User not found");
    }

    return AuthService.formatUser(user);
  }

  /**
   * Форматирование пользователя для ответа
   */
  private static formatUser(user: any): UserResponse {
    return {
      id: user.id,
      username: user.username,
      email: user.email,
      fullName: user.fullName,
      role: {
        id: user.role.id,
        name: user.role.name,
        hierarchy: user.role.hierarchy,
        permissions: user.role.permissions.map((rp: any) => ({
          id: rp.permission.id,
          name: rp.permission.name,
          category: rp.permission.category,
        })),
      },
      status: user.status,
      themePreference: user.themePreference,
      createdAt: user.createdAt.toISOString(),
      lastLoginAt: user.lastLoginAt?.toISOString() || null,
    };
  }
}
