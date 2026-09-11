import { Bell, X } from "lucide-react";
import { useUIStore, useWebSocketStore } from "../context/store";
import { useNotifications, useMarkNotificationAsRead } from "../hooks/queries";

export default function NotificationCenter() {
  const { notificationsOpen, setNotificationsOpen } = useUIStore();
  const { unreadCount } = useWebSocketStore();
  const { data: notificationsData } = useNotifications();
  const markAsReadMutation = useMarkNotificationAsRead();

  const handleMarkAsRead = (id: string) => {
    markAsReadMutation.mutate(id);
  };

  return (
    <div className="relative">
      {/* Bell Icon */}
      <button
        onClick={() => setNotificationsOpen(!notificationsOpen)}
        className="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors relative"
      >
        <Bell className="w-5 h-5" />
        {unreadCount > 0 && (
          <span className="absolute top-1 right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">
            {unreadCount > 9 ? "9+" : unreadCount}
          </span>
        )}
      </button>

      {/* Notifications Panel */}
      {notificationsOpen && (
        <div className="absolute top-12 right-0 w-80 bg-white dark:bg-slate-900 rounded-lg shadow-2xl border border-slate-200 dark:border-slate-700 z-50">
          {/* Header */}
          <div className="p-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <h3 className="font-semibold text-slate-900 dark:text-white">
              Notifications
            </h3>
            <button
              onClick={() => setNotificationsOpen(false)}
              className="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded"
            >
              <X className="w-4 h-4" />
            </button>
          </div>

          {/* Notifications List */}
          <div className="max-h-96 overflow-y-auto">
            {notificationsData?.notifications?.length === 0 ? (
              <div className="p-8 text-center text-slate-600 dark:text-slate-400">
                No notifications
              </div>
            ) : (
              notificationsData?.notifications?.map((notification: any) => (
                <div
                  key={notification.id}
                  className={`p-4 border-b border-slate-200 dark:border-slate-700 ${
                    !notification.readAt
                      ? "bg-blue-50 dark:bg-blue-900/20"
                      : ""
                  } hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer transition-colors`}
                  onClick={() =>
                    !notification.readAt &&
                    handleMarkAsRead(notification.id)
                  }
                >
                  <div className="flex items-start gap-3">
                    <div
                      className={`w-2 h-2 rounded-full mt-2 flex-shrink-0 ${
                        !notification.readAt ? "bg-blue-500" : "bg-slate-300"
                      }`}
                    />
                    <div className="flex-1 min-w-0">
                      <p className="font-medium text-slate-900 dark:text-white text-sm">
                        {notification.title}
                      </p>
                      <p className="text-sm text-slate-600 dark:text-slate-400 mt-1">
                        {notification.message}
                      </p>
                      <p className="text-xs text-slate-500 mt-2">
                        {new Date(notification.createdAt).toLocaleString()}
                      </p>
                    </div>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>
      )}
    </div>
  );
}
