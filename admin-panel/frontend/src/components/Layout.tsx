import { useNavigate } from "react-router-dom";
import { useAuthStore } from "../context/store";

export default function Layout({ children }: { children: React.ReactNode }) {
  const navigate = useNavigate();
  const { user, logout } = useAuthStore();

  return (
    <div className="flex h-screen bg-gray-100 dark:bg-gray-900">
      {/* Sidebar */}
      <div className="w-64 bg-gray-900 text-white shadow-lg">
        <div className="p-6 border-b border-gray-700">
          <h1 className="text-2xl font-bold">КОНТАНТА</h1>
          <p className="text-sm text-gray-400">{user?.username}</p>
        </div>

        <nav className="mt-6 space-y-2">
          <button
            onClick={() => navigate("/")}
            className="w-full text-left px-6 py-3 hover:bg-green-600 rounded transition"
          >
            📊 Dashboard
          </button>
          <button
            onClick={() => navigate("/applications")}
            className="w-full text-left px-6 py-3 hover:bg-green-600 rounded transition"
          >
            📋 Заявки
          </button>
          <button
            onClick={() => navigate("/calls")}
            className="w-full text-left px-6 py-3 hover:bg-green-600 rounded transition"
          >
            ☎️ Звонки
          </button>
          <button
            onClick={() => navigate("/stats")}
            className="w-full text-left px-6 py-3 hover:bg-green-600 rounded transition"
          >
            📊 Статистика
          </button>
        </nav>

        <div className="absolute bottom-0 left-0 right-0 p-6 border-t border-gray-700 w-64">
          <button
            onClick={() => {
              logout();
              navigate("/login");
            }}
            className="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition"
          >
            Logout
          </button>
        </div>
      </div>

      {/* Main content */}
      <div className="flex-1 overflow-auto">
        <div className="bg-white dark:bg-gray-800 shadow">
          <div className="max-w-7xl mx-auto px-6 py-4">
            <h1 className="text-xl font-bold text-gray-900 dark:text-white">
              КОНТАНТА Admin Panel
            </h1>
          </div>
        </div>
        {children}
      </div>
    </div>
  );
}
