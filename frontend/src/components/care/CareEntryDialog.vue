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
    <form class="care-form" @submit.prevent="saveEntry">
      <fieldset class="care-form care-form-fields" :disabled="!!store.pending">
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
        <fieldset v-if="members.length" class="care-form">
          <legend>Outros participantes afetados</legend>
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
        </fieldset>
        <label
          v-if="members.length && entryForm.kind !== 'expense'"
          class="care-field"
          >Prestador designado (opcional)<select
            v-model="entryForm.assigned_user_id"
          >
            <option :value="null">Sem designação</option>
            <option
              v-for="member in members.filter((m) => m.role !== 'observador')"
              :key="member.id"
              :value="member.id"
            >
              {{ member.name }}
            </option>
          </select></label
        >
        <label class="care-field"
          >Tipo<select v-model="entryForm.kind" :disabled="!!entryForm.id">
            <option v-for="key in availableKinds" :value="key">
              {{ entryKinds[key] }}
            </option>
          </select></label
        ><label class="care-field"
          >Título<input
            v-model="entryForm.title"
            required
            maxlength="200" /></label
        ><label class="care-field"
          >Descrição<textarea
            v-model="entryForm.description"
            rows="3"
          ></textarea></label
        ><CareScheduleFields
          v-if="['event', 'task', 'feeding'].includes(entryForm.kind)"
          v-model="entryForm.schedule"
          :timezone="auth.organization?.timezone || 'America/Sao_Paulo'"
        />
        <AppCheckbox
          id="care-publish-to-agenda"
          v-model="entryForm.publish_to_agenda"
          label="Publicar na agenda"
          hint="Visível na agenda para todos com permissão nesta área, após os aceites necessários. Informe uma data ou programação."
          :disabled="!!store.pending"
        />
        <label v-if="!entryForm.schedule" class="care-field"
          >Quando (horário deste dispositivo)<input
            type="datetime-local"
            v-model="entryForm.due_at"
            :required="entryForm.publish_to_agenda" /></label
        ><label
          v-if="entryForm.kind === 'event' && !entryForm.schedule"
          class="care-field"
          >Término<input
            type="datetime-local"
            v-model="entryForm.ends_at" /></label
        ><template
          v-if="['medication', 'vaccine', 'feeding'].includes(entryForm.kind)"
          ><label v-for="key in detailFields" class="care-field"
            >{{ detailLabels[key]
            }}<input v-model="entryForm.details[key]" /></label></template
        ><template v-if="entryForm.kind === 'expense'"
          ><AppCurrency
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
              <label class="care-field"
                >Responsável<select v-model="share.user_id" required>
                  <option v-for="m in members" :value="m.id">
                    {{ m.name }}
                  </option>
                </select></label
              ><AppCurrency
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
      </fieldset>
    </form>
  </AppDialog>
</template>
<script setup>
import CareScheduleFields from "./CareScheduleFields.vue";
import { ref, computed, watch } from "vue";
import { AppDialog, AppButton } from "@/components/ui";
import { AppCurrency, AppCheckbox } from "@/components/forms";
import { useCareStore } from "@/state/care";
import { useAuthStore } from "@/state/auth";
import { entryKinds } from "@/utils/care";
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
const detailLabels = {
  dose: "Dose informada",
  frequency: "Frequência informada",
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
        affected_user_ids: [...(e.affected_user_ids || [])],
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
      schedule: e.schedule || null,
      details: e.details,
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
  () => {
    if (!["event", "task", "feeding"].includes(entryForm.value.kind))
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
  gap: 1rem;
  margin-block: 1.25rem 1.5rem;
}
.care-share-add {
  justify-self: start;
  margin-top: 0.25rem;
}

.care-form-fields {
  border: 0;
  padding: 0;
  margin: 0;
  min-width: 0;
}
</style>
