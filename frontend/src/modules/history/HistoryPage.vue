<template>
  <CareShell
    title="Histórico de cuidados"
    :request-status="requestStatus"
    @retry="load"
  >
    <div v-if="store.timeline" class="history">
      <AppCard class="history-filter-card">
        <h2>Filtrar histórico</h2>
        <AppForm
          class="history-filters"
          :aria-busy="busy"
          @submit.prevent="load(1)"
        >
          <AppSelect
            id="history-area"
            class="history-area"
            label="Área"
            v-model="area"
            :options="areas"
            :disabled="busy"
          />
          <AppDate
            id="history-from"
            label="De"
            v-model="from"
            :disabled="busy"
            :show-icon="false"
            required
          />
          <AppDate
            id="history-to"
            label="Até"
            v-model="to"
            :min="from"
            :disabled="busy"
            :show-icon="false"
            required
          />
          <AppButton type="submit" :disabled="busy" icon="search"
            >Consultar</AppButton
          >
        </AppForm>
      </AppCard>

      <div class="history-workspace">
        <AppCard
          as="aside"
          class="history-export"
          aria-labelledby="history-export-title"
        >
          <div class="history-export-heading">
            <span class="history-icon history-icon--file"
              ><AppIcon name="file" :size="20"
            /></span>
            <div>
              <h2 id="history-export-title">Exportação</h2>
              <p>PDF com os filtros aplicados</p>
            </div>
          </div>
          <AppButton
            variant="outline"
            icon="file"
            :disabled="busy"
            @click="perform(store.requestExport)"
          >
            Solicitar PDF
          </AppButton>

          <div v-if="store.report" class="history-report">
            <div role="status" class="history-report-info">
              <div class="history-report-heading">
                <strong
                  >Último arquivo ·
                  {{ store.report.format.toUpperCase() }}</strong
                >
                <span class="history-badge" :data-status="store.report.status">
                  {{ labels[store.report.status] }}
                </span>
              </div>
              <p>{{ period(store.report.filters) }}</p>
              <p>{{ areaName(store.report.filters.area) }}</p>
              <p v-if="store.report.requested_at">
                Solicitado em {{ dateTime(store.report.requested_at) }}
              </p>
            </div>
            <AppButton
              v-if="store.report.status === 'pending'"
              variant="outline"
              size="sm"
              :disabled="busy"
              @click="perform(store.refreshExport)"
              >Verificar exportação</AppButton
            >
            <AppButton
              v-if="store.report.status === 'ready'"
              icon="download"
              size="sm"
              :disabled="busy"
              @click="perform(store.download)"
              >Baixar</AppButton
            >
          </div>
        </AppCard>

        <section
          class="history-timeline"
          aria-labelledby="history-timeline-title"
          :aria-busy="busy"
        >
          <header class="history-timeline-heading">
            <h2 id="history-timeline-title">Linha do tempo</h2>
            <div class="history-summary">
              <span
                ><AppIcon name="calendar" :size="16" />
                {{ period(applied) }}</span
              >
              <span>{{ areaName(applied.area) }}</span>
            </div>
            <p class="history-timezone">
              Fuso dos registros · {{ applied.timezone }}
            </p>
          </header>

          <AppCard v-if="!store.timeline.events.length" class="history-empty">
            <span class="history-icon"
              ><AppIcon name="calendar" :size="22"
            /></span>
            <div>
              <h3>Nenhum evento neste período.</h3>
              <p>Altere as datas ou a área e consulte novamente.</p>
            </div>
          </AppCard>
          <section v-for="[day, events] in days" :key="day" class="history-day">
            <h3>{{ day }}</h3>
            <ol class="history-events">
              <li v-for="event in events" :key="event.id">
                <AppCard as="article" class="history-event">
                  <header class="history-event-heading">
                    <p class="history-event-category">
                      {{ labels[event.type] }} · {{ labels[event.area] }}
                    </p>
                    <span class="history-badge" :data-status="event.status">{{
                      labels[event.status]
                    }}</span>
                    <h4>{{ event.title }}</h4>
                  </header>
                  <p class="history-event-byline">
                    <AppIcon name="user" :size="16" /> {{ event.author }}
                    <span class="history-event-date"
                      >Registrado em {{ dateTime(event.recorded_at) }}</span
                    >
                  </p>
                  <div class="history-event-details">
                    <p v-if="event.occurred_at">
                      Ocorreu em {{ dateTime(event.occurred_at) }}
                    </p>
                    <p v-if="event.version">
                      Versão {{ event.version }} · {{ labels[event.operation] }}
                    </p>
                    <p v-if="event.related_id">
                      Relacionado ao registro {{ event.related_id }}
                    </p>
                    <p v-if="event.outcome">{{ labels[event.outcome] }}</p>
                    <p v-if="event.reason" class="history-event-note">
                      <strong>Motivo</strong> {{ event.reason }}
                    </p>
                    <p v-if="event.notes" class="history-event-note">
                      <strong>Observação</strong> {{ event.notes }}
                    </p>
                  </div>
                </AppCard>
              </li>
            </ol>
          </section>

          <nav class="history-pagination" aria-label="Páginas do histórico">
            <AppButton
              v-if="store.timeline.page > 1"
              variant="outline"
              :disabled="busy"
              @click="load(store.timeline.page - 1, applied)"
              >Anterior</AppButton
            >
            <AppButton
              v-if="store.timeline.has_more"
              variant="outline"
              :disabled="busy"
              @click="load(store.timeline.page + 1, applied)"
              >Próxima</AppButton
            >
          </nav>
        </section>
      </div>
    </div>
  </CareShell>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { AppDate, AppForm, AppSelect } from "@/components/forms";
