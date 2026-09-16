import { createGroup as createGroupRequest } from "@/services/auth.js";
import { listCareGroups } from "@/services/auth.js";
import { useCareStore } from "./care.js";
import { useOrganizationInvitationsStore } from "./organization-invitations.js";
import { computed, ref } from "vue";

import { defineStore } from "pinia";

import {
  context as contextRequest,
  login as loginRequest,
  logout as logoutRequest,
  me as meRequest,
  refresh as refreshRequest,
  register as registerRequest,
} from "@/services/auth.js";

import {
  getAccessToken,
  removeAccessToken,
  setAccessToken,
} from "@/services/auth-token.js";

import {
  getCurrentTenant,
  removeCurrentTenant,
  setCurrentTenant,
} from "@/services/tenant.js";

import { useOrganizationMembersStore } from "@/state/organization-members.js";
import { useOrganizationRolesStore } from "@/state/organization-roles.js";

export const useAuthStore = defineStore("auth", () => {
  const token = ref(null);

  const user = ref(null);

  const last_login_at = ref(null);

  const organizations = ref([]);
  const careGroups = ref([]);

  async function fetchCareGroups() {
    careGroups.value = [];
    const response = await listCareGroups();
    careGroups.value = response.data;
  }

  const organization = ref(null);

  const roles = ref([]);

  const permissions = ref([]);

  const hydrated = ref(false);

  const contextLoaded = ref(false);

  const isAuthenticated = computed(() => Boolean(token.value && user.value));

  const hasOrganization = computed(() => Boolean(organization.value));

  const hasOrganizations = computed(() => organizations.value.length > 0);

  const hasMultipleOrganizations = computed(
    () => organizations.value.length > 1,
  );

  const needsOrganizationSelection = computed(
    () =>
      isAuthenticated.value &&
      !contextLoaded.value &&
      organizations.value.length !== 1,
  );

  const currentTenant = computed(() => organization.value?.slug ?? null);

  const userName = computed(() => user.value?.name ?? "");

  const userEmail = computed(() => user.value?.email ?? "");

  function hasRole(role) {
    return roles.value.includes(role);
  }

  function hasPermission(permission) {
    return permissions.value.includes(permission);
  }

  function applyIdentityPayload(payload) {
    user.value = payload?.user ?? null;

    last_login_at.value =
      payload?.user?.last_login_at ?? (user.value ? last_login_at.value : null);

    organizations.value = Array.isArray(payload?.organizations)
      ? payload.organizations
      : [];
  }

  function applyAuthPayload(payload) {
    const accessToken = payload?.access_token ?? payload?.token ?? null;

    token.value = accessToken;

    applyIdentityPayload(payload);

    if (accessToken) {
      setAccessToken(accessToken);
    } else {
      removeAccessToken();
    }
  }

  function applyContextPayload(payload) {
    organization.value = payload?.organization ?? null;

    roles.value = Array.isArray(payload?.roles) ? payload.roles : [];

    permissions.value = Array.isArray(payload?.permissions)
      ? payload.permissions
      : [];

    contextLoaded.value = Boolean(organization.value);
  }

  function clearTenantStores() {
    useCareStore().clear();
    useOrganizationMembersStore().clear();
    useOrganizationRolesStore().clear();
    useOrganizationInvitationsStore().clear();
  }

  function clearContext({ removeTenant = false, clearStores = false } = {}) {
    organization.value = null;

    roles.value = [];

    permissions.value = [];

    contextLoaded.value = false;

    if (clearStores) {
      clearTenantStores();
    }

    if (removeTenant) {
      removeCurrentTenant();
    }
  }

  function clearAuth() {
    token.value = null;

    user.value = null;

    last_login_at.value = null;

    organizations.value = [];
    careGroups.value = [];

    clearContext({
      removeTenant: true,
      clearStores: true,
    });

    removeAccessToken();
  }

  function restoreToken() {
    token.value = getAccessToken();

    hydrated.value = true;

    return token.value;
  }

  function resolveOrganizationByTenant(tenant) {
    if (!tenant) {
      return null;
    }

    return (
      organizations.value.find(
        (item) => String(item.slug) === String(tenant),
      ) ?? null
    );
  }

  function resolveInitialOrganization() {
    const persistedTenant = getCurrentTenant();

    if (persistedTenant) {
      const persistedOrganization =
        resolveOrganizationByTenant(persistedTenant);

      if (persistedOrganization) {
        return persistedOrganization;
      }

      removeCurrentTenant();
    }

    if (organizations.value.length === 1) {
      return organizations.value[0];
    }

    return null;
  }

  async function fetchContext(tenant = getCurrentTenant()) {
    if (!tenant) {
      clearContext();

      return null;
    }

    const response = await contextRequest();

    applyContextPayload(response.data);

    return response.data;
  }

  async function selectOrganization(selectedOrganization) {
    const resolvedOrganization =
      typeof selectedOrganization === "object"
        ? selectedOrganization
        : resolveOrganizationByTenant(selectedOrganization);

    if (!resolvedOrganization?.slug) {
      throw new Error("Grupo inválida.");
    }

    const tenant = resolvedOrganization.slug;

    const isChangingTenant =
      currentTenant.value !== null && currentTenant.value !== tenant;

    clearContext({
      clearStores: isChangingTenant,
    });

    setCurrentTenant(tenant);

    try {
      return await fetchContext(tenant);
    } catch (error) {
      removeCurrentTenant();

      clearContext({
        clearStores: true,
      });

      throw error;
    }
  }

  async function initializeContext() {
    const initialOrganization = resolveInitialOrganization();

    if (!initialOrganization) {
      clearContext();

      return null;
    }

    return selectOrganization(initialOrganization);
  }

  async function login(credentials) {
    const response = await loginRequest(credentials);

    applyAuthPayload(response.data);

    last_login_at.value =
      response.data?.user?.last_login_at ?? new Date().toISOString();

    await initializeContext();

    return response.data;
  }

  async function register(payload) {
    const response = await registerRequest(payload);

    applyAuthPayload(response.data);

    await initializeContext();

    return response.data;
  }

  async function fetchMe() {
    const response = await meRequest();

    const payload = response.data;

    applyIdentityPayload(payload);

    return payload;
  }

  async function refresh() {
    const response = await refreshRequest();

    applyAuthPayload(response.data);

    return response.data;
  }

  async function logout() {
    try {
      if (token.value) {
        await logoutRequest();
      }
    } finally {
      clearAuth();
    }
  }

  async function createGroup(name) {
    const response = await createGroupRequest(name);
    await fetchMe();
    await selectOrganization(response.data);
    return response.data;
  }

  async function hydrate() {
    if (hydrated.value) {
      return;
    }

    restoreToken();

    if (!token.value) {
      return;
    }

    try {
      await fetchMe();

      await initializeContext();
    } catch {
      clearAuth();
    }
  }

  return {
    token,
    user,
    last_login_at,

    organizations,
    careGroups,
    fetchCareGroups,
    organization,

    roles,
    permissions,

    hydrated,
    contextLoaded,

    isAuthenticated,
    hasOrganization,
    hasOrganizations,
    hasMultipleOrganizations,
    needsOrganizationSelection,
    currentTenant,

    userName,
    userEmail,

    hasRole,
    hasPermission,

    applyIdentityPayload,
    applyAuthPayload,
    applyContextPayload,

    clearTenantStores,
    clearContext,
    clearAuth,
    restoreToken,

    resolveOrganizationByTenant,
    resolveInitialOrganization,

    fetchContext,
    selectOrganization,
    initializeContext,

    login,
    register,
    createGroup,
    fetchMe,
    refresh,
    logout,
    hydrate,
  };
});
