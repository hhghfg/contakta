import { ReactNode } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { Menu, X, LogOut, Settings, Moon, Sun } from "lucide-react";
import { useAuthStore, useUIStore, useThemeStore, useWebSocketStore } from "../context/store";
import NotificationCenter from "./NotificationCenter";

interface MainLayoutProps {
  children: ReactNode;
}

export default function MainLayout({ children }: MainLayoutProps) {
  const navigate = useNavigate();
  const location = useLocation();
  const { user, logout } = useAuthStore();
  const { sidebarOpen, toggleSidebar } = useUIStore();
  const { theme, setTheme, getEffectiveTheme } = useThemeStore();
  const { status: wsStatus } = useWebSocketStore();

  const menuItems = [
    { label: "Dashboard", path: "/", icon: "📊" },
    { label: "Applications", path: "/applications", icon: "📋" },
    ...(user?.role?.permissions?.some((p) => p.name === "can_manage_users")
      ? [{ label: "Users", path: "/users", icon: "👥" }]
      : []),
    ...(user?.role?.permissions?.some((p) => p.name === "can_manage_roles")
      ? [{ label: "Roles", path: "/roles", icon: "🔐" }]
      : []),
    { label: "Settings", path: "/settings", icon: "⚙️" },
  ];

  return (
    <div className="flex h-screen bg-slate-50 dark:bg-slate-950">
      {/* Sidebar */}
      <aside
        className={`${
          sidebarOpen ? "w-64" : "w-20"
        } bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-700 transition-all duration-300 flex flex-col`}
      >
        {/* Logo */}
        <div className="p-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
          <h1
            className={`font-bold text-blue-600 ${
              sidebarOpen ? "text-xl" : "text-lg"
            }`}
          >
            {sidebarOpen ? "КОНТАНТА" : "К"}
          </h1>
          <button
            onClick={toggleSidebar}
            className="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded"
          >
            {sidebarOpen ? (
              <X className="w-5 h-5" />
            ) : (
              <Menu className="w-5 h-5" />
            )}
          </button>
        </div>

        {/* Menu */}
        <nav className="flex-1 p-4 space-y-2">
          {menuItems.map((item) => (
            <button
              key={item.path}
              onClick={() => navigate(item.path)}
              className={`w-full flex items-center gap-3 px-4 py-2 rounded-lg transition-colors ${
                location.pathname === item.path
                  ? "bg-blue-500 text-white"
                  : "text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800"
              }`}
            >
              <span className="text-lg">{item.icon}</span>
              {sidebarOpen && <span className="font-medium">{item.label}</span>}
            </button>
          ))}
        </nav>

        {/* User Profile */}
        <div className="p-4 border-t border-slate-200 dark:border-slate-700">
          <div
            className={`${
              sidebarOpen
                ? "flex items-center gap-2"
                : "flex flex-col items-center"
            }`}
          >
            <div
              className={`w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold text-sm`}
            >
              {user?.fullName?.charAt(0) || user?.username?.charAt(0)}
            </div>
            {sidebarOpen && (
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-slate-900 dark:text-white truncate">
                  {user?.fullName || user?.username}
                </p>
                <p className="text-xs text-slate-600 dark:text-slate-400 truncate">
                  {user?.role?.name}
                </p>
              </div>
            )}
          </div>
        </div>
      </aside>

      {/* Main Content */}
      <div className="flex-1 flex flex-col">
        {/* Top Bar */}
        <header className="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 px-6 py-4 flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
              {menuItems.find((item) => item.path === location.pathname)
                ?.label || "Dashboard"}
            </h1>
          </div>

          <div className="flex items-center gap-4">
            {/* WS Status */}
            <div className="flex items-center gap-2 text-sm">
              <div
                className={`w-2 h-2 rounded-full ${
                  wsStatus === "connected"
                    ? "bg-green-500"
                    : wsStatus === "polling"
                    ? "bg-yellow-500"
                    : "bg-red-500"
                }`}
              />
              <span className="text-slate-600 dark:text-slate-400">
                {wsStatus === "connected"
                  ? "Live"
                  : wsStatus === "polling"
                  ? "Polling"
                  : "Offline"}
              </span>
            </div>

            {/* Notifications */}
            <NotificationCenter />

            {/* Theme Toggle */}
            <button
              onClick={() => {
                const newTheme =
                  theme === "light" ? "dark" : theme === "dark" ? "system" : "light";
                setTheme(newTheme);
              }}
              className="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
              title={`Theme: ${theme}`}
            >
              {getEffectiveTheme() === "dark" ? (
                <Moon className="w-5 h-5" />
              ) : (
                <Sun className="w-5 h-5" />
              )}
            </button>

            {/* Settings */}
            <button
              onClick={() => navigate("/settings")}
              className="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
            >
              <Settings className="w-5 h-5" />
            </button>

            {/* Logout */}
            <button
              onClick={() => {
                logout();
                navigate("/login");
              }}
              className="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
            >
              <LogOut className="w-5 h-5" />
            </button>
          </div>
        </header>

        {/* Page Content */}
        <main className="flex-1 overflow-auto p-6">
          {children}
        </main>
      </div>
    </div>
  );
}
