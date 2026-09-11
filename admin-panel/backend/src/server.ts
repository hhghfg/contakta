import express, { Express } from "express";
import cors from "cors";
import { createServer } from "http";
import { Server as SocketServer } from "socket.io";
import dotenv from "dotenv";

import { initializeWebSocket } from "./services/websocket.service";
import authRoutes from "./api/auth.routes";
import applicationsRoutes from "./api/applications.routes";
import { authMiddleware, auditLogMiddleware } from "./middleware/auth";

dotenv.config();

const app: Express = express();
const server = createServer(app);
const io = new SocketServer(server, {
  cors: {
    origin: process.env.WS_CORS_ORIGIN || "http://localhost:5173",
    credentials: true,
  },
});

// ============ MIDDLEWARE ============

app.use(express.json({ limit: "10mb" }));
app.use(
  cors({
    origin: process.env.CORS_ORIGIN || "http://localhost:5173",
    credentials: true,
  })
);
app.use(auditLogMiddleware);

// ============ ROUTES ============

app.use("/api/auth", authRoutes);
app.use("/api/applications", applicationsRoutes);

/**
 * Health check
 */
app.get("/api/health", (req, res) => {
  res.json({
    status: "ok",
    timestamp: new Date().toISOString(),
    uptime: process.uptime(),
  });
});

// ============ WEBSOCKET ============

initializeWebSocket(io);

// Store io instance globally for use in services
(global as any).io = io;

// ============ ERROR HANDLING ============

app.use((err: any, req: any, res: any, next: any) => {
  console.error("Unhandled request error", err);
  const status = Number.isInteger(err?.status) ? err.status : 500;
  res.status(status).json({
    error: status >= 500 ? "Internal server error" : err.message,
  });
});

// ============ START SERVER ============

const PORT = process.env.PORT || 3000;

server.listen(PORT, () => {
  console.log(`
╔════════════════════════════════════════╗
║   КОНТАНТА Admin Panel Backend        ║
║   🚀 Server running on port ${PORT}         
║   🔌 WebSocket enabled                 ║
║   🗄️  Database connected               ║
╚════════════════════════════════════════╝
  `);
});

// Graceful shutdown
process.on("SIGTERM", () => {
  console.log("SIGTERM received, shutting down gracefully");
  server.close(() => {
    console.log("Server closed");
    process.exit(0);
  });
});

export { app, server, io };
