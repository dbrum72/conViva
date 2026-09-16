import { computed, ref } from "vue";

import { defineStore } from "pinia";

import {
  listOrganizationMembers as listOrganizationMembersRequest,
  updateOrganizationMemberRole as updateOrganizationMemberRoleRequest,
  updateOrganizationMemberStatus as updateOrganizationMemberStatusRequest,
} from "@/services/organization-members.js";

export const useOrganizationMembersStore = defineStore(
  "organization-members",
  () => {
    const members = ref([]);

    const count = computed(() => members.value.length);

    const activeMembers = computed(() =>
      members.value.filter((member) => member.status === "active"),
    );

    const inactiveMembers = computed(() =>
      members.value.filter((member) => member.status === "inactive"),
    );

    function getById(id) {
      return members.value.find((member) => member.id === Number(id)) ?? null;
    }

    function updateMemberLocally(userId, changes) {
      const member = getById(userId);

      if (!member) {
        return null;
      }

      Object.assign(member, changes);

      return member;
    }

    async function fetchMembers() {
      const response = await listOrganizationMembersRequest();

      members.value = Array.isArray(response.data) ? response.data : [];

      return members.value;
    }

    async function updateRole(userId, role) {
      await updateOrganizationMemberRoleRequest(userId, role);

      return updateMemberLocally(userId, {
        role,
      });
    }

    async function updateStatus(userId, status) {
      await updateOrganizationMemberStatusRequest(userId, status);

      return updateMemberLocally(userId, {
        status,
      });
    }

    function clear() {
      members.value = [];
    }

    return {
      members,

      count,
      activeMembers,
      inactiveMembers,

      getById,

      fetchMembers,
      updateRole,
      updateStatus,

      clear,
    };
  },
);
