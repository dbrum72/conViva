<template>
  <CareShell
    :title="store.recipient?.name || 'Cuidados'"
    subtitle="A rotina, o histórico e a rede de apoio deste assistido."
    ><template #actions
      ><RouterLink class="btn btn--outline" :to="{ name: 'recipients' }"
        >Voltar aos assistidos</RouterLink
      ></template
    ><template v-if="store.recipient"
      ><nav class="care-tabs" aria-label="Áreas de cuidados">
        <button
          v-for="t in tabs"
          :key="t.key"
          :class="{ active: tab === t.key }"
          @click="tab = t.key"
        >
          {{ t.label }}
        </button>
      </nav>
      <template v-if="!['documents', 'access'].includes(tab)"
        ><div class="care-toolbar">
          <h2>{{ tabs.find((t) => t.key === tab)?.label }}</h2>
          <AppButton variant="primary" v-if="canEdit" @click="openEntry()"
            >Adicionar registro</AppButton
          >
        </div>
        <AppCard v-for="e in filteredEntries" :key="e.id"
          ><div class="care-list-row">
            <div>
              <span class="care-pill">{{ entryKinds[e.kind] }}</span>
              <h3>{{ e.title }}</h3>
              <p class="care-muted">
                {{ dateTime(e.due_at) }} · {{ e.author?.name }}
              </p>
            </div>
            <span
              :class="[
                'care-pill',
                e.status === 'completed' ? 'care-done' : '',
              ]"
              >{{ statusLabels[e.status] || e.status }}</span
            >
          </div>
          <p class="care-pre">{{ e.description }}</p>
          <dl v-if="e.details" class="care-details">
            <template v-for="(value, key) in e.details" :key="key"
              ><dt>{{ detailLabels[key] }}</dt>
              <dd>{{ value }}</dd></template
            >
          </dl>
          <template v-if="e.kind === 'expense'"
            ><strong>{{ money(e.amount_cents) }}</strong>
            <div
              v-for="share in e.shares"
              :key="share.id"
              class="care-list-row"
            >
              <span
                >{{ share.user?.name }} · {{ money(share.amount_cents) }} ·
                {{ share.paid_at ? "Pago" : "Pendente" }}</span
              ><AppButton
                variant="action"
                v-if="
                  !share.paid_at &&
                  canEdit &&
                  share.user_id === auth.user.id &&
                  ['pending', 'completed'].includes(e.status) &&
                  !e.proposals?.some((p) => p.status === 'pending')
                "
                @click="store.pay(id, e.id, share.id).catch(() => {})"
                >Registrar pagamento</AppButton
              >
            </div></template
          >
          <AppButton
            variant="outline"
            v-if="
              canEdit &&
              e.assigned_user_id === auth.user.id &&
              e.status === 'pending' &&
              !e.proposals?.some((p) => p.status === 'pending') &&
              !store.entries.some((r) => r.related_entry_id === e.id)
            "
            @click="recordExecution(e)"
            >Registrar minha execução</AppButton
          >
          <CareDecisions
            :entry="e"
            :user-id="auth.user.id"
            :can-edit="canEdit"
            :busy="!!store.pending"
            @decide="
              (proposal, data) =>
                store.decide(id, e.id, proposal, data).catch(() => {})
            "
            @withdraw="
              (proposal) => store.withdraw(id, e.id, proposal).catch(() => {})
            "
          />
          <div
            v-if="
              canEdit &&
              e.created_by === auth.user.id &&
              e.status !== 'cancelled' &&
              !e.related_entry_id &&
              !e.proposals?.some((p) => p.status === 'pending')
            "
            class="care-actions"
          >
            <AppButton
              variant="action"
              v-if="e.status === 'pending' && e.kind !== 'expense'"
              @click="store.complete(id, e.id).catch(() => {})"
              >Marcar como concluído</AppButton
            ><AppButton variant="ghost" @click="openEntry(e)">Editar</AppButton
            ><AppButton variant="danger" @click="removeEntry(e)"
              >Solicitar cancelamento</AppButton
            >
          </div></AppCard
        >
        <p v-if="!filteredEntries.length" class="care-empty">
          Nenhum registro nesta área. Use “Adicionar registro” para começar.
        </p></template
      >
      <template v-if="tab === 'documents'"
        ><AppCard title="Documentos privados"
          ><label v-if="canEdit" class="care-field"
            >Adicionar documento (até 20 MB)<input
              type="file"
              accept=".pdf,.png,.jpg,.jpeg,.webp,.txt,.docx,.xlsx"
              @change="upload"
          /></label>
          <div v-for="d in store.documents" :key="d.id" class="care-list-row">
            <span>{{ d.filename }}</span>
            <div class="care-actions">
              <AppButton
                variant="outline"
                @click="store.download(id, d).catch(() => {})"
                >Baixar</AppButton
              ><AppButton
                variant="danger"
                v-if="canEdit && d.created_by === auth.user.id"
                @click="removeDocument(d)"
                >Excluir</AppButton
              >
            </div>
          </div>
          <p v-if="!store.documents.length" class="care-empty">
            Nenhum documento compartilhado.
          </p></AppCard
        ></template
      >
      <template v-if="tab === 'access'"
        ><AppCard title="Acesso por assistido"
          ><p>
            Responsáveis vinculados participam das decisões do assistido. Para
            cuidadores e observadores, conceda somente as áreas necessárias.
          </p>
          <form class="care-form" @submit.prevent="grant">
            <label class="care-field"
              >Membro<select v-model="accessForm.user_id" required>
                <option value="">Selecione</option>
                <option v-for="m in members" :value="m.id">
                  {{ m.name }} · {{ m.email }}
                </option>
              </select></label
            >
            <fieldset>
              <legend>Áreas disponíveis</legend>
              <label v-for="(label, key) in areas" class="care-check"
                ><input
                  type="checkbox"
                  :value="key"
                  v-model="accessForm.areas"
                />{{ label }}</label
              >
            </fieldset>
            <label class="care-check"
              ><input type="checkbox" v-model="accessForm.can_edit" />Permitir
              edição (observadores continuam somente leitura)</label
            ><label class="care-field"
              >Expira em (opcional)<input
                type="datetime-local"
                v-model="accessForm.expires_at" /></label
            ><AppButton variant="action" type="submit">Salvar acesso</AppButton>
          </form>
          <div v-for="a in store.accesses" :key="a.id" class="care-list-row">
            <div>
              <strong>{{ a.user.name }}</strong>
              <p>
                {{ a.areas.map((k) => areas[k]).join(", ") }} ·
                {{ a.can_edit ? "Edição" : "Leitura" }}
              </p>
              <p v-if="a.expires_at">Até {{ dateTime(a.expires_at) }}</p>
            </div>
            <AppButton
              variant="action"
              v-if="
                !members.some(
                  (m) => m.id === a.user_id && m.role === 'responsavel',
                )
              "
              @click="store.revoke(id, a.user_id).catch(() => {})"
              >Revogar</AppButton
            >
          </div></AppCard
        ></template
      >
    </template>
    <dialog ref="entryDialog" class="care-dialog">
      <form @submit.prevent="saveEntry">
        <h2>{{ entryForm.id ? "Propor alteração" : "Novo registro" }}</h2>
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
          <AppButton variant="outline" @click="entryDialog.close()"
            >Cancelar</AppButton
          ><AppButton variant="action" type="submit" :loading="!!store.pending"
            >Salvar registro</AppButton
          >
        </div>
      </form>
    </dialog></CareShell
  >
