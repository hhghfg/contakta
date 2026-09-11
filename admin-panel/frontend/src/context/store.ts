import { create } from "zustand";

interface Admin {
  id: number;
  username: string;
}

interface AuthState {
  user: Admin | null;
  token: string | null;
  isAuthenticated: boolean;
  setAuth: (user: Admin, token: string) => void;
  logout: () => void;
}

export const useAuthStore = create<AuthState>((set) => ({
  user: null,
  token: localStorage.getItem("accessToken"),
  isAuthenticated: !!localStorage.getItem("accessToken"),

  setAuth: (user, token) => {
    localStorage.setItem("accessToken", token);
    set({ user, token, isAuthenticated: true });
  },

  logout: () => {
    localStorage.removeItem("accessToken");
    set({ user: null, token: null, isAuthenticated: false });
  },
}));
