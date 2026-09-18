import { defineStore } from "pinia";
import { ref } from "vue";
import {
  acceptOrganizationInvitation,
  getInvitationAcceptance,
} from "@/services/organization-invitations.js";

export const useInvitationAcceptanceStore = defineStore(
  "invitation-acceptance",
  () => {
    const invitation = ref(null);
    let generation = 0;
    async function load(token) {
      const current = ++generation;
      invitation.value = null;
      const result = await getInvitationAcceptance(token);
      if (current === generation) invitation.value = result;
      return result;
    }
    async function accept(token, payload) {
      return acceptOrganizationInvitation(token, payload);
    }
    return { invitation, load, accept };
  },
);
