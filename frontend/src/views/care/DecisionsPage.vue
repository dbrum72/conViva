<template>
  <CareShell
    title="Central de decisões"
    subtitle="Responda por você e acompanhe as propostas do grupo."
    @retry="load()"
  >
    <div class="care-form">
      <label class="care-field"
        >Mostrar<select v-model="scope" @change="load(1)">
          <option value="mine">Minhas decisões pendentes</option>
          <option value="sent">Propostas enviadas</option>
          <option value="all">Todas as propostas acessíveis</option>
        </select></label
      >
      <label class="care-field"
        >Situação<select v-model="status" @change="load(1)">
          <option value="">Todas</option>
          <option value="pending">Aguardando aceite</option>
          <option value="accepted">Aceitas</option>
          <option value="rejected">Recusadas</option>
          <option value="withdrawn">Retiradas</option>
        </select></label
      >
      <label class="care-field"
        >Tipo<select v-model="type" @change="load(1)">
          <option value="">Todos</option>
          <option value="entry">Cuidado</option>
          <option value="profile">Cadastro</option>
        </select></label
      >
    </div>
    <AppCard
      v-for="proposal in store.decisions?.data"
      :key="`${proposal.type}-${proposal.id}`"
    >
      <h2>
        <RouterLink
          :to="{
            name: 'decision',
            params: { type: proposal.type, proposal: proposal.id },
          }"
          >{{ proposal.title }}</RouterLink
        >
      </h2>
      <p>
        {{ proposal.author?.name }} · Versão {{ proposal.version }} ·
        {{ labels[proposal.status] }}
      </p>
      <p>{{ proposal.recipient_name }} · {{ dateTime(proposal.created_at) }}</p>
      <p v-if="proposal.blockers.length" role="status">
        Proposta bloqueada. Abra para consultar o motivo.
      </p>
      <ul>
        <li v-for="vote in proposal.decisions" :key="vote.id">
          {{ vote.user?.name }}: {{ labels[vote.status]
          }}<span v-if="vote.reason"> — {{ vote.reason }}</span>
        </li>
      </ul>
    </AppCard>
    <p
      v-if="store.decisions && !store.decisions.data.length && !store.pending"
      class="care-empty"
    >
      Nenhuma proposta para estes filtros.
    </p>
    <nav
      v-if="store.decisions"
      class="care-actions"
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
import { ref, onMounted } from "vue";
import CareShell from "@/components/care/CareShell.vue";
import { AppCard, AppButton } from "@/components/ui";
import { useCareStore } from "@/state/care";
import { dateTime } from "@/utils/care";
const store = useCareStore();
const scope = ref("mine"),
  status = ref(""),
  type = ref(""),
  page = ref(1);
const labels = {
  pending: "Aguardando aceite",
  accepted: "Aceita",
  rejected: "Recusada",
  withdrawn: "Retirada",
};
async function load(next = page.value) {
  page.value = next;
  try {
    await store.loadDecisions({
      scope: scope.value,
      status: status.value,
      type: type.value,
      page: next,
    });
  } catch {}
}
onMounted(() => load());
</script>
