import { defineStore } from "pinia";
import { ref } from "vue";
import {
  requestPasswordLink,
  resetPassword,
} from "@/services/password-recovery.js";

export const usePasswordRecoveryStore = defineStore("password-recovery", () => {
  const pending = ref(false);
  async function submit(payload, resetting = false) {
    if (pending.value) return;
    pending.value = true;
    try {
      const { data } = await (resetting
        ? resetPassword(payload)
        : requestPasswordLink(payload.email));
      return data;
    } finally {
      pending.value = false;
    }
  }
  return { pending, submit };
});
