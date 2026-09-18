import { defineStore } from "pinia";
import { ref } from "vue";
import {
  fetchAvatar,
  uploadAvatar,
  deleteAvatar,
} from "@/services/recipient-avatar.js";

export const useRecipientAvatarStore = defineStore("recipient-avatar", () => {
  const url = ref("");
  const pending = ref(false);
  const error = ref("");
  let generation = 0;

  function setImage(blob) {
    if (url.value) URL.revokeObjectURL(url.value);
    url.value = blob ? URL.createObjectURL(blob) : "";
  }

  function clear() {
    generation++;
    setImage(null);
    pending.value = false;
    error.value = "";
  }

  async function load() {
    const current = ++generation;
    pending.value = true;
    error.value = "";
    try {
      const response = await fetchAvatar();
      if (current === generation)
        setImage(response.status === 204 ? null : response.data);
    } catch {
      if (current === generation) {
        setImage(null);
        error.value = "Não foi possível carregar sua foto. Tente novamente.";
      }
    } finally {
      if (current === generation) pending.value = false;
    }
  }

  async function save(file) {
    if (pending.value) return false;
    const current = ++generation;
    pending.value = true;
    error.value = "";
    try {
      if (file) await uploadAvatar(file);
      else await deleteAvatar();
      if (current !== generation) return false;
      // Only display the image after the server accepts it.
      setImage(file || null);
      return true;
    } catch (failure) {
      if (current === generation) {
        error.value =
          failure.response?.data?.errors?.image?.[0] ||
          failure.response?.data?.message ||
          "Não foi possível salvar sua foto. Tente novamente.";
      }
      return false;
    } finally {
      if (current === generation) pending.value = false;
    }
  }

  return { url, pending, error, clear, load, save };
});
