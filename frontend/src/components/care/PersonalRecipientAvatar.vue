<template>
  <button
    v-if="enabled"
    type="button"
    class="personal-avatar"
    aria-label="Alterar minha foto do assistido"
    title="Alterar minha foto do assistido"
    @click="open = true"
  >
    <img v-if="store.url" :src="store.url" alt="" />
    <span v-else aria-hidden="true">{{ initials }}</span>
  </button>
  <span v-else class="personal-avatar" aria-hidden="true">{{ initials }}</span>
  <AppDialog
    :open="open"
    title="Minha foto do assistido"
    :busy="store.pending"
    :error="store.error"
    @close="close"
  >
    <form class="care-form" @submit.prevent="save">
      <p>
        Esta foto aparece apenas para você. Cada responsável escolhe sua própria
        imagem do assistido.
      </p>
      <img
        v-if="preview || store.url"
        class="avatar-preview"
        :src="preview || store.url"
        alt="Sua foto do assistido"
      />
      <label for="personal-recipient-photo">Selecionar foto</label>
      <input
        id="personal-recipient-photo"
        type="file"
        accept="image/jpeg,image/png,image/webp"
        :disabled="store.pending"
        aria-describedby="personal-photo-help"
        @change="selectFile"
      />
      <p id="personal-photo-help">
        JPG, PNG ou WebP, até 2 MB e 4096 × 4096 pixels.
      </p>
      <div class="care-actions">
        <AppButton
          v-if="store.error && !file"
          variant="ghost"
          :disabled="store.pending"
          @click="store.load"
          >Tentar carregar novamente</AppButton
        >
        <AppButton
          v-if="store.url"
          variant="ghost"
          :disabled="store.pending"
          @click="remove"
          >Remover minha foto</AppButton
        >
        <AppButton variant="ghost" :disabled="store.pending" @click="close"
          >Cancelar</AppButton
        >
        <AppButton
          type="submit"
          variant="primary"
          :disabled="!file || store.pending"
          :loading="store.pending"
          >Salvar foto</AppButton
        >
      </div>
    </form>
  </AppDialog>
</template>

<script setup>
import { onBeforeUnmount, ref, watch } from "vue";
import { AppButton, AppDialog } from "@/components/ui";
import { useRecipientAvatarStore } from "@/state/recipient-avatar.js";

const props = defineProps({
  initials: String,
  enabled: Boolean,
  contextKey: String,
});
const store = useRecipientAvatarStore();
const open = ref(false);
const file = ref(null);
const preview = ref("");
function clearSelection() {
  if (preview.value) URL.revokeObjectURL(preview.value);
  preview.value = "";
  file.value = null;
}
function close() {
  open.value = false;
  clearSelection();
}
function selectFile(event) {
  clearSelection();
  file.value = event.target.files?.[0] || null;
  if (file.value) preview.value = URL.createObjectURL(file.value);
}
async function save() {
  if (file.value && (await store.save(file.value))) close();
}
async function remove() {
  if (await store.save(null)) close();
}
watch(
  () => [props.contextKey, props.enabled],
  () => {
    close();
    store.clear();
    if (props.enabled) store.load();
  },
  { immediate: true },
);
onBeforeUnmount(() => {
  clearSelection();
  store.clear();
});
</script>

<style scoped>
.personal-avatar {
  display: grid;
  place-items: center;
  flex: 0 0 2.5rem;
  width: 2.5rem;
  height: 2.5rem;
  padding: 0;
  overflow: hidden;
  border: 0;
  border-radius: 50%;
  background: var(--palette-care-green);
  color: var(--color-text);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-semibold);
}
button.personal-avatar {
  cursor: pointer;
}
button.personal-avatar:focus-visible {
  outline: 2px solid white;
  outline-offset: 3px;
}
.personal-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.avatar-preview {
  width: 8rem;
  height: 8rem;
  border-radius: 50%;
  object-fit: cover;
}
input {
  max-width: 100%;
}
</style>
