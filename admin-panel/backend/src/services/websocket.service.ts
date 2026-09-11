import { Server as SocketServer, Socket } from "socket.io";
import { PrismaClient } from "@prisma/client";
import jwt from "jsonwebtoken";
import { JwtPayload, WsEvent } from "../types";

const prisma = new PrismaClient();

// Хранилище активных соединений по пользователям
const userSockets = new Map<string, Set<Socket>>();
const socketUsers = new Map<string, string>();

/**
 * Инициализация WebSocket сервера
 */
export function initializeWebSocket(io: SocketServer) {
  io.use(async (socket, next) => {
    try {
      // Авторизация через токен
      const token =
        socket.handshake.auth.token ||
        socket.handshake.headers.authorization?.split(" ")[1];

      if (!token) {
        return next(new Error("Authentication required"));
      }

      const decoded = jwt.verify(token, process.env.JWT_SECRET!) as JwtPayload;
      socket.data.userId = decoded.userId;
      socket.data.username = decoded.username;
      socket.data.roleId = decoded.roleId;

      next();
    } catch (error) {
      next(new Error("Invalid token"));
    }
  });

  io.on("connection", (socket: Socket) => {
    const userId = socket.data.userId;
    console.log(`✓ User ${userId} connected via WebSocket`);

    // Добавить сокет пользователя
    if (!userSockets.has(userId)) {
      userSockets.set(userId, new Set());
    }
    userSockets.get(userId)!.add(socket);
    socketUsers.set(socket.id, userId);

    // Присоединиться к комнате пользователя
    socket.join(`user:${userId}`);

    // Если супер-админ, присоединиться к админской комнате
    (async () => {
      const user = await prisma.user.findUnique({
        where: { id: userId },
        include: { role: true },
      });
      if (user?.role?.hierarchy === 0) {
        socket.join("admins");
      }
    })();

    // ============ EVENT HANDLERS ============

    /**
     * Новая заявка (emit от сервера)
     * Слушают: все операторы
     */
    socket.on("application:new", (data) => {
      io.emit("application:new", data);
    });

    /**
     * Заявка захвачена
     * Слушают: все кроме того, кто захватил
     */
    socket.on("application:taken", (data) => {
      socket.broadcast.emit("application:taken", data);
    });

    /**
     * Заявка освобождена
     * Слушают: все
     */
    socket.on("application:released", (data) => {
      io.emit("application:released", data);
    });

    /**
     * Статус заявки изменился
     * Слушают: все
     */
    socket.on("application:status_changed", (data) => {
      io.emit("application:status_changed", data);
    });

    /**
     * Новый комментарий
     * Слушают: все
     */
    socket.on("application:comment_added", (data) => {
      io.emit("application:comment_added", data);
    });

    /**
     * Запрос уведомлений (подписка)
     * Слушают: этот пользователь
     */
    socket.on("notifications:subscribe", async () => {
      try {
        const unread = await prisma.notification.findMany({
          where: {
            userId,
            readAt: null,
          },
          orderBy: { createdAt: "desc" },
          take: 50,
        });

        socket.emit("notifications:unread", { notifications: unread });
      } catch (error) {
        socket.emit("error", { message: "Failed to load notifications" });
      }
    });

    /**
     * Отметить уведомление как прочитанное
     */
    socket.on("notifications:read", async (data: { notificationId: string }) => {
      try {
        await prisma.notification.update({
          where: { id: data.notificationId },
          data: { readAt: new Date() },
        });

        io.to(`user:${userId}`).emit("notifications:marked_read", {
          notificationId: data.notificationId,
        });
      } catch (error) {
        socket.emit("error", { message: "Failed to mark notification" });
      }
    });

    /**
     * Отключение сокета
     */
    socket.on("disconnect", () => {
      console.log(`✗ User ${userId} disconnected`);

      // Удалить сокет из хранилища
      userSockets.get(userId)?.delete(socket);
      if (userSockets.get(userId)?.size === 0) {
        userSockets.delete(userId);
      }
      socketUsers.delete(socket.id);
    });

    socket.on("error", (error) => {
      console.error(`WebSocket error for user ${userId}:`, error);
    });
  });
}

/**
 * Отправить событие конкретному пользователю
 */
export function emitToUser(
  io: SocketServer,
  userId: string,
  event: string,
  data: any
) {
  io.to(`user:${userId}`).emit(event, data);
}

/**
 * Отправить событие всем в комнате
 */
export function emitToRoom(
  io: SocketServer,
  room: string,
  event: string,
  data: any
) {
  io.to(room).emit(event, data);
}

/**
 * Отправить событие всем (broadcast)
 */
export function broadcastEvent(
  io: SocketServer,
  event: string,
  data: any
) {
  io.emit(event, data);
}

/**
 * Получить количество активных пользователей
 */
export function getActiveUsersCount(): number {
  return userSockets.size;
}

/**
 * Получить активных пользователей в комнате админов
 */
export async function getActiveAdmins(io: SocketServer): Promise<number> {
  const room = io.sockets.adapter.rooms.get("admins");
  return room?.size || 0;
}