import { AppButton, AppCard, AppIcon } from "@/components/ui";
import { useAuthStore } from "@/modules/auth";
import { CareShell } from "@/modules/care/ui.js";
import { useHistoryStore } from "./store.js";

const store = useHistoryStore();
const auth = useAuthStore();
const from = ref("");
const to = ref("");
const area = ref("");

const labels = {
  routine: "Rotina",
  health: "Saúde",
  expenses: "Despesas",
  proposal: "Proposta",
  decision: "Decisão",
  withdrawal: "Retirada",
  execution: "Execução",
  correction: "Correção",
  profile_proposal: "Proposta cadastral",
  profile_decision: "Decisão cadastral",
  profile_withdrawal: "Retirada cadastral",
  pending: "Pendente",
  accepted: "Aceita",
  rejected: "Recusada",
  withdrawn: "Retirada",
  completed: "Registrado",
  save: "Salvar",
  archive: "Arquivar",
  cancel: "Cancelar",
  cancel_occurrence: "Cancelar ocorrência",
  administered: "Administrado",
  not_administered: "Não administrado",
  ready: "Disponível",
  denied: "Acesso revogado",
  failed: "Falha na geração",
  expired: "Expirada",
};
const areaName = (key) => (key ? labels[key] : "Todas as áreas disponíveis");
const areas = computed(() => [
  { value: "", label: areaName(null) },
  ...store.timeline.areas.map((value) => ({ value, label: areaName(value) })),
]);
const applied = computed(() => store.timeline.filters);
const busy = computed(() => store.pending > 0);
const requestStatus = computed(() => ({
  pending: store.pending,
  error: store.error,
}));
const formatter = computed(
  () =>
    new Intl.DateTimeFormat("pt-BR", {
      dateStyle: "short",
      timeStyle: "short",
      timeZone: applied.value.timezone,
    }),
);
const dateTime = (value) => formatter.value.format(new Date(value));
const calendarDate = new Intl.DateTimeFormat("pt-BR", {
  dateStyle: "short",
  timeZone: "UTC",
});
const period = ({ from, to }) =>
  `${calendarDate.format(new Date(from))} a ${calendarDate.format(new Date(to))}`;
const days = computed(() => {
  const formatter = new Intl.DateTimeFormat("pt-BR", {
    dateStyle: "long",
    timeZone: applied.value.timezone,
  });
  const groups = new Map();
  for (const event of store.timeline.events) {
    const day = formatter.format(new Date(event.recorded_at));
    if (!groups.has(day)) groups.set(day, []);
    groups.get(day).push(event);
  }
  return groups;
});

async function perform(action) {
  try {
    return await action();
  } catch {
    // The store exposes request errors through CareShell.
  }
}

async function load(
  page = 1,
  filters = { from: from.value, to: to.value, area: area.value || null },
) {
  const result = await perform(() => store.load({ ...filters, page }));
  if (!result) return;
  from.value = result.from;
  to.value = result.to;
  area.value = result.area ?? "";
}

function loadContext() {
  store.clear();
  from.value = "";
  to.value = "";
  area.value = "";
  return load();
}

onMounted(loadContext);
watch(
  () => auth.organization?.id,
  (id) => {
    if (id) loadContext();
  },
);
onBeforeUnmount(store.clear);
</script>

<style scoped>
.history {
  container-type: inline-size;
  display: grid;
  gap: var(--space-5);
  min-width: 0;
}

.history h2,
.history h3,
.history h4,
.history p {
  margin: 0;
}

.history h2 {
  font-size: var(--font-size-lg);
  color: var(--color-text);
}

.history-filter-card .history-filters {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  align-items: end;
  gap: var(--space-3);
  margin-top: var(--space-3);
}

.history-area {
  grid-column: 1 / -1;
}

.history-filters > .history-area,
.history-filters > :deep(.field) {
  min-width: 0;
}

.history-workspace {
  display: grid;
  gap: var(--space-5);
  align-items: start;
  min-width: 0;
}

.history-export :deep(.card__body) {
  display: grid;
  justify-items: start;
  gap: var(--space-4);
}

