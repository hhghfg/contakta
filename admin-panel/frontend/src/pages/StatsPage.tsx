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

export default function StatsPage() {
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
      const response = await fetch(`/api/simple-stats.php?token=${token}`);
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
        <div className="flex justify-between items-center mb-8">
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
            📊 Статистика
          </h1>
          <button
            onClick={fetchStats}
            className="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded"
          >
            🔄 Обновить
          </button>
        </div>

        {loading ? (
          <div className="text-center text-gray-500">Loading...</div>
        ) : stats ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
            <div className="bg-white dark:bg-gray-800 p-8 rounded-lg shadow hover:shadow-lg transition">
              <div className="text-5xl font-bold text-blue-600 mb-2">
                {stats.total}
              </div>
              <div className="text-gray-600 dark:text-gray-400 text-lg">
                Total Applications
              </div>
            </div>

            <div className="bg-white dark:bg-gray-800 p-8 rounded-lg shadow hover:shadow-lg transition">
              <div className="text-5xl font-bold text-green-600 mb-2">
                {stats.new}
              </div>
              <div className="text-gray-600 dark:text-gray-400 text-lg">
                New
              </div>
            </div>

            <div className="bg-white dark:bg-gray-800 p-8 rounded-lg shadow hover:shadow-lg transition">
              <div className="text-5xl font-bold text-yellow-600 mb-2">
                {stats.processing}
              </div>
              <div className="text-gray-600 dark:text-gray-400 text-lg">
                Processing
              </div>
            </div>

            <div className="bg-white dark:bg-gray-800 p-8 rounded-lg shadow hover:shadow-lg transition">
              <div className="text-5xl font-bold text-purple-600 mb-2">
                {stats.completed}
              </div>
              <div className="text-gray-600 dark:text-gray-400 text-lg">
                Completed
              </div>
            </div>

            <div className="bg-white dark:bg-gray-800 p-8 rounded-lg shadow hover:shadow-lg transition">
              <div className="text-5xl font-bold text-red-600 mb-2">
                {stats.rejected}
              </div>
              <div className="text-gray-600 dark:text-gray-400 text-lg">
                Rejected
              </div>
            </div>
          </div>
        ) : null}

        {stats && (
          <div className="mt-12 bg-white dark:bg-gray-800 p-8 rounded-lg shadow">
            <h2 className="text-2xl font-bold mb-6">Breakdown</h2>

            <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
              <div>
                <div className="text-2xl font-bold text-green-600">
                  {stats.total > 0
                    ? Math.round((stats.new / stats.total) * 100)
                    : 0}
                  %
                </div>
                <div className="text-sm text-gray-600 dark:text-gray-400">
                  New
                </div>
              </div>

              <div>
                <div className="text-2xl font-bold text-yellow-600">
                  {stats.total > 0
                    ? Math.round((stats.processing / stats.total) * 100)
                    : 0}
                  %
                </div>
                <div className="text-sm text-gray-600 dark:text-gray-400">
                  Processing
                </div>
              </div>

              <div>
                <div className="text-2xl font-bold text-purple-600">
                  {stats.total > 0
                    ? Math.round((stats.completed / stats.total) * 100)
                    : 0}
                  %
                </div>
                <div className="text-sm text-gray-600 dark:text-gray-400">
                  Completed
                </div>
              </div>

              <div>
                <div className="text-2xl font-bold text-red-600">
                  {stats.total > 0
                    ? Math.round((stats.rejected / stats.total) * 100)
                    : 0}
                  %
                </div>
                <div className="text-sm text-gray-600 dark:text-gray-400">
                  Rejected
                </div>
              </div>

              <div>
                <div className="text-2xl font-bold text-blue-600">
                  {stats.total}
                </div>
                <div className="text-sm text-gray-600 dark:text-gray-400">
                  Total
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </Layout>
  );
}
