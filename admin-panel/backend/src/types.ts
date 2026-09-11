// Backend Types

export interface JwtPayload {
  userId: string;
  username: string;
  roleId: string;
  iat?: number;
  exp?: number;
}

export interface AuthRequest {
  username: string;
  password: string;
}

export interface AuthResponse {
  accessToken: string;
  refreshToken?: string;
  user: UserResponse;
}

export interface UserResponse {
  id: string;
  username: string;
  email: string;
  fullName: string | null;
  role: RoleResponse;
  status: string;
  themePreference: string;
  createdAt: string;
  lastLoginAt: string | null;
}

export interface RoleResponse {
  id: string;
  name: string;
  hierarchy: number;
  permissions: PermissionResponse[];
}

export interface PermissionResponse {
  id: string;
  name: string;
  category: string;
}

export interface ApplicationResponse {
  id: string;
  clientName: string;
  clientPhone: string;
  clientEmail: string;
  category: string;
  description: string;
  status: string;
  priority: number;
  assignedTo: UserResponse | null;
  createdAt: string;
  updatedAt: string;
  resolvedAt: string | null;
  version: number;
}

export interface ApplicationCommentResponse {
  id: string;
  applicationId: string;
  author: UserResponse;
  content: string;
  isInternal: boolean;
  createdAt: string;
}

export interface NotificationResponse {
  id: string;
  type: string;
  title: string;
  message: string;
  applicationId?: string;
  payload?: any;
  readAt?: string;
  createdAt: string;
}

// WebSocket Events
export interface WsEvent<T = any> {
  event: string;
  data: T;
  timestamp: number;
}

export interface TakeApplicationPayload {
  applicationId: string;
  userId: string;
  version: number;
}

export interface ApplicationTakenEvent {
  applicationId: string;
  assignedToId: string;
  assignedToName: string;
}

export interface ApplicationReleasedEvent {
  applicationId: string;
  releasedBy: string;
}

export interface ApplicationStatusChangedEvent {
  applicationId: string;
  oldStatus: string;
  newStatus: string;
  changedBy: string;
  changedByName: string;
  reason?: string;
}

export interface NewApplicationEvent {
  applicationId: string;
  clientName: string;
  clientPhone: string;
  category: string;
  priority: number;
}

export interface NewCommentEvent {
  applicationId: string;
  author: string;
  authorName: string;
  content: string;
  isInternal: boolean;
}
