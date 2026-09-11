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

export default function CallsPage() {
  const navigate = useNavigate();
  const { user, token } = useAuthStore();
  const [leads, setLeads] = useState<Lead[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedLead, setSelectedLead] = useState<Lead | null>(null);

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

  const makeCall = (phone: string) => {
    window.location.href = `tel:${phone}`;
  };

  const sendWhatsApp = (phone: string, name: string) => {
    const message = `Здравствуйте, ${name}! Это ответ на вашу заявку.`;
    window.open(
      `https://wa.me/${phone.replace(/\D/g, "")}?text=${encodeURIComponent(message)}`,
      "_blank"
    );
  };

  return (
    <Layout>
      <div className="max-w-6xl mx-auto p-6">
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-8">
          ☎️ Звонки и Связь
        </h1>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* List */}
          <div className="lg:col-span-1 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 className="text-xl font-bold mb-4">Заявки</h2>

            {loading ? (
              <div className="text-gray-500">Loading...</div>
            ) : leads.length === 0 ? (
              <div className="text-gray-500">No applications</div>
            ) : (
              <div className="space-y-2 max-h-96 overflow-y-auto">
                {leads.map((lead) => (
                  <button
                    key={lead.id}
                    onClick={() => setSelectedLead(lead)}
                    className={`w-full text-left px-4 py-2 rounded transition ${
                      selectedLead?.id === lead.id
                        ? "bg-green-600 text-white"
                        : "bg-gray-100 dark:bg-gray-700 hover:bg-gray-200"
                    }`}
                  >
                    <div className="font-semibold">{lead.name}</div>
                    <div className="text-sm">{lead.phone}</div>
                  </button>
                ))}
              </div>
            )}
          </div>

          {/* Detail */}
          <div className="lg:col-span-2">
            {selectedLead ? (
              <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-8">
                <h2 className="text-2xl font-bold mb-6">{selectedLead.name}</h2>

                <div className="space-y-4 mb-8">
                  <div>
                    <label className="text-gray-600 dark:text-gray-400 text-sm">
                      Phone
                    </label>
                    <div className="text-lg font-semibold text-gray-900 dark:text-white">
                      {selectedLead.phone}
                    </div>
                  </div>

                  {selectedLead.email && (
                    <div>
                      <label className="text-gray-600 dark:text-gray-400 text-sm">
                        Email
                      </label>
                      <div className="text-lg font-semibold text-gray-900 dark:text-white">
                        {selectedLead.email}
                      </div>
                    </div>
                  )}

                  {selectedLead.message && (
                    <div>
                      <label className="text-gray-600 dark:text-gray-400 text-sm">
                        Message
                      </label>
                      <div className="text-gray-900 dark:text-white">
                        {selectedLead.message}
                      </div>
                    </div>
                  )}

                  <div>
                    <label className="text-gray-600 dark:text-gray-400 text-sm">
                      Status
                    </label>
                    <span
                      className={`inline-block px-3 py-1 rounded text-white font-bold ${
                        (selectedLead.status || "new") === "new"
                          ? "bg-green-600"
                          : (selectedLead.status || "new") === "processing"
                          ? "bg-yellow-600"
                          : (selectedLead.status || "new") === "completed"
                          ? "bg-blue-600"
                          : "bg-red-600"
                      }`}
                    >
                      {selectedLead.status || "new"}
                    </span>
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <button
                    onClick={() => makeCall(selectedLead.phone)}
                    className="bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-6 rounded-lg transition text-lg"
                  >
                    ☎️ Позвонить
                  </button>
                  <button
                    onClick={() => sendWhatsApp(selectedLead.phone, selectedLead.name)}
                    className="bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-6 rounded-lg transition text-lg"
                  >
                    💬 WhatsApp
                  </button>
                </div>
              </div>
            ) : (
              <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center text-gray-500">
                Select an application to see details and make a call
              </div>
            )}
          </div>
        </div>
      </div>
    </Layout>
  );
}
