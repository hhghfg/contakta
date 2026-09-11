import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuthStore } from "../context/store";
import Layout from "../components/Layout";
import toast from "react-hot-toast";

interface Lead {
  id: number;
  name: string;
  phone: string;
  email?: string;
  message?: string;
  status?: string;
  created_at?: string;
}

export default function ApplicationsPage() {
  const navigate = useNavigate();
  const { token } = useAuthStore();
  const [leads, setLeads] = useState<Lead[]>([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState("all");

  useEffect(() => {
    if (!token) {
      navigate("/login");
      return;
    }

    fetchLeads();
  }, [token]);

  const fetchLeads = async () => {
    try {
      const response = await fetch(`/api/simple-leads.php?token=${token}&action=list`);
      const data = await response.json();

      if (data.ok) {
        setLeads(data.leads);
      }
    } catch (error) {
      console.error(error);
      toast.error("Failed to load applications");
    } finally {
      setLoading(false);
    }
  };

  const deleteLead = async (lead_id: number) => {
    if (!window.confirm("Delete this application?")) return;

    try {
      const response = await fetch(
        `/api/simple-leads.php?token=${token}&action=delete`,
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ lead_id }),
        }
      );

      const data = await response.json();

      if (data.ok) {
        toast.success("Application deleted");
        fetchLeads();
      } else {
        toast.error(data.error || "Failed to delete");
      }
    } catch (error) {
      console.error(error);
      toast.error("Network error");
    }
  };

  const filteredLeads = filter === "all" 
    ? leads 
    : leads.filter(l => (l.status || "new") === filter);

  return (
    <Layout>
      <div className="max-w-6xl mx-auto p-6">
        <div className="flex justify-between items-center mb-8">
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
            Заявки
          </h1>
          <button
            onClick={fetchLeads}
            className="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded"
          >
            🔄 Обновить
          </button>
        </div>

        <div className="mb-6 flex gap-2 flex-wrap">
          {["all", "new", "processing", "completed", "rejected"].map((s) => (
            <button
              key={s}
              onClick={() => setFilter(s)}
              className={`px-4 py-2 rounded transition ${
                filter === s
                  ? "bg-green-600 text-white"
                  : "bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white"
              }`}
            >
              {s.charAt(0).toUpperCase() + s.slice(1)}
            </button>
          ))}
        </div>

        {loading ? (
          <div className="text-center text-gray-500">Loading...</div>
        ) : filteredLeads.length === 0 ? (
          <div className="text-center text-gray-500">No applications found</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full bg-white dark:bg-gray-800 rounded-lg shadow">
              <thead className="bg-gray-100 dark:bg-gray-700 border-b">
                <tr>
                  <th className="px-6 py-3 text-left text-sm font-bold">ID</th>
                  <th className="px-6 py-3 text-left text-sm font-bold">Name</th>
                  <th className="px-6 py-3 text-left text-sm font-bold">Phone</th>
                  <th className="px-6 py-3 text-left text-sm font-bold">Email</th>
                  <th className="px-6 py-3 text-left text-sm font-bold">
                    Status
                  </th>
                  <th className="px-6 py-3 text-left text-sm font-bold">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody>
                {filteredLeads.map((lead) => (
                  <tr
                    key={lead.id}
                    className="border-b hover:bg-gray-50 dark:hover:bg-gray-700"
                  >
                    <td className="px-6 py-4 text-sm">{lead.id}</td>
                    <td className="px-6 py-4 text-sm font-medium">
                      {lead.name}
                    </td>
                    <td className="px-6 py-4 text-sm">{lead.phone}</td>
                    <td className="px-6 py-4 text-sm">{lead.email || "-"}</td>
                    <td className="px-6 py-4 text-sm">
                      <span
                        className={`px-3 py-1 rounded text-white text-xs font-bold ${
                          (lead.status || "new") === "new"
                            ? "bg-green-600"
                            : (lead.status || "new") === "processing"
                            ? "bg-yellow-600"
                            : (lead.status || "new") === "completed"
                            ? "bg-blue-600"
                            : "bg-red-600"
                        }`}
                      >
                        {lead.status || "new"}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-sm space-x-2">
                      <a
                        href={`tel:${lead.phone}`}
                        className="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-bold"
                      >
                        ☎️ Call
                      </a>
                      <a
                        href={`https://wa.me/${lead.phone.replace(/\D/g, "")}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs font-bold"
                      >
                        💬 WhatsApp
                      </a>
                      <button
                        onClick={() => deleteLead(lead.id)}
                        className="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs font-bold"
                      >
                        🗑️ Delete
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </Layout>
  );
}
