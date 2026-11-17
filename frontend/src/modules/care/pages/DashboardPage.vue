<template>
  <CareShell title="Hoje" :subtitle="today?.recipient?.name" @retry="load">
    <template #actions>
      <RouterLink class="btn btn--outline" :to="{ name: 'organizations.select' }">
        Meus assistidos
      </RouterLink>
    </template>
    <p v-if="notice" role="status">{{ notice }}</p>
    <p v-if="today && !today.recipient" class="care-muted">
      Nenhum assistido ativo disponível neste grupo.
    </p>
    <template v-if="today?.recipient">
      <AppCard v-if="today.quick_facts.length" title="Registro rápido">
        <div class="care-actions">
          <AppButton
            v-for="kind in today.quick_facts"
            :key="kind"
            variant="outline"
            :disabled="!!store.pending"
            @click="openFact(kind)"
          >
            {{ factTitles[kind] }}
          </AppButton>
        </div>
      </AppCard>
      <div class="care-grid">
        <AppCard title="Próximos cuidados">
          <p v-if="!today.upcoming.length" class="care-muted">
            Nenhum cuidado programado nos próximos sete dias.
          </p>
          <div v-for="item in today.upcoming" :key="item.id" class="care-list-row">
            <div>
              <strong>{{ item.title }}</strong>
              <p class="care-muted">{{ format(item.due_at) }}</p>
              <p v-if="item.last_execution" class="care-muted">
                Último registro por {{ item.last_execution.author || 'autor indisponível' }}
                em {{ format(item.last_execution.occurred_at) }}
                <span v-if="item.last_execution.outcome === 'not_administered'"> · Não administrado</span>
              </p>
              <p v-if="item.execution_block || item.availability_conflict" class="care-muted">
                {{ item.execution_block || item.availability_conflict }}
              </p>
            </div>
            <AppButton
              v-if="item.can_execute"
              variant="outline"
              :disabled="!!store.pending"
              @click="openExecution(item, true)"
            >
              Registrar execução
            </AppButton>
          </div>
          <RouterLink :to="{ name: 'agenda' }">Abrir agenda</RouterLink>
        </AppCard>
        <AppCard title="Minhas decisões pendentes">
          <p v-if="!today.decision_count" class="care-muted">Nenhuma decisão pendente para você.</p>
          <ul v-else class="today-list">
            <li v-for="proposal in today.decisions" :key="`${proposal.type}-${proposal.id}`">
              <RouterLink :to="{ name: 'decisions', query: { type: proposal.type, proposal: proposal.id } }">
                {{ proposal.title }}
              </RouterLink>
            </li>
          </ul>
          <RouterLink :to="{ name: 'decisions' }">
            Abrir Central de Decisões<span v-if="today.decision_count"> ({{ today.decision_count }})</span>
          </RouterLink>
        </AppCard>
      </div>
      <AppCard v-if="handover" title="Passagem de cuidados">
        <div class="care-grid">
          <section>
            <h3>Últimas execuções e fatos</h3>
            <p v-if="!recent.length" class="care-muted">Nenhum registro nos últimos sete dias.</p>
            <ul v-else class="today-list">
              <li v-for="item in recent" :key="item.key">
                <strong>{{ item.title }}</strong>
                <p class="care-muted">{{ item.author || 'Autor indisponível' }} · {{ format(item.occurred_at) }}</p>
                <p v-if="item.outcome">{{ item.outcome === 'not_administered' ? 'Não administrado' : 'Administrado' }}</p>
                <p v-if="item.description" class="today-description">{{ item.description }}</p>
              </li>
            </ul>
          </section>
          <section>
            <h3>Pendências de cuidados</h3>
            <p v-if="!handover.overdue.length && !handover.tasks.length" class="care-muted">
              Nenhuma pendência neste resumo.
            </p>
            <div v-for="item in handover.overdue" :key="`occurrence-${item.id}`" class="care-list-row">
              <div>
                <strong>{{ item.title }}</strong>
                <p class="care-muted">{{ format(item.due_at) }} · Sem execução registrada</p>
                <p v-if="item.execution_block || item.availability_conflict" class="care-muted">
                  {{ item.execution_block || item.availability_conflict }}
                </p>
              </div>
              <AppButton
                v-if="item.can_execute"
                variant="outline"
                :disabled="!!store.pending"
                @click="openExecution(item, true)"
              >
                Registrar execução
              </AppButton>
            </div>
            <div v-for="item in handover.tasks" :key="`task-${item.id}`" class="care-list-row">
              <div>
                <strong>{{ item.title }}</strong>
                <p class="care-muted">{{ item.execution_block || 'Tarefa sem data' }}</p>
              </div>
              <AppButton
                v-if="item.can_execute"
                variant="outline"
                :disabled="!!store.pending"
                @click="openExecution(item, false)"
              >
                Registrar execução
              </AppButton>
            </div>
          </section>
        </div>
        <div v-if="recent.length || handover.overdue.length || handover.tasks.length" class="care-actions">
          <p v-if="handover.read_at" role="status">Leitura registrada em {{ format(handover.read_at) }}.</p>
          <AppButton v-else variant="outline" :disabled="!!store.pending" @click="acknowledge">
            Registrar ciência da leitura
          </AppButton>
        </div>
      </AppCard>
      <CareFactDialog
        :kind="factKind"
        :title="factTitles[factKind]"
        :recipient-id="today.recipient.id"
        @close="factKind = null"
        @saved="saved('Registro salvo.')"
      />
      <CareExecutionDialog
        :entry="execution?.item"
        :occurrence="execution?.occurrence"
        :recipient-id="today.recipient.id"
        :refresh-entries="false"
        @close="execution = null"
        @saved="saved('Execução registrada.')"
      />
    </template>
  </CareShell>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import CareExecutionDialog from "../components/CareExecutionDialog.vue";
