<template>
  <AppDialog
    :open="open"
    :title="
      entryForm.id
        ? 'Propor alteração'
        : entryForm.kind === 'expense'
          ? 'Nova despesa'
          : 'Novo registro'
    "
    :busy="!!store.pending"
    :error="store.error"
    @close="$emit('close')"
  >
    <AppForm class="care-form" @submit.prevent="saveEntry">
      <AppFieldset
        class="care-form care-form-fields"
        :disabled="!!store.pending"
      >
        <p v-if="entryForm.kind === 'expense'" class="care-muted">
          Informe o valor e o rateio. Participantes afetados precisam aceitar a
          proposta antes do pagamento. Sem rateio, a despesa fica com quem a
          registrou.
        </p>
        <p v-else class="care-muted">
          Tarefas, compromissos, alimentação, medicamentos e vacinas precisam do
          aceite dos demais responsáveis. Alterações não substituem o que já foi
          acordado até todos aceitarem. Recusas exigem motivo.
        </p>
        <AppFieldset v-if="members.length" class="care-form">
          <template #legend>Outros participantes afetados</template>
          <AppCheckbox
            v-for="member in members.filter(
              (m) => m.id !== auth.user.id && m.role !== 'observador',
            )"
            :key="member.id"
            :id="`care-affected-member-${member.id}`"
            :label="member.name"
            :model-value="
              entryForm.affected_user_ids?.includes(member.id) || false
            "
            :disabled="!!store.pending"
            @update:model-value="setAffectedMember(member.id, $event)"
          />
          <small
            >Selecione também quem assumirá uma obrigação. O servidor verifica o
            acesso à área. Participantes de rateios são incluídos
            automaticamente.</small
          >
        </AppFieldset>
        <AppSelect
          v-if="members.length && entryForm.kind !== 'expense'"
          id="care-entry-assignee"
          v-model="assignee"
          label="Prestador designado (opcional)"
          :options="assigneeOptions"
        />
        <AppSelect
          id="care-entry-kind"
          v-model="entryForm.kind"
          label="Tipo"
          :options="kindOptions"
          :disabled="!!entryForm.id"
        />
        <AppInput
          label="Título"
          :id="fieldId + '-1'"
          v-model="entryForm.title"
          required
          :maxlength="200"
        /><AppTextarea
          label="Descrição"
          :id="fieldId + '-2'"
          v-model="entryForm.description"
          :rows="3"
        /><CareScheduleFields
          v-if="scheduleKinds.includes(entryForm.kind)"
          v-model="entryForm.schedule"
          :kind="entryForm.kind"
          :timezone="auth.organization?.timezone || 'America/Sao_Paulo'"
        />
        <AppCheckbox
          id="care-publish-to-agenda"
          v-model="entryForm.publish_to_agenda"
          label="Publicar na agenda"
          hint="Visível na agenda para todos com permissão nesta área, após os aceites necessários. Informe uma data ou programação."
          :disabled="!!store.pending"
        />
        <AppInput
          v-if="!entryForm.schedule"
          label="Quando (horário deste dispositivo)"
          :id="fieldId + '-3'"
          type="datetime-local"
          v-model="entryForm.due_at"
          :required="entryForm.publish_to_agenda"
        /><AppInput
          v-if="entryForm.kind === 'event' && !entryForm.schedule"
          label="Término"
          :id="fieldId + '-4'"
          type="datetime-local"
          v-model="entryForm.ends_at"
        /><template
          v-if="['medication', 'vaccine', 'feeding'].includes(entryForm.kind)"
          ><AppInput
            v-for="key in detailFields"
            :label="detailLabels[key]"
            :id="fieldId + '-5' + '-' + key"
            v-model="entryForm.details[key]" /></template
        ><template v-if="entryForm.kind === 'expense'">
          <AppInput
            id="care-entry-expense-category"
            label="Categoria (opcional)"
            v-model="entryForm.details.expense_category"
            :maxlength="100"
          />
          <AppCurrency
            id="care-entry-amount"
            name="amount"
            label="Valor total (R$)"
            v-model="entryForm.amount"
            shift-decimal
            :min="0.01"
            required
          />
          <div class="care-share-group">
            <div
              v-for="(share, index) in entryForm.shares"
              :key="index"
              class="care-share"
            >
              <AppSelect
                :id="`care-entry-share-user-${index}`"
                v-model="share.user_id"
                label="Responsável"
                :options="members"
                option-label="name"
                option-value="id"
                required
              />
              <AppCurrency
                :id="`care-entry-share-${index}`"
                :name="`shares[${index}][amount]`"
                label="Parcela (R$)"
                v-model="share.amount"
                shift-decimal
                :min="0.01"
                required
              /><AppButton
                variant="ghost"
                @click="entryForm.shares.splice(index, 1)"
                >Remover</AppButton
              >
            </div>
            <AppButton
              v-if="members.length"
              class="care-share-add"
              size="sm"
              variant="outline"
              @click="
                entryForm.shares.push({ user_id: members[0].id, amount: null })
              "
              >Adicionar parcela</AppButton
            >
          </div></template
        >
        <p v-if="store.error" role="alert" class="care-error">
          {{ store.error }}
        </p>
        <div class="care-actions">
          <AppButton variant="cancel" @click="$emit('close')"
            >Cancelar</AppButton
          ><AppButton variant="action" type="submit" :loading="!!store.pending"
            >Salvar registro</AppButton
          >
        </div>
      </AppFieldset>
    </AppForm>
  </AppDialog>
