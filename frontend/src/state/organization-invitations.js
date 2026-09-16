import { ref } from "vue";

import { defineStore } from "pinia";

import {
  createOrganizationInvitation,
  listOrganizationInvitations,
  resendOrganizationInvitation,
  revokeOrganizationInvitation,
} from "@/services/organization-invitations.js";

export const useOrganizationInvitationsStore = defineStore(
  "organization-invitations",
  () => {
    const invitations = ref([]);
    const invitation = ref(null);

    const fetching = ref(false);
    const creating = ref(false);

    const resendingId = ref(null);
    const revokingId = ref(null);

    function upsertInvitation(value, prependWhenNew = false) {
      const index = invitations.value.findIndex((item) => item.id === value.id);

      if (index >= 0) {
        invitations.value.splice(index, 1, value);

        return;
      }

      if (prependWhenNew) {
        invitations.value.unshift(value);

        return;
      }

      invitations.value.push(value);
    }

    async function fetchInvitations() {
      fetching.value = true;

      try {
        const result = await listOrganizationInvitations();

        invitations.value = Array.isArray(result) ? result : [];

        return invitations.value;
      } finally {
        fetching.value = false;
      }
    }

    async function create(payload) {
      creating.value = true;

      try {
        const result = await createOrganizationInvitation(payload);

        invitation.value = result;

        upsertInvitation(result, true);

        return result;
      } finally {
        creating.value = false;
      }
    }

    async function resend(invitationId) {
      resendingId.value = invitationId;

      try {
        const result = await resendOrganizationInvitation(invitationId);

        upsertInvitation(result);

        return result;
      } finally {
        if (resendingId.value === invitationId) {
          resendingId.value = null;
        }
      }
    }

    async function revoke(invitationId) {
      revokingId.value = invitationId;

      try {
        const result = await revokeOrganizationInvitation(invitationId);

        upsertInvitation(result);

        return result;
      } finally {
        if (revokingId.value === invitationId) {
          revokingId.value = null;
        }
      }
    }

    function clear() {
      invitations.value = [];
      invitation.value = null;

      fetching.value = false;
      creating.value = false;

      resendingId.value = null;
      revokingId.value = null;
    }

    return {
      invitations,
      invitation,

      fetching,
      creating,

      resendingId,
      revokingId,

      fetchInvitations,
      create,
      resend,
      revoke,
      clear,
    };
  },
);
