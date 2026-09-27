<template>
  <CareShell
    title="Proposta e decisões"
    subtitle="O silêncio não representa aceite."
    @retry="load"
  >
    <RouterLink :to="{ name: 'decisions' }"
      >Voltar à central de decisões</RouterLink
    >
    <AppCard v-if="proposal">
      <h2>{{ proposal.title }}</h2>
      <p>
        {{ proposal.author?.name }} · Versão {{ proposal.version }} ·
        {{ labels[proposal.status] }}
      </p>
      <p v-if="proposal.status === 'pending'">
        {{
          proposal.has_current_version
            ? "A versão vigente permanece válida até os aceites necessários."
            : "Este cuidado ainda não está confirmado e não entra na agenda."
        }}
      </p>
      <p v-if="proposal.type === 'entry' && proposal.status === 'pending'">
        Concluir, executar ou pagar este registro fica indisponível enquanto
        houver proposta pendente.
      </p>
      <div v-if="proposal.blockers.length" role="status">
        <h3>Proposta bloqueada</h3>
        <ul>
          <li v-for="(blocker, index) in proposal.blockers" :key="index">
            {{ blocker.message }}
            <span v-if="blocker.user_id">{{
              participant(blocker.user_id)
            }}</span>
          </li>
        </ul>
        <p>Os votos e os participantes desta versão são preservados.</p>
      </div>
      <h3>Alterações propostas</h3>
      <p v-if="proposal.comparison_available">
        Comparação com a versão vigente no momento da proposta.
      </p>
      <p v-else>
        Esta proposta anterior à central não possui uma cópia da versão original
        para comparação. Os valores apresentados são os propostos.
      </p>
      <div class="comparison-scroll">
        <table v-if="proposal.changes.length">
          <thead>
            <tr>
              <th>Campo</th>
              <th>Antes</th>
              <th>Proposto</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="change in proposal.changes" :key="change.field">
              <th>{{ fields[change.field] || change.field }}</th>
              <td>{{ display(change.field, change.before) }}</td>
              <td>{{ display(change.field, change.after) }}</td>
            </tr>
          </tbody>
        </table>
        <p v-else>Nenhuma diferença de conteúdo registrada.</p>
      </div>
      <h3>Participantes e respostas</h3>
      <p>
        {{ proposal.author?.name }} apresentou esta proposta em
        {{ dateTime(proposal.created_at) }}.
      </p>
      <ul>
        <li v-for="vote in proposal.decisions" :key="vote.id">
          <strong>{{ vote.user?.name }}</strong
          >: {{ labels[vote.status]
          }}<span v-if="vote.decided_at">
            · {{ dateTime(vote.decided_at) }}</span
          >
          <p v-if="vote.reason">Motivo da recusa: {{ vote.reason }}</p>
        </li>
      </ul>
      <p v-if="!proposal.decisions.length">
        Sem outros participantes exigidos para esta versão.
      </p>
      <form
        v-if="proposal.can_reject || proposal.can_accept"
        @submit.prevent="respond('rejected')"
      >
        <AppButton
          v-if="proposal.can_accept"
          type="button"
          variant="action"
          :disabled="!!store.pending"
          @click="respond('accepted')"
          >Aceitar proposta</AppButton
        >
        <label v-if="proposal.can_reject" class="care-field"
          >Motivo da recusa<textarea
            v-model="reason"
            required
            maxlength="2000"
            rows="3"
          />
        </label>
        <AppButton
          v-if="proposal.can_reject"
          type="submit"
          :disabled="!!store.pending || !reason.trim()"
          >Recusar com justificativa</AppButton
        >
      </form>
      <AppButton
        v-if="proposal.can_withdraw"
        :disabled="!!store.pending"
        @click="withdraw"
        >Retirar proposta</AppButton
      >
    </AppCard>
  </CareShell>
</template>
<script setup>
import { computed, ref, watch } from "vue";
import { useRoute } from "vue-router";
import CareShell from "@/components/care/CareShell.vue";
import { AppCard, AppButton } from "@/components/ui";
import { useCareStore } from "@/state/care";
import { dateTime, money, recipientKinds } from "@/utils/care";
const store = useCareStore(),
  route = useRoute(),
  reason = ref("");
const proposal = computed(() => store.decision);
const labels = {
  pending: "Aguardando aceite",
  accepted: "Aceita",
  rejected: "Recusada",
  withdrawn: "Retirada",
};
const fields = {
  title: "Título",
  description: "Descrição",
  due_at: "Quando",
  ends_at: "Término",
  assigned_user_id: "Designado",
  amount_cents: "Valor",
  details: "Detalhes",
  shares: "Parcelas",
  affected_user_ids: "Participantes afetados",
  name: "Nome",
  kind: "Tipo",
  birth_date: "Nascimento",
  species: "Espécie",
  breed: "Raça",
  status: "Situação",
};
function participant(id) {
  return (
    proposal.value.decisions.find((v) => v.user_id === id)?.user?.name ||
    (proposal.value.author?.id === id
      ? proposal.value.author.name
      : `Participante ${id}`)
  );
}
function display(field, value) {
  if (value === null || value === undefined || value === "") return "—";
  if (field === "amount_cents") return money(value);
  if (field === "assigned_user_id") return participant(value);
  if (field === "affected_user_ids")
    return value.map(participant).join(", ") || "—";
  if (field === "shares")
    return (
      value
        .map((s) => `${participant(s.user_id)}: ${money(s.amount_cents)}`)
        .join("; ") || "—"
    );
  if (["due_at", "ends_at"].includes(field)) return dateTime(value);
  if (field === "kind") return recipientKinds[value] || value;
  if (field === "status")
    return (
      {
        active: "Ativo",
        archived: "Arquivado",
        pending: "Confirmado",
        cancelled: "Cancelado",
        completed: "Concluído",
      }[value] || value
    );
  if (typeof value === "object")
    return (
      Object.entries(value)
        .map(
          ([key, item]) =>
            `${{ dose: "Dose", frequency: "Frequência", route: "Via", food: "Alimento", quantity: "Quantidade", provider: "Profissional/local" }[key] || key}: ${item ?? "—"}`,
        )
        .join("; ") || "—"
    );
  return value;
}
async function load() {
  reason.value = "";
  try {
    await store.loadDecision(route.params.type, route.params.proposal);
  } catch {}
}
async function respond(decision) {
  try {
    await store.respondToProposal(proposal.value, {
      decision,
      reason: reason.value,
    });
  } catch {}
}
async function withdraw() {
  try {
    await store.withdrawProposal(proposal.value);
  } catch {}
}
watch(() => [route.params.type, route.params.proposal], load, {
  immediate: true,
});
</script>
<style scoped>
.comparison-scroll {
  overflow-x: auto;
}
table {
  border-collapse: collapse;
  width: 100%;
  margin-block: 1rem;
}
th,
td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid var(--color-border);
  vertical-align: top;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}
</style>
