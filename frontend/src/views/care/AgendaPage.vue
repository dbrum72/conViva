<template>
  <CareShell
    class="agenda-page"
    title="Agenda"
    subtitle="Acompanhe os cuidados do grupo em uma visão de calendário."
    @retry="load()"
  >
    <section class="agenda-toolbar" aria-label="Controles da agenda">
      <div class="agenda-period">
        <div class="agenda-navigation">
          <button type="button" aria-label="Período anterior" @click="move(-1)">
            ‹</button
          ><button type="button" class="agenda-today" @click="goToday">
            Hoje</button
          ><button type="button" aria-label="Próximo período" @click="move(1)">
            ›
          </button>
        </div>
        <h2 aria-live="polite">{{ periodLabel }}</h2>
      </div>
      <div class="agenda-view-switcher" aria-label="Visualização da agenda">
        <button
          v-for="(label, key) in views"
          :key="key"
          type="button"
          :aria-pressed="view === key"
          :class="{ 'is-active': view === key }"
          @click="setView(key)"
        >
          {{ label }}
        </button>
      </div>
      <div class="agenda-toolbar-filters">
        <label for="agenda-kind"
          >Tipo<select id="agenda-kind" v-model="filters.kind">
            <option value="">Todos os tipos</option>
            <option v-for="(label, key) in entryKinds" :key="key" :value="key">
              {{ label }}
            </option>
          </select></label
        >
        <label for="agenda-executor"
          >Executor<select id="agenda-executor" v-model="filters.executor">
            <option value="">Todos os executores</option>
            <option
              v-for="member in executors"
              :key="member.id"
              :value="member.id"
            >
              {{ member.name }}
            </option>
          </select></label
        >
        <label for="agenda-status"
          >Situação<select id="agenda-status" v-model="filters.status">
            <option
              v-for="(label, key) in agendaStates"
              :key="key"
              :value="key"
            >
              {{ label }}
            </option>
          </select></label
        >
        <label for="agenda-jump"
          >Ir para a data<input
            id="agenda-jump"
            type="date"
            :value="selectedDate"
            @change="jump($event.target.value)"
        /></label>
      </div>
    </section>
    <div class="agenda-context">
      <span
        >Horários em <strong>{{ zone }}</strong></span
      ><RouterLink :to="{ name: 'decisions' }"
        >Propostas pendentes <span aria-hidden="true">↗</span></RouterLink
      >
    </div>
    <p v-if="notice" role="status" class="agenda-feedback">{{ notice }}</p>
    <p v-if="store.error && !store.agendaPage" class="agenda-data-notice">
      Não foi possível carregar o período completo. Tente novamente.
    </p>
    <div
      v-if="view === 'month'"
      class="agenda-workspace"
      :aria-busy="!!store.pending"
    >
      <div>
        <AgendaMonth
          :dates="dates"
          :items="groups"
          :month="anchor"
          :today="today"
          :selected-date="selectedDate"
          :timezone="zone"
          @select="selectedDate = $event"
        />
        <footer class="agenda-legend" aria-label="Tipos de cuidados">
          <span
            v-for="(label, key) in entryKinds"
            :key="key"
            :class="`agenda-kind-${key}`"
            ><i aria-hidden="true" />{{ label }}</span
          >
        </footer>
      </div>
      <aside
        class="agenda-selected-day"
        aria-labelledby="agenda-selected-title"
      >
        <header class="agenda-selected-heading">
          <span>{{ dateLabel(selectedDate, { weekday: "long" }) }}</span>
          <h2 id="agenda-selected-title">{{ dateLabel(selectedDate) }}</h2>
          <p>
            {{
              store.pending
                ? "Carregando cuidados…"
                : `${selectedItems.length} ${selectedItems.length === 1 ? "cuidado" : "cuidados"}`
            }}
          </p>
        </header>
        <div
          v-if="!selectedItems.length && !store.pending && !store.error"
          class="agenda-empty"
        >
          <span class="agenda-empty-mark" aria-hidden="true">○</span
          ><strong>Nenhum cuidado neste dia</strong>
          <p>
            Os cuidados deste dia aparecerão aqui conforme os filtros
            selecionados.
          </p>
        </div>
        <div class="agenda-selected-items">
          <AgendaOccurrence
            v-for="item in selectedItems"
            :key="item.id"
            :item="item"
            :timezone="zone"
            :executor="executor(item.assigned_user_id)"
            :busy="!!store.pending"
            @execute="open($event, 'execute')"
            @cancel="open($event, 'cancel')"
          />
        </div>
      </aside>
    </div>
    <section
      v-else-if="view === 'week'"
      class="agenda-week-viewport"
      aria-label="Agenda semanal"
      :aria-busy="!!store.pending"
    >
      <div class="agenda-week-grid">
        <section
          v-for="date in dates"
          :key="date"
          class="agenda-week-day"
          :class="{ 'is-today': date === today }"
        >
          <header>
            <span>{{ dateLabel(date, { weekday: "short" }) }}</span
            ><button
              type="button"
              :aria-label="`Abrir ${dateLabel(date, { dateStyle: 'full' })}`"
              @click="showDay(date)"
            >
              {{ Number(date.slice(-2)) }}</button
            ><small>{{ groups[date]?.length || 0 }} cuidados</small>
          </header>
          <button
            v-for="item in groups[date]"
            :key="item.id"
            type="button"
            class="agenda-week-item"
            :class="[
              `agenda-kind-${item.kind}`,
              { 'agenda-is-overdue': item.is_overdue },
            ]"
            @click="showDay(date)"
          >
            <span class="agenda-kind-label"
              ><i aria-hidden="true" />{{ entryKinds[item.kind] }}</span
            ><time>{{ timeLabel(item.due_at, zone) }}</time
            ><strong>{{ item.title }}</strong
            ><small>{{
              executor(item.assigned_user_id) || "Sem executor designado"
            }}</small
            ><span v-if="item.conflict" class="agenda-week-warning"
              >Sobreposição de horários</span
            >
          </button>
          <p
            v-if="!groups[date]?.length && !store.pending && !store.error"
            class="agenda-week-empty"
          >
            Nenhum cuidado
          </p>
        </section>
      </div>
    </section>
    <section
      v-else
      class="agenda-list"
      :aria-label="view === 'day' ? 'Cuidados do dia' : 'Agenda em lista'"
      :aria-busy="!!store.pending"
    >
      <form
        v-if="view === 'list'"
        class="agenda-list-range"
        @submit.prevent="applyRange"
      >
        <label>De<input type="date" v-model="rangeDraft.from" required /></label
        ><label>Até<input type="date" v-model="rangeDraft.to" required /></label
        ><AppButton type="submit" variant="outline" :disabled="!!store.pending"
          >Aplicar período</AppButton
        >
      </form>
      <div
        v-if="!visibleGroups.length && !store.pending && !store.error"
        class="agenda-empty"
      >
        <strong>Nenhum cuidado neste período</strong>
        <p>Experimente outro período ou ajuste os filtros.</p>
      </div>
      <section
        v-for="group in visibleGroups"
        :key="group.date"
        class="agenda-list-group"
      >
        <header>
          <span>{{ dateLabel(group.date, { weekday: "long" }) }}</span>
          <h2>{{ dateLabel(group.date) }}</h2>
          <small>{{ group.items.length }} cuidados</small>
        </header>
        <div class="agenda-list-items">
          <AgendaOccurrence
            v-for="item in group.items"
            :key="item.id"
            :item="item"
            :timezone="zone"
            :executor="executor(item.assigned_user_id)"
            :busy="!!store.pending"
            @execute="open($event, 'execute')"
            @cancel="open($event, 'cancel')"
          />
        </div>
      </section>
      <nav
        v-if="view === 'list' && store.agendaPage"
        class="care-pagination"
        aria-label="Páginas da agenda"
      >
        <AppButton
          variant="outline"
          :disabled="store.agendaPage.current_page <= 1 || !!store.pending"
          @click="load(store.agendaPage.current_page - 1)"
          >Anterior</AppButton
        ><span
          >Página {{ store.agendaPage.current_page }} de
          {{ store.agendaPage.last_page }} ·
          {{ store.agendaPage.total }} ocorrências</span
        ><AppButton
          variant="outline"
          :disabled="
            store.agendaPage.current_page >= store.agendaPage.last_page ||
            !!store.pending
          "
          @click="load(store.agendaPage.current_page + 1)"
          >Próxima</AppButton
        >
      </nav>
    </section>
    <AppDialog
      :open="!!selected"
      :title="
        action === 'execute'
          ? 'Registrar execução da ocorrência'
          : 'Propor cancelamento'
      "
      :busy="!!store.pending"
      :error="store.error"
      @close="selected = null"
    >
      <form class="care-form" @submit.prevent="submit">
        <p>
          {{ selected?.title }} · {{ selected ? format(selected.due_at) : "" }}
        </p>
        <template v-if="action === 'execute'"
          ><p>
            Execução realizada por {{ auth.user?.name }}. Sua identificação será
            registrada automaticamente.
          </p>
          <label class="care-field"
            >Quando ocorreu (horário deste dispositivo)<input
              type="datetime-local"
              v-model="occurredAt"
              required /></label
          ><label class="care-field"
            >Observação<textarea
              v-model="description"
              maxlength="10000"
            /></label
        ></template>
        <template v-else
          ><label class="care-field"
            >Cancelar<select v-model="scope">
              <option value="one">Somente esta ocorrência</option>
              <option value="future">
                Esta e todas as futuras desta série
              </option>
            </select></label
          >
          <p>
            O cancelamento depende dos aceites necessários na Central. O
            histórico é preservado.
          </p></template
        >
        <AppButton type="submit" :disabled="!!store.pending">{{
          action === "execute" ? "Confirmar execução" : "Enviar proposta"
        }}</AppButton>
      </form>
    </AppDialog>
  </CareShell>
