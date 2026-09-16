import apiClient from "./client.js";

const BASE_PATH = "/organization-members";

export function listOrganizationMembers() {
  return apiClient.get(BASE_PATH);
}

export function updateOrganizationMemberRole(userId, role) {
  return apiClient.patch(`${BASE_PATH}/${userId}/role`, {
    role,
  });
}

export function updateOrganizationMemberStatus(userId, status) {
  return apiClient.patch(`${BASE_PATH}/${userId}/status`, {
    status,
  });
}
