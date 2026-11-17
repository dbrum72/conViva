import { authenticatedClient as apiClient } from "@/modules/http";

export function listCareGroups() {
  return apiClient.get("/groups");
}

export function createGroup(name, timezone) {
  return apiClient.post("/groups", { name, timezone });
}

export function context() {
  return apiClient.get("/auth/context");
}
