import { authenticatedClient as apiClient } from "@/modules/http";

export function login(credentials) {
  return apiClient.post("/auth/login", credentials);
}

export function register(payload) {
  return apiClient.post("/auth/register", payload);
}

export function me() {
  return apiClient.get("/auth/me");
}

export function refresh() {
  return apiClient.post("/auth/refresh");
}

export function logout() {
  return apiClient.post("/auth/logout");
}
