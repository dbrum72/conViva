<template>
  <CareShell
    :title="store.recipient?.name || 'Cuidados'"
    subtitle="A rotina, o histórico e a rede de apoio deste assistido."
    @retry="store.loadRecipient(id).catch(() => {})"
    ><template #actions
      ><RouterLink class="btn btn--outline" :to="{ name: 'recipients' }"
        >Voltar aos assistidos</RouterLink
      ></template
    ><template v-if="store.recipient">
      <AppTabs
        v-model="tab"
        class="care-tabs"
        aria-label="Áreas de cuidados"
        :items="tabs.map(({ key, label }) => ({ value: key, label }))"
      />
      <template v-if="!['documents', 'access'].includes(tab)"
        ><div class="care-toolbar">
          <h2>{{ tabs.find((t) => t.key === tab)?.label }}</h2>
          <AppButton variant="modal" v-if="canEdit" @click="openEntry()"
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
                e.status === 'pending' ? 'care-confirmed' : '',
                e.status === 'cancelled' ? 'care-cancelled' : '',
              ]"
              >{{
                e.related_entry_id && e.status === "completed"
                  ? e.details?.outcome === "not_administered"
                    ? "Não administrada"
                    : areaFor(e.kind) === "health"
                      ? e.kind === "vaccine"
                        ? "Aplicada"
                        : "Administrada"
                      : "Executado"
                  : statusLabels[e.status] || e.status
              }}</span
            >
          </div>
          <p
            v-if="e.status === 'completed' && !e.related_entry_id"
            class="care-muted"
          >
            Cadastro encerrado em {{ dateTime(e.completed_at) }}. As execuções
            são registradas separadamente; o encerramento não cancela a
            programação.
          </p>
          <p v-if="e.related_entry_id" class="care-muted">
            Registrado em {{ dateTime(e.created_at) }}.
          </p>
          <p class="care-pre">{{ e.description }}</p>
          <dl v-if="e.details" class="care-details">
            <template
              v-for="(value, key) in visibleDetails(e.details)"
              :key="key"
              ><dt>{{ detailLabels[key] }}</dt>
              <dd>{{ value }}</dd></template
            >
          </dl>
          <p
            v-if="e.schedule && areaFor(e.kind) === 'health'"
            class="care-muted"
          >
            {{ formatSchedule(e.schedule) }}
          </p>
          <p v-if="e.execution_block" role="status">{{ e.execution_block }}</p>
          <AppButton
            variant="modal"
            v-if="e.can_execute"
            @click="recordExecution(e)"
            >Registrar minha execução</AppButton
          >
          <RouterLink
            v-if="
              e.has_occurrences &&
              (e.publish_to_agenda || areaFor(e.kind) === 'health')
            "
            :to="{
              name: 'agenda',
              query:
                areaFor(e.kind) === 'health'
                  ? { health_entry: e.id, date: e.due_at }
                  : {},
            }"
            >Consultar ocorrências</RouterLink
          >
          <CareDecisions :entry="e" :user-id="auth.user.id" />
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
              >Encerrar cadastro</AppButton
            ><AppButton variant="modal" @click="openEntry(e)">Editar</AppButton
            ><AppButton variant="danger" @click="removeEntry(e)"
              >Solicitar cancelamento</AppButton
            >
          </div></AppCard
        >
        <p
          v-if="!store.pending && !store.error && !filteredEntries.length"
          class="care-empty"
        >
          Nenhum registro nesta área.
          {{
            canEdit
              ? "Use “Adicionar registro” para começar."
              : "Os cuidados compartilhados aparecerão aqui."
          }}
        </p></template
      >
      <template v-if="tab === 'documents'"
        ><AppCard title="Documentos privados"
          ><AppFileUpload
              v-if="canEdit"
              id="recipient-document"
              label="Adicionar documento (até 20 MB)"
              accept=".pdf,.png,.jpg,.jpeg,.webp,.txt,.docx,.xlsx"
              :disabled="!!store.pending"
              @select="upload"
          />
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
          <CareAccessForm
            :current-user-id="auth.user?.id"
            :recipient-id="id"
            :members="members"
            :accesses="store.accesses"
            :ready="store.accessesLoaded"
            :busy="!!store.pending"
            @save="grant"
          />
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
    <CareEntryDialog
      :open="entryOpen"
      :entry="selectedEntry"
      :recipient-id="id"
      :members="members"
      :available-kinds="availableKinds"
      @close="entryOpen = false"
    />
    <CareExecutionDialog
      :entry="executionEntry"
      :recipient-id="id"
      @close="executionEntry = null"
    />
    <AppConfirmDialog
      :open="!!confirmation"
      :title="confirmation?.title || 'Confirmar'"
      :message="confirmation?.message || ''"
      :loading="!!store.pending"
      :error="store.error"
      @cancel="confirmation = null"
      @confirm="confirmAction"
    />
  </CareShell>
