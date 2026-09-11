import { useEffect } from "react";
import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
import { QueryClientProvider, QueryClient } from "@tanstack/react-query";
import { Toaster } from "react-hot-toast";

import { useThemeStore } from "./context/store";
import LoginPage from "./pages/LoginPage";
import DashboardPage from "./pages/DashboardPage";
import ApplicationsPage from "./pages/ApplicationsPage";
import ApplicationDetailPage from "./pages/ApplicationDetailPage";
import CallsPage from "./pages/CallsPage";
import StatsPage from "./pages/StatsPage";
import SettingsPage from "./pages/SettingsPage";
import UsersPage from "./pages/UsersPage";
import RolesPage from "./pages/RolesPage";
import ProtectedRoute from "./components/ProtectedRoute";

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});

function ProtectedPage({ children }: { children: React.ReactNode }) {
  return <ProtectedRoute>{children}</ProtectedRoute>;
}

function AppContent() {
  const { theme, getEffectiveTheme } = useThemeStore();

  useEffect(() => {
    const root = document.documentElement;
    const media = window.matchMedia("(prefers-color-scheme: dark)");
    const applyTheme = () => {
      root.classList.toggle("dark", getEffectiveTheme() === "dark");
    };

    applyTheme();
    media.addEventListener("change", applyTheme);
    return () => media.removeEventListener("change", applyTheme);
  }, [theme, getEffectiveTheme]);

  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route path="/" element={<ProtectedPage><DashboardPage /></ProtectedPage>} />
      <Route path="/applications" element={<ProtectedPage><ApplicationsPage /></ProtectedPage>} />
      <Route path="/applications/:id" element={<ProtectedPage><ApplicationDetailPage /></ProtectedPage>} />
      <Route path="/calls" element={<ProtectedPage><CallsPage /></ProtectedPage>} />
      <Route path="/stats" element={<ProtectedPage><StatsPage /></ProtectedPage>} />
      <Route path="/settings" element={<ProtectedPage><SettingsPage /></ProtectedPage>} />
      <Route path="/users" element={<ProtectedPage><UsersPage /></ProtectedPage>} />
      <Route path="/roles" element={<ProtectedPage><RolesPage /></ProtectedPage>} />
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}

export default function App() {
  return (
    <BrowserRouter>
      <QueryClientProvider client={queryClient}>
        <AppContent />
        <Toaster
          position="top-right"
          toastOptions={{
            style: { background: "#1f2937", color: "#f3f4f6" },
          }}
        />
      </QueryClientProvider>
    </BrowserRouter>
  );
}
