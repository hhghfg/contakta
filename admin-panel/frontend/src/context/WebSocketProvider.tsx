import React, { useEffect, useRef, useCallback, ReactNode } from "react";
import { io, Socket } from "socket.io-client";
import { useAuthStore, useWebSocketStore } from "./store";
import { useQueryClient } from "@tanstack/react-query";
import toast from "react-hot-toast";

interface WebSocketContextType {
  socket: Socket | null;
  status: string;
}

export const WebSocketContext = React.createContext<WebSocketContextType>({
  socket: null,
  status: "disconnected",
});

let reconnectAttempt = 0;
const MAX_RECONNECT_ATTEMPTS = 10;
const INITIAL_RECONNECT_DELAY = 1000; // 1 second

/**
 * Exponential backoff: 1s → 2s → 4s → 8s → 16s → 30s
 */
function getReconnectDelay(attempt: number): number {
  const delay = Math.min(
    INITIAL_RECONNECT_DELAY * Math.pow(2, attempt),
    30000
  );
  return delay;
}

export function WebSocketProvider({ children }: { children: ReactNode }) {
  const socketRef = useRef<Socket | null>(null);
  const reconnectTimeoutRef = useRef<NodeJS.Timeout>();
  const pollingIntervalRef = useRef<NodeJS.Timeout>();

  const { accessToken } = useAuthStore();
  const { setStatus, setUnreadCount } = useWebSocketStore();
  const queryClient = useQueryClient();

  // Fallback: polling mode
  const startPolling = useCallback(() => {
    setStatus("polling");
    console.log("📡 Switched to polling mode");

    pollingIntervalRef.current = setInterval(async () => {
      try {
        // Poll for notifications
        const response = await fetch(
          `${import.meta.env.VITE_API_URL}/api/notifications/unread`,
          {
            headers: {
              Authorization: `Bearer ${accessToken}`,
            },
          }
        );

        if (response.ok) {
          const data = await response.json();
          setUnreadCount(data.total || 0);
        }

        // Poll for application changes
        const lastSync = sessionStorage.getItem("lastSync") || new Date(Date.now() - 60000).toISOString();
        const changesResponse = await fetch(
          `${import.meta.env.VITE_API_URL}/api/applications/changes?since=${lastSync}`,
          {
            headers: {
              Authorization: `Bearer ${accessToken}`,
            },
          }
        );

        if (changesResponse.ok) {
          queryClient.invalidateQueries({ queryKey: ["applications"] });
          sessionStorage.setItem("lastSync", new Date().toISOString());
        }
      } catch (error) {
        console.error("Polling error:", error);
      }
    }, 15000); // Every 15 seconds
  }, [accessToken, queryClient, setStatus, setUnreadCount]);

  const stopPolling = useCallback(() => {
    if (pollingIntervalRef.current) {
      clearInterval(pollingIntervalRef.current);
      pollingIntervalRef.current = undefined;
    }
  }, []);

  // Initialize WebSocket connection
  const connect = useCallback(() => {
    if (!accessToken) {
      return;
    }

    if (socketRef.current?.connected) {
      return;
    }

    setStatus("connecting");
    console.log("🔌 Connecting to WebSocket...");

    try {
      const socket = io(import.meta.env.VITE_WS_URL || "ws://localhost:3000", {
        auth: {
          token: accessToken,
        },
        reconnection: true,
        reconnectionDelay: getReconnectDelay(reconnectAttempt),
        reconnectionDelayMax: 30000,
        reconnectionAttempts: MAX_RECONNECT_ATTEMPTS,
      });

      // ============ CONNECTION EVENTS ============

      socket.on("connect", () => {
        console.log("✅ WebSocket connected");
        setStatus("connected");
        stopPolling();
        reconnectAttempt = 0;

        // Subscribe to notifications
        socket.emit("notifications:subscribe");

        // Invalidate queries to refresh data
        queryClient.invalidateQueries({ queryKey: ["applications"] });
        queryClient.invalidateQueries({ queryKey: ["notifications"] });

        toast.success("Live connection restored", { duration: 2000 });
      });

      socket.on("disconnect", () => {
        console.log("❌ WebSocket disconnected");
        setStatus("reconnecting");
        startPolling();
      });

      socket.on("connect_error", (error) => {
        console.error("Connection error:", error);
        setStatus("reconnecting");
        startPolling();
      });

      // ============ APPLICATION EVENTS ============

      socket.on(
        "application:new",
        (data: { applicationId: string; clientName: string }) => {
          console.log("📢 New application:", data);
          setUnreadCount((c) => c + 1);
          queryClient.invalidateQueries({ queryKey: ["applications"] });

          toast.success(
            `New application from ${data.clientName}`,
            { duration: 5000 }
          );

          // Play sound if enabled
          playNotificationSound();
        }
      );

      socket.on(
        "application:taken",
        (data: { applicationId: string; assignedToName: string }) => {
          console.log("📌 Application taken:", data);
          queryClient.invalidateQueries({ queryKey: ["applications"] });
        }
      );

      socket.on("application:released", (data: { applicationId: string }) => {
        console.log("🔓 Application released:", data);
        queryClient.invalidateQueries({ queryKey: ["applications"] });
      });

      socket.on(
        "application:status_changed",
        (data: { applicationId: string; newStatus: string }) => {
          console.log("🔄 Status changed:", data);
          queryClient.invalidateQueries({ queryKey: ["applications"] });
        }
      );

      socket.on("application:comment_added", (data: any) => {
        console.log("💬 Comment added:", data);
        queryClient.invalidateQueries({ queryKey: ["application", data.applicationId] });
      });

      // ============ NOTIFICATION EVENTS ============

      socket.on(
        "notifications:unread",
        (data: { notifications: any[] }) => {
          setUnreadCount(data.notifications.length);
        }
      );

      socket.on(
        "notifications:marked_read",
        (data: { notificationId: string }) => {
          setUnreadCount((c) => Math.max(0, c - 1));
          queryClient.invalidateQueries({ queryKey: ["notifications"] });
        }
      );

      socket.on("error", (error: any) => {
        console.error("Socket error:", error);
        toast.error(error.message || "Connection error");
      });

      socketRef.current = socket;
    } catch (error) {
      console.error("Failed to connect:", error);
      setStatus("reconnecting");
      startPolling();
    }
  }, [accessToken, setStatus, stopPolling, startPolling, queryClient, setUnreadCount]);

  // Disconnect function
  const disconnect = useCallback(() => {
    if (socketRef.current) {
      socketRef.current.disconnect();
      socketRef.current = null;
    }
    stopPolling();
    setStatus("disconnected");
  }, [stopPolling, setStatus]);

  // Handle auth changes
  useEffect(() => {
    if (accessToken) {
      connect();
    } else {
      disconnect();
    }

    return () => {
      // cleanup
    };
  }, [accessToken, connect, disconnect]);

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      disconnect();
      if (reconnectTimeoutRef.current) {
        clearTimeout(reconnectTimeoutRef.current);
      }
    };
  }, [disconnect]);

  return (
    <WebSocketContext.Provider value={{ socket: socketRef.current, status: "connected" }}>
      {children}
    </WebSocketContext.Provider>
  );
}

/**
 * Play notification sound
 */
function playNotificationSound() {
  const audio = new Audio(
    "data:audio/wav;base64,UklGRiYAAABXQVZFZm10IBAAAAABAAEAQB8AAAB9AAACABAAZGF0YQIAAAAAAA=="
  );
  audio.play().catch((e) => console.log("Could not play sound:", e));
}

export function useWebSocket() {
  const context = React.useContext(WebSocketContext);
  if (!context) {
    throw new Error("useWebSocket must be used within WebSocketProvider");
  }
  return context;
}
