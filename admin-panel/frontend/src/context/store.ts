import { create } from "zustand";
import type { Theme, User } from "../types";

type AuthUser = Pick<User, "username"> &
  Partial<Omit<User, "username" | "id">> & { id: string | number };

interface AuthState {
  user: AuthUser | null;
  token: string | null;
  accessToken: string | null;
  isAuthenticated: boolean;
  setAuth: (user: AuthUser, token: string) => void;
  logout: () => void;
}

function loadStoredUser(): AuthUser | null {
  try {
    const value = localStorage.getItem("authUser");
    return value ? (JSON.parse(value) as AuthUser) : null;
  } catch {
    localStorage.removeItem("authUser");
    return null;
  }
}

const storedToken = localStorage.getItem("accessToken");

export const useAuthStore = create<AuthState>((set) => ({
  user: loadStoredUser(),
  token: storedToken,
  accessToken: storedToken,
  isAuthenticated: Boolean(storedToken),

  setAuth: (user, token) => {
    localStorage.setItem("accessToken", token);
    localStorage.setItem("authUser", JSON.stringify(user));
    set({ user, token, accessToken: token, isAuthenticated: true });
  },

  logout: () => {
    localStorage.removeItem("accessToken");
    localStorage.removeItem("authUser");
    set({ user: null, token: null, accessToken: null, isAuthenticated: false });
  },
}));

interface UIState {
  sidebarOpen: boolean;
  notificationsOpen: boolean;
  toggleSidebar: () => void;
  setNotificationsOpen: (open: boolean) => void;
}

export const useUIStore = create<UIState>((set) => ({
  sidebarOpen: true,
  notificationsOpen: false,
  toggleSidebar: () => set((state) => ({ sidebarOpen: !state.sidebarOpen })),
  setNotificationsOpen: (notificationsOpen) => set({ notificationsOpen }),
}));

interface ThemeState {
  theme: Theme;
  setTheme: (theme: Theme) => void;
  getEffectiveTheme: () => "light" | "dark";
}

function getInitialTheme(): Theme {
  const value = localStorage.getItem("theme");
  return value === "light" || value === "dark" || value === "system"
    ? value
    : "system";
}

export const useThemeStore = create<ThemeState>((set, get) => ({
  theme: getInitialTheme(),
  setTheme: (theme) => {
    localStorage.setItem("theme", theme);
    set({ theme });
  },
  getEffectiveTheme: () => {
    const { theme } = get();
    if (theme !== "system") return theme;
    return window.matchMedia("(prefers-color-scheme: dark)").matches
      ? "dark"
      : "light";
  },
}));

type ConnectionStatus = "disconnected" | "connecting" | "connected" | "reconnecting" | "polling";
type CountUpdate = number | ((current: number) => number);

interface WebSocketState {
  status: ConnectionStatus;
  unreadCount: number;
  setStatus: (status: ConnectionStatus) => void;
  setUnreadCount: (value: CountUpdate) => void;
}

export const useWebSocketStore = create<WebSocketState>((set) => ({
  status: "disconnected",
  unreadCount: 0,
  setStatus: (status) => set({ status }),
  setUnreadCount: (value) =>
    set((state) => ({
      unreadCount:
        typeof value === "function" ? value(state.unreadCount) : value,
    })),
}));
