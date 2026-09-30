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
      <ProposalComparison :proposal="proposal" />
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
      <RouterLink
        :to="{
          name: 'decisions',
          query: { type: proposal.type, proposal: proposal.id },
        }"
        >Responder na central de decisões</RouterLink
      >
    </AppCard>
  </CareShell>
</template>
<script setup>
import { computed, watch } from "vue";
import { useRoute } from "vue-router";
import ProposalComparison from "@/components/care/ProposalComparison.vue";
import CareShell from "@/components/care/CareShell.vue";
import { AppCard } from "@/components/ui";
import { useCareStore } from "@/state/care";
import { dateTime } from "@/utils/care";
const store = useCareStore(),
  route = useRoute();
const proposal = computed(() => store.decision);
const labels = {
  pending: "Aguardando aceite",
  accepted: "Aceita",
  rejected: "Recusada",
  withdrawn: "Retirada",
};
function participant(id) {
  return (
    proposal.value.decisions.find((v) => v.user_id === id)?.user?.name ||
    (proposal.value.author?.id === id
      ? proposal.value.author.name
      : `Participante ${id}`)
  );
}
async function load() {
  try {
    await store.loadDecision(route.params.type, route.params.proposal);
  } catch {}
}
watch(() => [route.params.type, route.params.proposal], load, {
  immediate: true,
});
</script>
