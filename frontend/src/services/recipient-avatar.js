import apiClient from "./client.js";

export function fetchAvatar() {
  return apiClient.get("/my-recipient-avatar", { responseType: "blob" });
}

export function uploadAvatar(file) {
  const data = new FormData();
  data.append("image", file);
  return apiClient.post("/my-recipient-avatar", data);
}

export function deleteAvatar() {
  return apiClient.delete("/my-recipient-avatar");
}
