<template>
  <AppDialog
    :open="!!entry"
    title="Registrar minha execução"
    :busy="!!store.pending"
    :error="store.error"
    @close="$emit('close')"
  >
    <form class="care-form" @submit.prevent="submit">
      <p>{{ entry?.title }}</p>
      <p>O registro ficará vinculado ao cuidado, com sua autoria.</p>
      <AppTextarea
        id="execution-description"
        v-model="description"
        label="Descreva o cuidado realizado (opcional)"
        :disabled="!!store.pending"
        maxlength="10000"
      />
      <div class="care-actions">
        <AppButton
          variant="ghost"
          :disabled="!!store.pending"
          @click="$emit('close')"
          >Cancelar</AppButton
        >
        <AppButton
          type="submit"
          variant="primary"
          :loading="!!store.pending"
          :disabled="!!store.pending"
          >Registrar execução</AppButton
        >
      </div>
    </form>
  </AppDialog>
</template>
<script setup>
import { ref, watch } from "vue";
import { AppDialog, AppButton } from "@/components/ui";
import { AppTextarea } from "@/components/forms";
import { useCareStore } from "@/state/care";
const props = defineProps({ entry: Object, recipientId: [Number, String] });
const emit = defineEmits(["close"]);
const store = useCareStore(),
  description = ref("");
watch(
  () => props.entry,
  () => {
    description.value = "";
  },
);
async function submit() {
  if (store.pending || !props.entry) return;
  try {
    await store.execute(props.recipientId, props.entry.id, description.value);
    emit("close");
  } catch {
    /* Error remains visible in the dialog. */
  }
}
</script>
