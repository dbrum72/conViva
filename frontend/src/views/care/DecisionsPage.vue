<template>
  <CareShell
    title="Central de decisões"
    subtitle="Responda por você e acompanhe as propostas do grupo."
    @retry="load()"
  >
    <div class="care-form">
      <label class="care-field"
        >Mostrar<select
          :disabled="!!store.pending"
          v-model="scope"
          @change="load(1)"
        >
          <option value="mine">Minhas decisões pendentes</option>
          <option value="sent">Propostas enviadas</option>
          <option value="all">Todas as propostas acessíveis</option>
        </select></label
      >
      <label class="care-field"
        >Situação<select
          :disabled="!!store.pending"
          v-model="status"
          @change="load(1)"
        >
          <option value="">Todas</option>
          <option value="pending">Aguardando aceite</option>
          <option value="accepted">Aceitas</option>
          <option value="rejected">Recusadas</option>
          <option value="withdrawn">Retiradas</option>
        </select></label
      >
      <label class="care-field"
        >Tipo<select
          :disabled="!!store.pending"
          v-model="type"
          @change="load(1)"
        >
          <option value="">Todos</option>
          <option value="entry">Cuidado</option>
          <option value="profile">Cadastro</option>
        </select></label
      >
    </div>
    <p v-if="feedback" role="status">{{ feedback }}</p>
    <AppCard
      v-for="proposal in proposals"
      :key="`${proposal.type}-${proposal.id}`"
    >
      <p
        v-if="
          String(route.query.proposal) === String(proposal.id) &&
          route.query.type === proposal.type
        "
        class="care-muted"
      >
        Proposta selecionada pelo link
      </p>
      <h2>{{ proposal.title }}</h2>
      <p>{{ proposal.author?.name }} · Versão {{ proposal.version }}</p>
      <p>
        Situação da proposta:
        <DecisionStatusBadge :status="proposal.status" />
      </p>
      <p>{{ proposal.recipient_name }} · {{ dateTime(proposal.created_at) }}</p>
      <div v-if="proposal.blockers.length" role="status">
        <strong>Proposta bloqueada</strong>
        <ul>
          <li v-for="(blocker, index) in proposal.blockers" :key="index">
            {{ blocker.message }}
          </li>
        </ul>
      </div>
      <details>
        <summary>Ver alterações propostas</summary>
        <ProposalComparison :proposal="proposal" />
      </details>
      <p v-if="proposal.status === 'pending'">
        {{
          proposal.has_current_version
            ? "A versão vigente permanece válida até todos os aceites necessários."
            : "Esta proposta ainda não está confirmada."
        }}
      </p>
      <ul class="proposal-decisions">
        <li v-if="proposal.author_acceptance">
          {{ proposal.author?.name }} (autor):
          <DecisionStatusBadge :status="proposal.author_acceptance.status" />
          <span> — Aceite automático ao enviar a proposta.</span>
        </li>
        <li v-for="vote in proposal.decisions" :key="vote.id">
          {{ vote.user?.name }}: <DecisionStatusBadge :status="vote.status" />
          <span v-if="vote.reason"> — {{ vote.reason }}</span>
        </li>
      </ul>
      <ProposalActions
        :proposal="proposal"
        :busy="!!store.pending"
        @respond="respond(proposal, $event)"
        @withdraw="withdraw(proposal)"
      />
    </AppCard>
    <p
      v-if="store.decisions && !proposals.length && !store.pending"
      class="care-empty"
    >
      Nenhuma proposta para estes filtros.
    </p>
    <nav
      v-if="store.decisions"
      class="care-pagination"
      aria-label="Páginas de propostas"
    >
      <AppButton
        :disabled="!!store.pending || page === 1"
        @click="load(page - 1)"
        >Anterior</AppButton
      >
      <span
        >Página {{ store.decisions.current_page }} de
        {{ store.decisions.last_page }}</span
      >
      <AppButton
        :disabled="!!store.pending || page >= store.decisions.last_page"
        @click="load(page + 1)"
        >Próxima</AppButton
      >
    </nav>
  </CareShell>
</template>
<script setup>
import { ref, computed, watch, onBeforeUnmount } from "vue";
import { useRoute } from "vue-router";
import { useAuthStore } from "@/state/auth";
import ProposalActions from "@/components/care/ProposalActions.vue";
import DecisionStatusBadge from "@/components/care/DecisionStatusBadge.vue";
import ProposalComparison from "@/components/care/ProposalComparison.vue";
import CareShell from "@/components/care/CareShell.vue";
import { AppCard, AppButton } from "@/components/ui";
import { useCareStore } from "@/state/care";
import { dateTime } from "@/utils/care";
const store = useCareStore(),
  route = useRoute(),
  auth = useAuthStore();
const feedback = ref("");
let context = 0,
  loadRequest = 0;
const scope = ref(route.query.proposal ? "all" : "mine"),
  status = ref(""),
  type = ref(""),
  page = ref(1);
const showLinkedProposal = computed(
  () =>
    scope.value === "all" && !status.value && !type.value && page.value === 1,
);
const proposals = computed(() => {
  const rows = store.decisions?.data || [];
  const selected = store.decision;
  if (
    showLinkedProposal.value &&
    route.query.proposal &&
    selected?.type === route.query.type &&
    String(selected.id) === String(route.query.proposal)
  ) {
    return [
      selected,
      ...rows.filter((p) => p.type !== selected.type || p.id !== selected.id),
    ];
  }
  return rows;
});
async function load(next = page.value) {
  const current = context,
    request = ++loadRequest;
  page.value = next;
  if (route.query.proposal) store.decision = null;
  try {
    await store.loadDecisions({
      scope: scope.value,
      status: status.value,
      type: type.value,
      page: next,
    });
    if (current !== context || request !== loadRequest) return;
    if (
      showLinkedProposal.value &&
      route.query.proposal &&
      ["entry", "profile"].includes(route.query.type)
    ) {
      await store.loadDecision(route.query.type, route.query.proposal);
    }
    if (store.decisions && next > store.decisions.last_page)
      await load(store.decisions.last_page);
  } catch {}
}
async function respond(proposal, data) {
  if (store.pending) return;
  const current = context;
  feedback.value = "";
  try {
    const completed = await store.respondToProposal(proposal, data);
    if (!completed || current !== context) return;
    feedback.value =
      data.decision === "accepted"
        ? "Seu aceite foi registrado. A proposta depende de todos os aceites exigidos."
        : "Recusa registrada com justificativa.";
    await load();
  } catch {}
}
async function withdraw(proposal) {
  if (store.pending) return;
  const current = context;
  feedback.value = "";
  try {
    const completed = await store.withdrawProposal(proposal);
    if (!completed || current !== context) return;
    feedback.value = "Proposta retirada. O histórico foi preservado.";
    await load();
  } catch {}
}
watch(
  () => [
    route.query.type,
    route.query.proposal,
    auth.organization?.id,
    auth.user?.id,
  ],
  ([linkedType, linkedId], [previousType, previousId] = []) => {
    if (linkedId && (linkedId !== previousId || linkedType !== previousType)) {
      scope.value = "all";
      status.value = "";
      type.value = "";
    }
    context++;
    feedback.value = "";
    load(1);
  },
  { immediate: true },
);
onBeforeUnmount(() => context++);
</script>

<style scoped>
.proposal-decisions {
  display: grid;
  gap: 0.75rem;
  margin-block: 1rem 1.25rem;
  padding-left: 1.5rem;
}
.proposal-decisions li {
  line-height: 1.75;
}
</style>
