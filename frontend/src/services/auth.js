import apiClient from "./client.js";

export function listCareGroups() {
  return apiClient.get("/groups");
}

export function createGroup(name) {
  return apiClient.post("/groups", { name });
}

export function login(credentials) {
  return apiClient.post("/auth/login", credentials);
}

export function register(payload) {
  return apiClient.post("/auth/register", payload);
}

export function me() {
  return apiClient.get("/auth/me");
}

export function context() {
  return apiClient.get("/auth/context");
}

export function refresh() {
  return apiClient.post("/auth/refresh");
}

export function logout() {
  return apiClient.post("/auth/logout");
}