.history-export-heading,
.history-empty :deep(.card__body) {
  display: flex;
  align-items: center;
  gap: var(--space-3);
}

.history-icon {
  display: grid;
  place-items: center;
  flex: 0 0 2.75rem;
  width: 2.75rem;
  height: 2.75rem;
  border-radius: var(--radius-md);
  background: var(--color-info-soft);
  color: var(--color-brand);
}

.history-icon--file {
  background: var(--color-surface-accent);
  color: var(--color-brand-secondary);
}

.history-export-heading p,
.history-report-info p,
.history-timezone,
.history-event-date,
.history-empty p {
  color: var(--color-text-muted);
  font-size: var(--font-size-sm);
}

.history-report {
  display: grid;
  justify-items: start;
  gap: var(--space-3);
  width: 100%;
  border-top: var(--border-width) solid var(--color-divider);
  padding-top: var(--space-4);
}

.history-report-heading,
.history-summary,
.history-event-byline {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-2);
}

.history-report-heading {
  margin-bottom: var(--space-2);
  font-size: var(--font-size-sm);
}

.history-badge {
  display: inline-block;
  width: fit-content;
  border-radius: 1rem;
  padding: 0.2rem 0.65rem;
  color: var(--color-info);
  background: var(--color-info-soft);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-bold);
}

.history-badge:is(
  [data-status="ready"],
  [data-status="accepted"],
  [data-status="completed"]
) {
  color: var(--color-success);
  background: var(--color-success-soft);
}

.history-badge[data-status="pending"] {
  color: var(--color-on-warning);
  background: var(--color-surface-warning-soft);
}

.history-badge:is(
  [data-status="failed"],
  [data-status="denied"],
  [data-status="rejected"]
) {
  color: var(--color-danger);
  background: var(--color-danger-soft);
}

.history-timeline {
  display: grid;
  gap: var(--space-4);
  min-width: 0;
}

.history-timeline-heading {
  display: grid;
  gap: var(--space-2);
}

.history-summary span {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  padding: 0.25rem 0.65rem;
  border: var(--border-width) solid var(--color-border);
  border-radius: 1rem;
  background: var(--color-surface);
  color: var(--color-text-soft);
  font-size: var(--font-size-sm);
}

.history-empty h3,
.history-event-heading h4 {
  font-size: var(--font-size-md);
}

.history-day > h3 {
  margin-bottom: var(--space-3);
  font-size: var(--font-size-sm);
  color: var(--color-text-soft);
}

.history-events {
  display: grid;
  gap: var(--space-3);
  border-inline-start: 1px solid var(--color-border-strong);
  margin: 0 0 0 0.35rem;
  padding: 0 0 0 var(--space-4);
  list-style: none;
}

.history-events li {
  position: relative;
  min-width: 0;
}

.history-events li::before {
  position: absolute;
  inset-block-start: 1.35rem;
  inset-inline-start: calc(-1 * var(--space-4) - 0.32rem);
  content: "";
  width: 0.55rem;
  height: 0.55rem;
  border: 2px solid var(--color-page);
  border-radius: 50%;
  background: var(--color-brand);
}

.history-event-heading {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: start;
  gap: var(--space-2);
}

.history-event-heading h4 {
  grid-column: 1 / -1;
  overflow-wrap: anywhere;
}

.history-event-heading .history-badge {
  max-width: 100%;
  text-align: center;
}

.history-event-category {
  font-size: var(--font-size-sm);
  color: var(--color-brand);
  font-weight: var(--font-weight-bold);
}

.history-event .history-event-byline {
  margin-block: var(--space-2);
  font-size: var(--font-size-sm);
  color: var(--color-text-soft);
}

.history-event-details {
  display: grid;
  gap: var(--space-2);
  font-size: var(--font-size-sm);
  color: var(--color-text-soft);
}

.history-event-note {
  border-inline-start: 2px solid var(--color-border);
  padding-inline-start: var(--space-3);
  white-space: pre-line;
  overflow-wrap: anywhere;
}

.history-event-note strong {
  display: block;
  color: var(--color-text);
}

.history-pagination {
  display: flex;
  justify-content: space-between;
  gap: var(--space-3);
}

.history-pagination:empty {
  display: none;
}

@container (min-width: 44rem) {
  .history-filter-card .history-filters {
    grid-template-columns: minmax(0, 1.25fr) repeat(2, minmax(0, 1fr)) auto;
  }

  .history-area {
    grid-column: auto;
  }
}

@container (min-width: 52rem) {
  .history-workspace {
    grid-template-columns: minmax(0, 1fr) minmax(16rem, 0.48fr);
  }

  .history-timeline {
    grid-column: 1;
    grid-row: 1;
  }

  .history-export {
    grid-column: 2;
  }
}

@container (max-width: 25rem) {
  .history-filter-card .history-filters {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>
