import apiClient from "./client.js";

const BASE_PATH = "/organization-roles";

export function listOrganizationRoles() {
  return apiClient.get(BASE_PATH);
}

export function getOrganizationRole(roleId) {
  return apiClient.get(`${BASE_PATH}/${roleId}`);
}

export function updateOrganizationRolePermissions(roleId, permissions) {
  return apiClient.patch(`${BASE_PATH}/${roleId}/permissions`, {
    permissions,
  });
}
