import axios, { AxiosInstance } from "axios";
import { useAuthStore } from "../context/store";
import { AuthResponse, User, Application } from "../types";

class ApiClient {
  private client: AxiosInstance;

  constructor() {
    this.client = axios.create({
      baseURL: import.meta.env.VITE_API_URL || "http://localhost:3000",
    });

    // Add auth header
    this.client.interceptors.request.use((config) => {
      const token = useAuthStore.getState().accessToken;
      if (token) {
        config.headers.Authorization = `Bearer ${token}`;
      }
      return config;
    });

    // Handle 401 responses
    this.client.interceptors.response.use(
      (response) => response,
      (error) => {
        if (error.response?.status === 401) {
          useAuthStore.getState().logout();
          window.location.href = "/login";
        }
        return Promise.reject(error);
      }
    );
  }

  // ============ AUTH ============

  async login(username: string, password: string): Promise<AuthResponse> {
    const response = await this.client.post("/api/auth/login", {
      username,
      password,
    });
    return response.data;
  }

  async getCurrentUser(): Promise<User> {
    const response = await this.client.get("/api/auth/me");
    return response.data;
  }

  // ============ APPLICATIONS ============

  async getApplications(params: {
    status?: string;
    assignedToId?: string;
    category?: string;
    limit?: number;
    offset?: number;
  }) {
    const response = await this.client.get("/api/applications", { params });
    return response.data;
  }

  async getApplication(id: string): Promise<Application> {
    const response = await this.client.get(`/api/applications/${id}`);
    return response.data;
  }

  async createApplication(data: {
    clientName: string;
    clientPhone: string;
    clientEmail: string;
    category: string;
    description: string;
  }): Promise<Application> {
    const response = await this.client.post("/api/applications", data);
    return response.data;
  }

  async takeApplication(
    id: string,
    version: number
  ): Promise<Application> {
    const response = await this.client.post(
      `/api/applications/${id}/take`,
      { version }
    );
    return response.data;
  }

  async releaseApplication(id: string): Promise<Application> {
    const response = await this.client.post(`/api/applications/${id}/release`);
    return response.data;
  }

  async changeApplicationStatus(
    id: string,
    newStatus: string,
    reason?: string
  ): Promise<Application> {
    const response = await this.client.patch(
      `/api/applications/${id}/status`,
      { newStatus, reason }
    );
    return response.data;
  }

  async getApplicationChanges(since: string) {
    const response = await this.client.get("/api/applications/changes", {
      params: { since },
    });
    return response.data;
  }

  // ============ NOTIFICATIONS ============

  async getNotifications(params: { limit?: number; offset?: number } = {}) {
    const response = await this.client.get("/api/notifications", {
      params: { ...params, limit: params.limit || 50 },
    });
    return response.data;
  }

  async markNotificationAsRead(id: string) {
    const response = await this.client.put(
      `/api/notifications/${id}/read`
    );
    return response.data;
  }

  // ============ USERS (Admin only) ============

  async getUsers() {
    const response = await this.client.get("/api/users");
    return response.data;
  }

  async createUser(data: {
    username: string;
    email: string;
    fullName?: string;
    roleId: string;
    password: string;
  }) {
    const response = await this.client.post("/api/users", data);
    return response.data;
  }

  async updateUser(id: string, data: any) {
    const response = await this.client.patch(`/api/users/${id}`, data);
    return response.data;
  }

  async deleteUser(id: string) {
    const response = await this.client.delete(`/api/users/${id}`);
    return response.data;
  }

  // ============ ROLES (Admin only) ============

  async getRoles() {
    const response = await this.client.get("/api/roles");
    return response.data;
  }

  async createRole(data: {
    name: string;
    description?: string;
    permissions: string[];
  }) {
    const response = await this.client.post("/api/roles", data);
    return response.data;
  }

  async updateRole(
    id: string,
    data: { name?: string; description?: string; permissions?: string[] }
  ) {
    const response = await this.client.patch(`/api/roles/${id}`, data);
    return response.data;
  }

  // ============ STATISTICS ============

  async getStatistics(params: { from?: string; to?: string } = {}) {
    const response = await this.client.get("/api/statistics", { params });
    return response.data;
  }
}

export const apiClient = new ApiClient();
