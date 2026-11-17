<template>
  <AppButton
    bare
    v-if="enabled"
    type="button"
    class="personal-avatar"
    aria-label="Alterar minha foto do assistido"
    title="Alterar minha foto do assistido"
    @click="open = true"
  >
    <RecipientAvatar :src="store.url" :initials="initials" />
  </AppButton>
  <RecipientAvatar v-else :initials="initials" />
  <AppDialog
    :open="open"
    title="Minha foto do assistido"
    :busy="busy"
    :error="cropError || store.error"
    @close="close"
  >
    <AppForm class="care-form" @submit.prevent="save">
      <p>
        Esta foto aparece apenas para você. Cada responsável escolhe sua própria
        imagem do assistido.
      </p>
      <template v-if="preview">
        <p>
          Use os controles para posicionar o quadro sobre a área que deseja
          salvar.
        </p>
        <div class="avatar-crop">
          <img
            :key="preview"
            :src="preview"
            alt="Foto selecionada para recorte"
            @load="prepareCrop"
            @error="rejectImage"
          />
          <span
            v-if="crop"
            class="avatar-crop__frame"
            :style="frameStyle"
            aria-hidden="true"
          />
        </div>
        <div v-if="crop" class="avatar-crop__controls">
          <AppInput
            label="Zoom"
            :id="fieldId + '-zoom'"
            v-model.number="zoom"
            type="range"
            min="100"
            max="300"
            :disabled="busy"
          />
          <AppInput
            v-if="crop.width > crop.size"
            key="horizontal"
            label="Posição horizontal"
            :id="fieldId + '-horizontal'"
            v-model.number="horizontal"
            type="range"
            min="0"
            max="100"
            :disabled="busy"
          />
          <AppInput
            v-if="crop.height > crop.size"
            key="vertical"
            label="Posição vertical"
            :id="fieldId + '-vertical'"
            v-model.number="vertical"
            type="range"
            min="0"
            max="100"
            :disabled="busy"
          />
        </div>
      </template>
      <img
        v-else-if="store.url"
        class="avatar-preview"
        :src="store.url"
        alt="Sua foto do assistido"
      />
      <AppFileUpload
        id="personal-recipient-photo"
        :model-value="selectedFiles"
        accept="image/jpeg,image/png,image/webp"
        :disabled="busy"
        label="Selecionar foto"
        hint="JPG, PNG ou WebP, até 2 MB e 4096 × 4096 pixels."
        @select="selectFile"
        @remove="clearSelection"
      />
      <div class="care-actions">
        <AppButton
          v-if="store.error && !preview"
          variant="ghost"
          :disabled="busy"
          @click="store.load"
          >Tentar carregar novamente</AppButton
        >
        <AppButton
          v-if="store.url"
          variant="ghost"
          :disabled="busy"
          @click="remove"
          >Remover minha foto</AppButton
        >
        <AppButton variant="cancel" :disabled="busy" @click="close"
          >Cancelar</AppButton
        >
        <AppButton
          type="submit"
          variant="primary"
          :disabled="!crop || busy"
          :loading="busy"
          >Salvar foto</AppButton
        >
      </div>
    </AppForm>
  </AppDialog>
</template>

<script setup>
import { AppForm, AppFileUpload, AppInput } from "@/components/forms";
import { useId } from "vue";
const fieldId = useId();
import { computed, onBeforeUnmount, ref, shallowRef, watch } from "vue";
import { AppButton, AppDialog } from "@/components/ui";
import RecipientAvatar from "@/components/care/RecipientAvatar.vue";
import { useRecipientAvatarStore } from "@/state/recipient-avatar.js";