</template>
<script setup>
import { computed, ref, watch } from "vue";
import { useRouter } from "vue-router";
import CareShell from "@/components/care/CareShell.vue";
import AgendaMonth from "@/components/care/agenda/AgendaMonth.vue";
import AgendaOccurrence from "@/components/care/agenda/AgendaOccurrence.vue";
import { AppButton, AppDialog } from "@/components/ui";
import { useCareStore } from "@/state/care";
import { useAuthStore } from "@/state/auth";
import { entryKinds } from "@/utils/care";
import {
  addDays,
  agendaStates,
  calendarDates,
  dateKey,
  dateLabel,
  monthStart,
  occurrenceDays,
  shiftMonth,
  timeLabel,
  weekStart,
} from "@/utils/agenda";
import "@/assets/styles/agenda.css";
const store = useCareStore(),
  auth = useAuthStore(),
  router = useRouter();
const zone = computed(
  () =>
    auth.organization?.timezone ||
    store.agendaPage?.timezone ||
    "America/Sao_Paulo",
);
const today = computed(() => dateKey(new Date(), zone.value));
const views = { month: "Mês", week: "Semana", day: "Dia", list: "Lista" };
const view = ref("month"),
  anchor = ref(today.value),
  selectedDate = ref(today.value);
const filters = ref({ kind: "", executor: "", status: "scheduled" });
const range = ref({
  from: monthStart(today.value),
  to: addDays(shiftMonth(today.value, 1), -1),
});
const rangeDraft = ref({ ...range.value });
const dates = computed(() => {
  if (view.value === "month") return calendarDates(anchor.value);
  if (view.value === "week")
    return Array.from({ length: 7 }, (_, i) =>
      addDays(weekStart(anchor.value), i),
    );
  if (view.value === "day") return [selectedDate.value];
  const result = [];
  for (
    let date = range.value.from, n = 0;
    date <= range.value.to && n < 93;
    date = addDays(date, 1), n++
  )
    result.push(date);
  return result;
});
const bounds = computed(() =>
  view.value === "list"
    ? range.value
    : { from: dates.value[0], to: dates.value.at(-1) },
);
const groups = computed(() =>
  occurrenceDays(store.agenda, dates.value, zone.value),
);
const selectedItems = computed(() => groups.value[selectedDate.value] || []);
const visibleGroups = computed(() =>
  dates.value
    .map((date) => ({ date, items: groups.value[date] || [] }))
    .filter((group) => group.items.length),
);
const executors = computed(() => store.agendaPage?.executors || []);
const periodLabel = computed(() => {
  if (view.value === "month")
    return dateLabel(anchor.value, { month: "long", year: "numeric" });
  if (view.value === "day")
    return dateLabel(selectedDate.value, {
      day: "numeric",
      month: "long",
      year: "numeric",
    });
  return `${dateLabel(bounds.value.from, { day: "numeric", month: "short" })} — ${dateLabel(bounds.value.to, { day: "numeric", month: "short", year: "numeric" })}`;
});
const selected = ref(null),
  action = ref(""),
  scope = ref("one"),
  description = ref(""),
  occurredAt = ref(""),
  notice = ref("");
