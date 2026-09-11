import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { apiClient } from "../services/api";

// ============ APPLICATIONS ============

export function useApplications(filters?: {
  status?: string;
  assignedToId?: string;
  category?: string;
}) {
  return useQuery({
    queryKey: ["applications", filters],
    queryFn: () =>
      apiClient.getApplications({
        status: filters?.status,
        assignedToId: filters?.assignedToId,
        category: filters?.category,
        limit: 100,
      }),
    refetchInterval: 60000, // 60 seconds
    staleTime: 30000, // 30 seconds
  });
}

export function useApplication(id: string) {
  return useQuery({
    queryKey: ["application", id],
    queryFn: () => apiClient.getApplication(id),
    enabled: !!id,
    staleTime: 30000,
  });
}

export function useTakeApplication() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, version }: { id: string; version: number }) =>
      apiClient.takeApplication(id, version),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["applications"] });
    },
  });
}

export function useReleaseApplication() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => apiClient.releaseApplication(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["applications"] });
    },
  });
}

export function useChangeApplicationStatus() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({
      id,
      newStatus,
      reason,
    }: {
      id: string;
      newStatus: string;
      reason?: string;
    }) => apiClient.changeApplicationStatus(id, newStatus, reason),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["applications"] });
    },
  });
}

// ============ NOTIFICATIONS ============

export function useNotifications() {
  return useQuery({
    queryKey: ["notifications"],
    queryFn: () => apiClient.getNotifications({ limit: 50 }),
    refetchInterval: 30000,
    staleTime: 10000,
  });
}

export function useMarkNotificationAsRead() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => apiClient.markNotificationAsRead(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["notifications"] });
    },
  });
}

// ============ USERS ============

export function useUsers() {
  return useQuery({
    queryKey: ["users"],
    queryFn: () => apiClient.getUsers(),
    staleTime: 60000,
  });
}

export function useCreateUser() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: any) => apiClient.createUser(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["users"] });
    },
  });
}

export function useUpdateUser() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: string; data: any }) =>
      apiClient.updateUser(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["users"] });
    },
  });
}

export function useDeleteUser() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => apiClient.deleteUser(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["users"] });
    },
  });
}

// ============ ROLES ============

export function useRoles() {
  return useQuery({
    queryKey: ["roles"],
    queryFn: () => apiClient.getRoles(),
    staleTime: 60000,
  });
}

export function useCreateRole() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: any) => apiClient.createRole(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["roles"] });
    },
  });
}

// ============ STATISTICS ============

export function useStatistics(params?: { from?: string; to?: string }) {
  return useQuery({
    queryKey: ["statistics", params],
    queryFn: () => apiClient.getStatistics(params),
    refetchInterval: 120000, // 2 minutes
    staleTime: 60000,
  });
}