const props = defineProps({
  initials: String,
  enabled: Boolean,
  contextKey: String,
});
const store = useRecipientAvatarStore();
const open = ref(false);
const preview = ref("");
const selectedFiles = ref([]);
const image = shallowRef(null);
const cropError = ref("");
const encoding = ref(false);
const zoom = ref(100);
const horizontal = ref(50);
const vertical = ref(50);
const busy = computed(() => encoding.value || store.pending);
const crop = computed(() => {
  if (!image.value) return null;
  const { naturalWidth: width, naturalHeight: height } = image.value;
  const size = (Math.min(width, height) * 100) / zoom.value;
  return {
    width,
    height,
    size,
    x: ((width - size) * horizontal.value) / 100,
    y: ((height - size) * vertical.value) / 100,
  };
});
const frameStyle = computed(() => {
  const { width, height, size, x, y } = crop.value;
  return {
    left: `${(x / width) * 100}%`,
    top: `${(y / height) * 100}%`,
    width: `${(size / width) * 100}%`,
    height: `${(size / height) * 100}%`,
  };
});
function clearSelection() {
  if (preview.value) URL.revokeObjectURL(preview.value);
  preview.value = "";
  selectedFiles.value = [];
  image.value = null;
  cropError.value = "";
  zoom.value = 100;
  horizontal.value = 50;
  vertical.value = 50;
}
function close() {
  open.value = false;
  clearSelection();
}
function selectFile([file]) {
  clearSelection();
  if (!file) return;
  if (!["image/jpeg", "image/png", "image/webp"].includes(file.type)) {
    cropError.value = "Use uma foto JPG, PNG ou WebP.";
    return;
  }
  if (file.size > 2 * 1024 * 1024) {
    cropError.value = "A foto deve ter no máximo 2 MB.";
    return;
  }
  selectedFiles.value = [file];
  preview.value = URL.createObjectURL(file);
}
function prepareCrop(event) {
  const img = event.target;
  if (img.getAttribute("src") !== preview.value) return;
  if (img.naturalWidth > 4096 || img.naturalHeight > 4096) {
    clearSelection();
    cropError.value = "A foto deve ter no máximo 4096 × 4096 pixels.";
    return;
  }
  image.value = img;
}
function rejectImage(event) {
  if (event.target.getAttribute("src") !== preview.value) return;
  clearSelection();
  cropError.value = "Não foi possível abrir a foto.";
}
async function save() {
  if (!crop.value || busy.value) return;
  const source = preview.value;
  const { x, y, size } = crop.value;
  encoding.value = true;
  cropError.value = "";
  try {
    const canvas = document.createElement("canvas");
    canvas.width = canvas.height = Math.max(1, Math.min(512, Math.round(size)));
    canvas
      .getContext("2d")
      .drawImage(
        image.value,
        x,
        y,
        size,
        size,
        0,
        0,
        canvas.width,
        canvas.height,
      );
    const blob = await new Promise((resolve) =>
      canvas.toBlob(resolve, "image/png"),
    );
    if (preview.value !== source) return;
    if (!blob) throw new Error("Falha ao gerar recorte");
    if (await store.save(new File([blob], "avatar.png", { type: "image/png" })))
      close();
  } catch {
    if (preview.value === source)
      cropError.value = "Não foi possível preparar o recorte.";
  } finally {
    encoding.value = false;
  }
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
  flex: 0 0 2.5rem;
  padding: 0;
  border: 0;
  border-radius: 50%;
  background: transparent;
  cursor: pointer;
}
button.personal-avatar:focus-visible {
  outline: 2px solid white;
  outline-offset: 3px;
}
.avatar-crop {
  position: relative;
  width: fit-content;
  max-width: 100%;
  overflow: hidden;
  margin-inline: auto;
}
.avatar-crop img {
  display: block;
  max-width: 100%;
  max-height: 240px;
}
.avatar-crop__frame {
  position: absolute;
  box-sizing: border-box;
  border: 2px solid white;
  box-shadow: 0 0 0 999px rgb(0 0 0 / 55%);
  pointer-events: none;
}
.avatar-crop__controls {
  display: grid;
  gap: var(--space-3);
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