function setView(value) {
  anchor.value = selectedDate.value;
  if (value === "list") {
    range.value = {
      from: monthStart(anchor.value),
      to: addDays(shiftMonth(anchor.value, 1), -1),
    };
    rangeDraft.value = { ...range.value };
  }
  view.value = value;
}
function move(direction) {
  if (view.value === "month" || view.value === "list") {
    anchor.value = shiftMonth(
      view.value === "list" ? range.value.from : anchor.value,
      direction,
    );
    if (view.value === "list") {
      range.value = {
        from: anchor.value,
        to: addDays(shiftMonth(anchor.value, 1), -1),
      };
      rangeDraft.value = { ...range.value };
    }
  } else
    anchor.value = addDays(
      anchor.value,
      direction * (view.value === "week" ? 7 : 1),
    );
  selectedDate.value = anchor.value;
}
function jump(date) {
  if (!date) return;
  anchor.value = date;
  selectedDate.value = date;
  if (view.value === "list") {
    range.value = {
      from: monthStart(date),
      to: addDays(shiftMonth(date, 1), -1),
    };
    rangeDraft.value = { ...range.value };
  }
}
function goToday() {
  jump(today.value);
}
function showDay(date) {
  anchor.value = date;
  selectedDate.value = date;
  view.value = "day";
}
function applyRange() {
  range.value = { ...rangeDraft.value };
}
async function load(page = 1) {
  try {
    await store.loadAgenda(
      {
        ...bounds.value,
        ...filters.value,
        page,
        per_page: view.value === "list" ? 50 : 100,
      },
      { allPages: view.value !== "list" },
    );
  } catch {}
}
function executor(id) {
  return (
    executors.value.find((m) => m.id === id)?.name ||
    (id ? `Participante ${id}` : "")
  );
}
function format(value) {
  return new Date(value).toLocaleString("pt-BR", {
    timeZone: zone.value,
    dateStyle: "short",
    timeStyle: "short",
  });
}
function open(item, type) {
  selected.value = item;
  action.value = type;
  scope.value = "one";
  description.value = "";
  store.error = "";
  const d = new Date();
  occurredAt.value = new Date(d.getTime() - d.getTimezoneOffset() * 60000)
    .toISOString()
    .slice(0, 16);
}
async function submit() {
  if (store.pending || !selected.value) return;
  try {
    const ok =
      action.value === "execute"
        ? await store.executeOccurrence(selected.value.id, {
            description: description.value,
            occurred_at: new Date(occurredAt.value).toISOString(),
          })
        : await store.cancelOccurrence(selected.value.id, scope.value);
    if (!ok) return;
    notice.value =
      action.value === "execute"
        ? "Execução registrada."
        : "Proposta enviada. Consulte as decisões na Central.";
    selected.value = null;
    if (action.value === "cancel") {
      await router.push({
        name: "decisions",
        query: { type: "entry", proposal: ok.proposals[0].id },
      });
      return;
    }
    await load();
  } catch {}
}
watch(
  () => auth.currentTenant,
  () => {
    selected.value = null;
    notice.value = "";
    anchor.value = today.value;
    selectedDate.value = today.value;
    filters.value = { kind: "", executor: "", status: "scheduled" };
    range.value = {
      from: monthStart(today.value),
      to: addDays(shiftMonth(today.value, 1), -1),
    };
    rangeDraft.value = { ...range.value };
  },
);
watch(
  () => [
    bounds.value.from,
    bounds.value.to,
    view.value,
    filters.value.kind,
    filters.value.executor,
    filters.value.status,
    auth.currentTenant,
  ],
  () => load(),
  { immediate: true },
);
</script>