</template>
<script setup>
import { ref, computed, watch } from "vue";
import { useRoute } from "vue-router";
import CareAccessForm from "@/components/care/CareAccessForm.vue";
import CareDecisions from "@/components/care/CareDecisions.vue";
import CareShell from "@/components/care/CareShell.vue";
import CareEntryDialog from "@/components/care/CareEntryDialog.vue";
import CareExecutionDialog from "@/components/care/CareExecutionDialog.vue";
import { AppCard, AppButton, AppConfirmDialog, AppTabs } from "@/components/ui";
import { AppFileUpload } from "@/components/forms";
import { useCareStore } from "@/state/care";
import { useAuthStore } from "@/modules/auth";
import { useOrganizationMembersStore } from "@/modules/groups";
import {
  entryKinds,
  areas,
  areaFor,
  dateTime,
  formatSchedule,
} from "@/utils/care";
const route = useRoute(),
  store = useCareStore(),
  auth = useAuthStore(),
  team = useOrganizationMembersStore(),
  tab = ref("routine"),
  entryOpen = ref(false),
  selectedEntry = ref(null),
  executionEntry = ref(null),
  confirmation = ref(null);
const id = computed(() => route.params.id),
  members = computed(() => team.activeMembers);
const tabs = computed(() => [
  ...Object.entries(areas)
    .filter(
      ([key]) => key !== "finance" && store.recipient?.capabilities[key]?.view,
    )
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
  completed: "Cadastro encerrado",
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
  next_date: "Próxima data informada",
  correction_reason: "Justificativa da correção",
};
function visibleDetails(details) {
  return Object.fromEntries(
    Object.entries(details).filter(
      ([key, value]) => detailLabels[key] && value,
    ),
  );
}
function openEntry(entry = null) {
  selectedEntry.value = entry;
  entryOpen.value = true;
}
async function grant(data) {
  if (!store.accessesLoaded || store.pending) return;
  try {
    await store.grant(id.value, data);
  } catch {}
}
function recordExecution(entry) {
  store.error = "";
  executionEntry.value = entry;
}
function removeEntry(entry) {
  store.error = "";
  confirmation.value = {
    title: "Solicitar cancelamento",
    message:
      "O cancelamento depende do aceite dos participantes afetados. O histórico será preservado.",
    kind: "entry",
    id: entry.id,
  };
}
function removeDocument(doc) {
  store.error = "";
  confirmation.value = {
    title: "Excluir documento",
    message: "Excluir " + doc.filename + "?",
    kind: "document",
    id: doc.id,
  };
}
async function confirmAction() {
  if (store.pending || !confirmation.value) return;
  try {
    const action = confirmation.value;
    if (action.kind === "entry") await store.removeEntry(id.value, action.id);
    else await store.removeDocument(id.value, action.id);
    confirmation.value = null;
  } catch {
    /* Store exposes the error inside the dialog. */
  }
}
async function upload([f]) {
  if (f) await store.upload(id.value, f).catch(() => {});
}
watch(
  id,
  async (value) => {
    entryOpen.value = false;
    executionEntry.value = confirmation.value = null;
    tab.value = "routine";
    await store.loadRecipient(value).catch(() => {});
    tab.value = tabs.value[0]?.key || "routine";
    if (auth.hasPermission("organization-members.view"))
      await team.fetchMembers().catch(() => {});
  },
  { immediate: true },
);
</script>
