import { computed, ref } from "vue";
import { listCareGroups, context as contextRequest, createGroup as createGroupRequest } from "../api/groups.js";
import { getCurrentTenant, removeCurrentTenant, setCurrentTenant } from "../context/tenant.js";
import { useNotificationsStore } from "@/modules/notifications";
import { useCareStore, useRecipientAvatarStore } from "@/modules/care";
import { useAgendaStore } from "@/modules/agenda";
import { useExpensesStore } from "@/modules/expenses";
import { useHistoryStore } from "@/modules/history";
import { useOrganizationInvitationsStore } from "./organization-invitations.js";
import { useOrganizationMembersStore } from "./organization-members.js";
import { useOrganizationRolesStore } from "./organization-roles.js";

export function useGroupContext({ isAuthenticated, identityVersion, fetchMe }) {
  let contextGeneration = 0;
  const organizations = ref([]);
  const careGroups = ref([]);

  async function fetchCareGroups() {
    const generation = identityVersion();
    careGroups.value = [];
    const response = await listCareGroups();
    if (generation === identityVersion()) careGroups.value = response.data;
  }

  const organization = ref(null);

  const roles = ref([]);

  const permissions = ref([]);

  const contextLoaded = ref(false);

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

  function hasRole(role) {
    return roles.value.includes(role);
  }

  function hasPermission(permission) {
    return permissions.value.includes(permission);
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
    useRecipientAvatarStore().clear();
    useCareStore().clear();
    useAgendaStore().clear();
    useExpensesStore().clear();
    useHistoryStore().clear();
    useNotificationsStore().clear();
    useOrganizationMembersStore().clear();
    useOrganizationRolesStore().clear();
    useOrganizationInvitationsStore().clear();
  }

  function clearContext({ removeTenant = false, clearStores = false } = {}) {
    contextGeneration++;
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

    const generation = contextGeneration;
    const response = await contextRequest();
    if (generation !== contextGeneration || tenant !== getCurrentTenant())
      return null;

    applyContextPayload(response.data);

    return response.data;
  }

  async function selectOrganization(selectedOrganization) {
    const resolvedOrganization =
      typeof selectedOrganization === "object"
        ? selectedOrganization
        : resolveOrganizationByTenant(selectedOrganization);

    if (!resolvedOrganization?.slug) {
      throw new Error("Grupo inválido.");
    }

    const tenant = resolvedOrganization.slug;

    clearContext({
      clearStores: true,
    });

    setCurrentTenant(tenant);
    const generation = contextGeneration;

    try {
      return await fetchContext(tenant);
    } catch (error) {
      if (generation !== contextGeneration) throw error;
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

  async function createGroup(name, timezone) {
    const response = await createGroupRequest(name, timezone);
    await fetchMe();
    await selectOrganization(response.data);
    return response.data;
  }

  function applyMemberships(payload) {
    organizations.value = Array.isArray(payload?.organizations)
      ? payload.organizations
      : [];
  }

  function resetGroupContext() {
    organizations.value = [];
    careGroups.value = [];

    clearContext({
      removeTenant: true,
      clearStores: true,
    });
  }

  return {
    organizations,
    careGroups,
    fetchCareGroups,
    organization,
    roles,
    permissions,
    contextLoaded,
    hasOrganization,
    hasOrganizations,
    hasMultipleOrganizations,
    needsOrganizationSelection,
    currentTenant,
    hasRole,
    hasPermission,
    applyContextPayload,
    clearTenantStores,
    clearContext,
    resolveOrganizationByTenant,
    resolveInitialOrganization,
    fetchContext,
    selectOrganization,
    initializeContext,
    createGroup,
    applyMemberships,
    resetGroupContext,
  };
}
