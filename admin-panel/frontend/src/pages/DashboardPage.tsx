import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuthStore } from "../context/store";
import Layout from "../components/Layout";
import toast from "react-hot-toast";

interface Stats {
  total: number;
  new: number;
  processing: number;
  completed: number;
  rejected: number;
}

export default function DashboardPage() {
  const navigate = useNavigate();
  const { token } = useAuthStore();
  const [stats, setStats] = useState<Stats | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!token) {
      navigate("/login");
      return;
    }

    fetchStats();
  }, [token]);

  const fetchStats = async () => {
    try {
      const response = await fetch("/api/simple-stats.php", { headers: { Authorization: `Bearer ${token}` } });
      const data = await response.json();

      if (data.ok) {
        setStats(data.stats);
      }
    } catch (error) {
      console.error(error);
      toast.error("Failed to load stats");
    } finally {
      setLoading(false);
    }
  };

  return (
    <Layout>
      <div className="max-w-6xl mx-auto p-6">
        <h1 className="text-4xl font-bold text-gray-900 dark:text-white mb-8">
          Dashboard
        </h1>

        {loading ? (
          <div className="text-center text-gray-500">Loading...</div>
        ) : stats ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
            <div className="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
              <div className="text-4xl font-bold text-blue-600">
                {stats.total}
              </div>
              <div className="text-gray-600 dark:text-gray-400">Total</div>
            </div>

            <div className="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
              <div className="text-4xl font-bold text-green-600">
                {stats.new}
              </div>
              <div className="text-gray-600 dark:text-gray-400">New</div>
            </div>

            <div className="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
              <div className="text-4xl font-bold text-yellow-600">
                {stats.processing}
              </div>
              <div className="text-gray-600 dark:text-gray-400">Processing</div>
            </div>

            <div className="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
              <div className="text-4xl font-bold text-purple-600">
                {stats.completed}
              </div>
              <div className="text-gray-600 dark:text-gray-400">Completed</div>
            </div>

            <div className="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
              <div className="text-4xl font-bold text-red-600">
                {stats.rejected}
              </div>
              <div className="text-gray-600 dark:text-gray-400">Rejected</div>
            </div>
          </div>
        ) : null}

        <div className="mt-12 grid grid-cols-1 md:grid-cols-4 gap-6">
          <button
            onClick={() => navigate("/applications")}
            className="bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-6 rounded-lg transition text-lg"
          >
            📋 Заявки
          </button>
          <button
            onClick={() => navigate("/stats")}
            className="bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-6 rounded-lg transition text-lg"
          >
            📊 Статистика
          </button>
          <button
            onClick={() => navigate("/calls")}
            className="bg-purple-600 hover:bg-purple-700 text-white font-bold py-4 px-6 rounded-lg transition text-lg"
          >
            ☎️ Звонки
          </button>
          <button
            onClick={() => fetchStats()}
            className="bg-gray-600 hover:bg-gray-700 text-white font-bold py-4 px-6 rounded-lg transition text-lg"
          >
            🔄 Обновить
          </button>
        </div>
      </div>
    </Layout>
  );
}
