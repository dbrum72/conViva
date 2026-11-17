<template>
  <AppDialog
    :open="!!kind"
    :title="title"
    :busy="!!store.pending"
    :error="store.error"
    @close="$emit('close')"
  >
    <AppForm class="care-form" @submit.prevent="submit">
      <AppInput
        label="Quando ocorreu (horário deste dispositivo)"
        :id="fieldId + '-1'"
        v-model="occurredAt"
        type="datetime-local"
        required
        :disabled="!!store.pending"
      />
      <AppTextarea
        id="care-fact-description"
        v-model="description"
        :label="kind === 'observation' ? 'Observação' : 'Detalhes (opcional)'"
        :required="kind === 'observation'"
        :disabled="!!store.pending"
        :maxlength="10000"
      />
      <div class="care-actions">
        <AppButton
          variant="cancel"
          :disabled="!!store.pending"
          @click="$emit('close')"
        >
          Cancelar
        </AppButton>
        <AppButton
          type="submit"
          :loading="!!store.pending"
          :disabled="!!store.pending"
        >
          Salvar registro
        </AppButton>
      </div>
    </AppForm>
  </AppDialog>
</template>

<script setup>
import { useId } from "vue";
const fieldId = useId();
import { ref, watch } from "vue";
import { AppForm, AppTextarea, AppInput } from "@/components/forms";
import { AppButton, AppDialog } from "@/components/ui";
import { useCareStore } from "../state/care.js";

const props = defineProps({ kind: String, title: String, recipientId: Number });
const emit = defineEmits(["close", "saved"]);
const store = useCareStore();
const occurredAt = ref("");
const description = ref("");
let requestId;
let submitted;

watch(
  () => props.kind,
  (kind) => {
    if (!kind) return;
    requestId = crypto.randomUUID();
    submitted = null;
    description.value = "";
    const now = new Date();
    occurredAt.value = new Date(now.getTime() - now.getTimezoneOffset() * 60000)
      .toISOString()
      .slice(0, 16);
  },
);

async function submit() {
  if (store.pending || !props.kind) return;
  const fields = {
    kind: props.kind,
    occurred_at: new Date(occurredAt.value).toISOString(),
    description: description.value.trim() || null,
  };
  const fingerprint = JSON.stringify(fields);
  if (submitted && submitted !== fingerprint) requestId = crypto.randomUUID();
  submitted = fingerprint;
  try {
    if (
      await store.recordFact(props.recipientId, {
        ...fields,
        request_id: requestId,
      })
    ) {
      emit("close");
      emit("saved");
    }
  } catch {
    // The dialog displays the server error and keeps the entered values.
  }
}
</script>
