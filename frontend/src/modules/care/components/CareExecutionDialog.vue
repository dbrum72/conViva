<template>
  <AppDialog
    :open="!!entry"
    :title="health ? 'Registrar administração' : 'Registrar minha execução'"
    :busy="!!store.pending"
    :error="store.error"
    @close="$emit('close')"
  >
    <AppForm class="care-form" @submit.prevent="submit">
      <p>{{ entry?.title }}</p>
      <p>O registro ficará vinculado ao cuidado, com sua autoria.</p>
      <AppSelect
        v-if="health"
        id="execution-outcome"
        v-model="outcome"
        label="Resultado"
        :options="outcomes"
        required
      />
      <AppInput
        label="Quando ocorreu (horário deste dispositivo)"
        :id="fieldId + '-1'"
        type="datetime-local"
        v-model="occurredAt"
        required
        :disabled="!!store.pending"
      />
      <AppTextarea
        id="execution-description"
        v-model="description"
        label="Descreva o cuidado realizado (opcional)"
        :disabled="!!store.pending"
        :maxlength="10000"
      />
      <AppDate
        v-if="entry?.kind === 'vaccine' && outcome === 'administered'"
        label="Próxima data informada (opcional)"
        :id="fieldId + '-2'"
        v-model="nextDate"
        :disabled="!!store.pending"
        :show-icon="false"
      />
      <div class="care-actions">
        <AppButton
          variant="cancel"
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
    </AppForm>
  </AppDialog>
</template>
<script setup>
import { useId } from "vue";
const fieldId = useId();
import { computed, ref, watch } from "vue";
import { AppDialog, AppButton } from "@/components/ui";
import {
  AppForm,
  AppSelect,
  AppTextarea,
  AppInput,
  AppDate,
} from "@/components/forms";
import { useCareStore } from "../state/care.js";
const props = defineProps({
  entry: Object,
  recipientId: [Number, String],
  occurrence: Boolean,
  refreshEntries: { type: Boolean, default: true },
});
const emit = defineEmits(["close", "saved"]);
const health = computed(
  () =>
    props.occurrence && ["medication", "vaccine"].includes(props.entry?.kind),
);
const outcomes = [
  { value: "administered", label: "Administrado" },
  { value: "not_administered", label: "Não administrado" },
];
const outcome = ref(null);
const nextDate = ref("");
const store = useCareStore(),
  description = ref(""),
  occurredAt = ref("");
watch(
  () => props.entry,
  () => {
    description.value = "";
    outcome.value = null;
    nextDate.value = "";
    const now = new Date();
    occurredAt.value = new Date(now.getTime() - now.getTimezoneOffset() * 60000)
      .toISOString()
      .slice(0, 16);
  },
  { immediate: true },
);
async function submit() {
  if (store.pending || !props.entry) return;
  try {
    const date = new Date(occurredAt.value).toISOString();
    const saved = props.occurrence
      ? await store.executeOccurrence(props.entry.id, {
          occurred_at: date,
          description: description.value,
          ...(health.value ? { outcome: outcome.value } : {}),
          ...(props.entry.kind === "vaccine" &&
          outcome.value === "administered" &&
          nextDate.value
            ? { next_date: nextDate.value }
            : {}),
        })
      : await store.execute(
          props.recipientId,
          props.entry.id,
          description.value,
          date,
          ...(props.refreshEntries ? [] : [{ refreshEntries: false }]),
        );
    if (saved === false) return;
    emit("close");
    emit("saved");
  } catch {
    /* Error remains visible in the dialog. */
  }
}
</script>
