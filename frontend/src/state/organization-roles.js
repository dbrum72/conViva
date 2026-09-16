import { computed, ref } from "vue";

import { defineStore } from "pinia";

import {
  getOrganizationRole as getOrganizationRoleRequest,
  listOrganizationRoles as listOrganizationRolesRequest,
  updateOrganizationRolePermissions as updateOrganizationRolePermissionsRequest,
} from "@/services/organization-roles.js";

export const useOrganizationRolesStore = defineStore(
  "organization-roles",
  () => {
    const roles = ref([]);
    const selectedRole = ref(null);
    const loadingRole = ref(false);
    const updatingPermissions = ref(false);

    const count = computed(() => roles.value.length);

    const options = computed(() =>
      roles.value.map((role) => ({
        label: role.name,

        value: role.name,
      })),
    );

    function getById(id) {
      return roles.value.find((role) => role.id === Number(id)) ?? null;
    }

    function getByName(name) {
      return roles.value.find((role) => role.name === name) ?? null;
    }

    async function fetchRoles() {
      const response = await listOrganizationRolesRequest();

      roles.value = Array.isArray(response.data) ? response.data : [];

      return roles.value;
    }

    async function fetchRole(roleId) {
      loadingRole.value = true;

      try {
        const response = await getOrganizationRoleRequest(roleId);

        selectedRole.value = response.data;

        return selectedRole.value;
      } finally {
        loadingRole.value = false;
      }
    }

    async function updatePermissions(roleId, permissions) {
      updatingPermissions.value = true;

      try {
        const response = await updateOrganizationRolePermissionsRequest(
          roleId,
          permissions,
        );

        selectedRole.value = response.data;

        return selectedRole.value;
      } finally {
        updatingPermissions.value = false;
      }
    }

    function clear() {
      roles.value = [];
      selectedRole.value = null;
    }

    return {
      roles,
      selectedRole,
      loadingRole,
      updatingPermissions,

      count,
      options,

      getById,
      getByName,

      fetchRoles,
      fetchRole,
      updatePermissions,

      clear,
    };
  },
);