import CareFactDialog from "../components/CareFactDialog.vue";
import CareShell from "../components/CareShell.vue";
import { AppButton, AppCard } from "@/components/ui";
import { useAuthStore } from "@/modules/auth";
import { useCareStore } from "../state/care.js";

const auth = useAuthStore();
const store = useCareStore();
const today = computed(() => store.today);
const handover = computed(() => today.value?.handover);
const execution = ref(null);
const factKind = ref(null);
const notice = ref("");
const factLabels = { feeding: "Alimentação", walk: "Passeio", observation: "Observação" };
const factTitles = {
  feeding: "Registrar alimentação",
  walk: "Registrar passeio",
  observation: "Registrar observação",
};

const recent = computed(() => [
  ...(handover.value?.executions ?? []).map((item) => ({ ...item, key: `execution-${item.id}` })),
  ...(handover.value?.facts ?? []).map((item) => ({
    ...item,
    title: factLabels[item.kind],
    key: `fact-${item.id}`,
  })),
].sort((a, b) => Date.parse(b.occurred_at) - Date.parse(a.occurred_at)));

function format(value) {
  return new Date(value).toLocaleString("pt-BR", {
    timeZone: today.value.timezone,
    dateStyle: "short",
    timeStyle: "short",
  });
}

function openFact(kind) {
  store.error = "";
  factKind.value = kind;
}

function openExecution(item, occurrence) {
  store.error = "";
  execution.value = { item, occurrence };
}

async function load() {
  try {
    await store.loadToday();
  } catch {
    // CareShell displays the error and provides a retry.
  }
}

function saved(message) {
  notice.value = message;
  store.today = null;
  return load();
}

async function acknowledge() {
  try {
    await store.readHandover(today.value.recipient.id, handover.value.version);
  } catch {
    // A stale summary can be refreshed using the retry in CareShell.
  }
}

watch(() => auth.currentTenant, () => {
  execution.value = null;
  factKind.value = null;
  notice.value = "";
  load();
}, { immediate: true });
</script>

<style scoped>
.today-list {
  display: grid;
  gap: 0.75rem;
  padding-inline-start: 1.2rem;
}

.today-description {
  overflow-wrap: anywhere;
  white-space: pre-wrap;
}

.care-list-row {
  flex-wrap: wrap;
}
</style>
