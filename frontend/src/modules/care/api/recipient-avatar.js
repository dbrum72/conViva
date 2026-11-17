import { authenticatedClient as apiClient } from "@/modules/http";

export function fetchAvatar({ tenant, signal } = {}) {
  return apiClient.get("/my-recipient-avatar", {
    responseType: "blob",
    ...(tenant ? { headers: { "X-Tenant": tenant } } : {}),
    signal,
  });
}

export function uploadAvatar(file) {
  const data = new FormData();
  data.append("image", file);
  return apiClient.post("/my-recipient-avatar", data);
}

export function deleteAvatar() {
  return apiClient.delete("/my-recipient-avatar");
}