</template>
<script setup>
import { useId } from "vue";
const fieldId = useId();
import CareScheduleFields from "./CareScheduleFields.vue";
import { ref, computed, watch } from "vue";
import { AppDialog, AppButton } from "@/components/ui";
import {
  AppForm,
  AppFieldset,
  AppCurrency,
  AppCheckbox,
  AppSelect,
  AppInput,
  AppTextarea,
} from "@/components/forms";
import { useCareStore } from "../state/care.js";
import { useAuthStore } from "@/modules/auth";
import { entryKinds } from "../utils/care.js";
const props = defineProps({
  open: Boolean,
  entry: Object,
  recipientId: [String, Number],
  members: Array,
  availableKinds: Array,
});
const emit = defineEmits(["close", "saved"]);
const store = useCareStore(),
  auth = useAuthStore();
const entryForm = ref({ details: {}, shares: [] });
const assignee = computed({
  get: () => entryForm.value.assigned_user_id ?? "",
  set: (value) => {
    entryForm.value.assigned_user_id = value === "" ? null : value;
  },
});
const assigneeOptions = computed(() => [
  { label: "Sem designação", value: "" },
  ...props.members
    .filter((member) => member.role !== "observador")
    .map((member) => ({ label: member.name, value: member.id })),
]);
const kindOptions = computed(() =>
  props.availableKinds.map((kind) => ({
    label: entryKinds[kind],
    value: kind,
  })),
);
const scheduleKinds = ["event", "task", "feeding", "medication", "vaccine"];
const detailLabels = {
  dose: "Dose informada",
  frequency: "Frequência informada (texto de referência)",
  route: "Via de administração",
  food: "Alimento",
  quantity: "Quantidade",
  provider: "Profissional ou local",
};
const detailFields = computed(() =>
  entryForm.value.kind === "feeding"
    ? ["food", "quantity"]
    : entryForm.value.kind === "vaccine"
      ? ["provider"]
      : ["dose", "frequency", "route"],
);
function localDate(value) {
  if (!value) return "";
  const d = new Date(value);
  return new Date(d.getTime() - d.getTimezoneOffset() * 60000)
    .toISOString()
    .slice(0, 16);
}
function initialize(e) {
  store.error = "";
  entryForm.value = e
    ? {
        ...e,
        publish_to_agenda: !!e.publish_to_agenda,
        schedule: e.schedule ? JSON.parse(JSON.stringify(e.schedule)) : null,
        details: { ...e.details },
        affected_user_ids: [...(e.responsibility_user_ids || [])],
        due_at: localDate(e.due_at),
        ends_at: localDate(e.ends_at),
        amount: (e.amount_cents || 0) / 100,
        shares: (e.shares || []).map((s) => ({
          user_id: s.user_id,
          amount: s.amount_cents / 100,
        })),
      }
    : {
        kind: props.availableKinds[0],
        publish_to_agenda: false,
        title: "",
        description: "",
        affected_user_ids: [],
        assigned_user_id: null,
        due_at: "",
        ends_at: "",
        details: {},
        amount: null,
        shares: [],
      };
  if (entryForm.value.schedule?.times) {
    entryForm.value.schedule.times = entryForm.value.schedule.times.filter(
      (time) => time !== entryForm.value.schedule.local_start.slice(11),
    );
  }
}
function setAffectedMember(id, checked) {
  const selected = entryForm.value.affected_user_ids || [];
  entryForm.value.affected_user_ids = checked
    ? [...new Set([...selected, id])]
    : selected.filter((memberId) => memberId !== id);
}
async function saveEntry() {
  if (store.pending) return;
  const e = entryForm.value;
  try {
    await store.saveEntry(props.recipientId, {
      id: e.id,
      ...(e.id ? { revision: e.revision } : {}),
      kind: e.kind,
      publish_to_agenda: e.publish_to_agenda,
      title: e.title,
      description: e.description,
      assigned_user_id: e.assigned_user_id || null,
      affected_user_ids: e.affected_user_ids || [],
      due_at: e.due_at ? new Date(e.due_at).toISOString() : null,
      ends_at: e.ends_at ? new Date(e.ends_at).toISOString() : null,
      schedule: e.schedule && {
        ...e.schedule,
        until:
          e.schedule.frequency === "once"
            ? e.schedule.local_start.slice(0, 10)
            : e.schedule.until,
        ...(e.kind === "medication" && {
          times: [
            e.schedule.local_start.slice(11),
            ...(e.schedule.frequency === "once" ? [] : e.schedule.times || []),
          ],
        }),
      },
      details:
        e.kind === "expense"
          ? e.details
          : Object.fromEntries(
              Object.entries(e.details).filter(
                ([key]) => key !== "expense_category",
              ),
            ),
      amount_cents:
        e.kind === "expense" ? Math.round(Number(e.amount) * 100) : null,
      shares:
        e.kind === "expense"
          ? e.shares.map((s) => ({
              user_id: s.user_id,
              amount_cents: Math.round(Number(s.amount) * 100),
            }))
          : [],
    });
    emit("saved");
    emit("close");
  } catch {}
}

watch(
  () => entryForm.value.kind,
  (kind, previous) => {
    if (
      !scheduleKinds.includes(kind) ||
      (!props.entry &&
        [kind, previous].some((value) =>
          ["medication", "vaccine"].includes(value),
        ))
    )
      entryForm.value.schedule = null;
  },
);
watch(
  () => auth.currentTenant,
  () => emit("close"),
);
watch(
  () => props.open,
  (open) => {
    if (open) initialize(props.entry);
  },
  { immediate: true },
);
</script>
<style scoped>
.care-share-group {
  display: grid;
  gap: var(--space-4);
}
.care-share-add {
  justify-self: start;
}

.care-form-fields {
  border: 0;
  padding: 0;
  margin: 0;
  min-width: 0;
}
</style>
