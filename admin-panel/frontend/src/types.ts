// Frontend Types

export interface User {
  id: string;
  username: string;
  email: string;
  fullName: string | null;
  role: Role;
  status: string;
  themePreference: "light" | "dark" | "system";
  createdAt: string;
  lastLoginAt: string | null;
}

export interface Role {
  id: string;
  name: string;
  hierarchy: number;
  permissions: Permission[];
}

export interface Permission {
  id: string;
  name: string;
  category: string;
}

export interface Application {
  id: string;
  clientName: string;
  clientPhone: string;
  clientEmail: string;
  category: string;
  description: string;
  status: "NEW" | "IN_PROGRESS" | "AWAITING_CLIENT" | "RESOLVED" | "CLOSED" | "REJECTED";
  priority: number;
  assignedTo: User | null;
  createdAt: string;
  updatedAt: string;
  resolvedAt: string | null;
  version: number;
}

export interface ApplicationComment {
  id: string;
  applicationId: string;
  author: User;
  content: string;
  isInternal: boolean;
  createdAt: string;
}

export interface Notification {
  id: string;
  type: string;
  title: string;
  message: string;
  applicationId?: string;
  payload?: any;
  readAt?: string;
  createdAt: string;
}

export interface AuthResponse {
  accessToken: string;
  refreshToken?: string;
  user: User;
}

export type Theme = "light" | "dark" | "system";

export interface UserPreferences {
  themePreference: Theme;
  soundNotifications: boolean;
  pollingInterval: number;
}