</template>
<script setup>
import { ref, computed, watch } from "vue";
import { useRoute } from "vue-router";
import CareDecisions from "@/components/care/CareDecisions.vue";
import CareShell from "@/components/care/CareShell.vue";
import { AppCard, AppButton } from "@/components/ui";
import { useCareStore } from "@/state/care";
import { useAuthStore } from "@/state/auth";
import { useOrganizationMembersStore } from "@/state/organization-members";
import { entryKinds, areas, areaFor, dateTime, money } from "@/utils/care";
const route = useRoute(),
  store = useCareStore(),
  auth = useAuthStore(),
  team = useOrganizationMembersStore(),
  tab = ref("routine"),
  entryDialog = ref(),
  entryForm = ref({ details: {}, shares: [] }),
  accessForm = ref({
    user_id: "",
    areas: ["routine"],
    can_edit: false,
    expires_at: "",
  });
const id = computed(() => route.params.id),
  members = computed(() => team.activeMembers);
const tabs = computed(() => [
  ...Object.entries(areas)
    .filter(([key]) => store.recipient?.capabilities[key]?.view)
    .map(([key, label]) => ({ key, label })),
  ...(store.recipient?.can_manage_access
    ? [{ key: "access", label: "Permissões" }]
    : []),
]);
const canEdit = computed(() => store.recipient?.capabilities[tab.value]?.edit);
const availableKinds = computed(() =>
  Object.keys(entryKinds).filter((k) => areaFor(k) === tab.value),
);
const filteredEntries = computed(() =>
  store.entries.filter((e) => areaFor(e.kind) === tab.value),
);
const statusLabels = {
  pending: "Confirmado",
  completed: "Concluído",
  awaiting_approval: "Aguardando aceite",
  rejected: "Proposta recusada",
  withdrawn: "Proposta retirada",
  cancelled: "Cancelado",
};
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
function openEntry(e) {
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
        kind: availableKinds.value[0],
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
  entryDialog.value.showModal();
}
async function saveEntry() {
  const e = entryForm.value;
  try {
    await store.saveEntry(id.value, {
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
    entryDialog.value.close();
  } catch {}
}
async function grant() {
  try {
    await store.grant(id.value, {
      ...accessForm.value,
      expires_at: accessForm.value.expires_at
        ? new Date(accessForm.value.expires_at).toISOString()
        : null,
    });
  } catch {}
}
function recordExecution(e) {
  const description = window.prompt("Descreva o cuidado realizado (opcional):");
  if (description !== null)
    store.execute(id.value, e.id, description).catch(() => {});
}
function removeEntry(e) {
  if (
    window.confirm(
      "Cancelar este registro? Se houver participantes afetados, o cancelamento dependerá de aceite. O histórico será preservado.",
    )
  )
    store.removeEntry(id.value, e.id).catch(() => {});
}
function removeDocument(d) {
  if (window.confirm("Excluir " + d.filename + "?"))
    store.removeDocument(id.value, d.id).catch(() => {});
}
async function upload(e) {
  const f = e.target.files[0];
  if (f) await store.upload(id.value, f).catch(() => {});
  e.target.value = "";
}
watch(
  id,
  async (value) => {
    tab.value = "routine";
    await store.loadRecipient(value).catch(() => {});
    tab.value = tabs.value[0]?.key || "routine";
    if (auth.hasPermission("organization-members.view"))
      await team.fetchMembers().catch(() => {});
  },
  { immediate: true },
);
</script>
