<template>
  <AppDialog
    :open="open"
    :title="entryForm.id ? 'Propor alteração' : 'Novo registro'"
    :busy="!!store.pending"
    :error="store.error"
    @close="$emit('close')"
  >
    <form class="care-form" @submit.prevent="saveEntry">
      <fieldset class="care-form care-form-fields" :disabled="!!store.pending">
        <p class="care-muted">
          Tarefas, compromissos, alimentação, medicamentos e vacinas precisam do
          aceite dos demais responsáveis. Alterações não substituem o que já foi
          acordado até todos aceitarem. Recusas exigem motivo.
        </p>
        <fieldset v-if="members.length" class="care-form">
          <legend>Outros participantes afetados</legend>
          <label
            v-for="member in members.filter(
              (m) => m.id !== auth.user.id && m.role !== 'observador',
            )"
            :key="member.id"
            class="care-check"
          >
            <input
              type="checkbox"
              v-model="entryForm.affected_user_ids"
              :value="member.id"
            />{{ member.name }}
          </label>
          <small
            >Selecione também quem assumirá uma obrigação. O servidor verifica o
            acesso à área. Participantes de rateios são incluídos
            automaticamente.</small
          >
        </fieldset>
        <label v-if="members.length" class="care-field"
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
        ><label class="care-field"
          >Quando<input
            type="datetime-local"
            v-model="entryForm.due_at" /></label
        ><label v-if="entryForm.kind === 'event'" class="care-field"
          >Término<input
            type="datetime-local"
            v-model="entryForm.ends_at" /></label
        ><template
          v-if="['medication', 'vaccine', 'feeding'].includes(entryForm.kind)"
          ><label v-for="key in detailFields" class="care-field"
            >{{ detailLabels[key]
            }}<input v-model="entryForm.details[key]" /></label></template
        ><template v-if="entryForm.kind === 'expense'"
          ><label class="care-field"
            >Valor total (R$)<input
              type="number"
              min="0.01"
              step="0.01"
              v-model="entryForm.amount"
              required
          /></label>
          <p>
            Opcional: distribua o valor entre membros. Sem rateio informado, a
            despesa fica integralmente com quem a registrou.
          </p>
          <div
            v-for="(share, index) in entryForm.shares"
            :key="index"
            class="care-share"
          >
            <label class="care-field"
              >Responsável<select v-model="share.user_id" required>
                <option v-for="m in members" :value="m.id">{{ m.name }}</option>
              </select></label
            ><label class="care-field"
              >Parcela (R$)<input
                type="number"
                min="0.01"
                step="0.01"
                v-model="share.amount"
                required /></label
            ><AppButton
              variant="ghost"
              @click="entryForm.shares.splice(index, 1)"
              >Remover</AppButton
            >
          </div>
          <AppButton
            v-if="members.length"
            variant="outline"
            @click="
              entryForm.shares.push({ user_id: members[0].id, amount: '' })
            "
            >Adicionar parcela</AppButton
          ></template
        >
        <p v-if="store.error" role="alert" class="care-error">
          {{ store.error }}
        </p>
        <div class="care-actions">
          <AppButton variant="outline" @click="$emit('close')"
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
import { ref, computed, watch } from "vue";
import { AppDialog, AppButton } from "@/components/ui";
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
const emit = defineEmits(["close"]);
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
        title: "",
        description: "",
        affected_user_ids: [],
        assigned_user_id: null,
        due_at: "",
        ends_at: "",
        details: {},
        amount: "",
        shares: [],
      };
}
async function saveEntry() {
  if (store.pending) return;
  const e = entryForm.value;
  try {
    await store.saveEntry(props.recipientId, {
      id: e.id,
      kind: e.kind,
      title: e.title,
      description: e.description,
      assigned_user_id: e.assigned_user_id || null,
      affected_user_ids: e.affected_user_ids || [],
      due_at: e.due_at ? new Date(e.due_at).toISOString() : null,
      ends_at: e.ends_at ? new Date(e.ends_at).toISOString() : null,
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
    emit("close");
  } catch {}
}

watch(
  () => props.open,
  (open) => {
    if (open) initialize(props.entry);
  },
  { immediate: true },
);
</script>
<style scoped>
.care-form-fields {
  border: 0;
  padding: 0;
  margin: 0;
  min-width: 0;
}
</style>
