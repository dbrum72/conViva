<template>
  <CareShell
    :request-status="requestStatus"
    title="Despesas compartilhadas"
    subtitle="Cadastre despesas, acompanhe os aceites, o rateio e os pagamentos do assistido neste grupo."
    @retry="load"
  >
    <template #actions>
      <AppButton
        v-if="recipient?.can_create"
        variant="modal"
        :disabled="!!requestStatus.pending"
        @click="edit()"
        >Nova despesa</AppButton
      >
    </template>
    <p v-if="recipient" class="care-muted">{{ recipient.name }}</p>
    <AppForm class="expenses-filters" @submit.prevent="applyPeriod">
      <AppSelect
        label="Período"
        id="expenses-period"
        v-model="period"
        :disabled="!!requestStatus.pending"
        @update:model-value="changePeriod"
        :options="[
          { value: 'month', label: 'Este mês' },
          { value: 'today', label: 'Hoje' },
          { value: 'week', label: 'Esta semana' },
          { value: 'last_7', label: 'Últimos 7 dias' },
          { value: 'last_30', label: 'Últimos 30 dias' },
          { value: 'last_month', label: 'Mês anterior' },
          { value: 'year', label: 'Este ano' },
          { value: 'all', label: 'Todo o período' },
          { value: 'custom', label: 'Personalizado' },
        ]"
      />
      <template v-if="period === 'custom'">
        <AppDate
          label="De"
          id="expenses-from"
          v-model="from"
          required
          :disabled="!!requestStatus.pending"
          :show-icon="false"
        />
        <AppDate
          label="Até"
          id="expenses-to"
          v-model="to"
          :min="from"
          required
          :disabled="!!requestStatus.pending"
          :show-icon="false"
        />
        <AppButton
          type="submit"
          variant="outline"
          :disabled="!!requestStatus.pending"
          >Consultar</AppButton
        >
      </template>
      <AppSelect
        id="expenses-category"
        v-model="category"
        label="Categoria"
        :disabled="!!requestStatus.pending"
        :options="categoryOptions"
        @update:model-value="changeFilters"
      />
      <AppSelect
        id="expenses-participant"
        v-model="participant"
        label="Participante"
        :disabled="!!requestStatus.pending"
        :options="participantOptions"
        @update:model-value="changeFilters"
      />
      <AppSelect
        id="expenses-status"
        v-model="status"
        label="Situação"
        :disabled="!!requestStatus.pending"
        :options="statusOptions"
        @update:model-value="changeFilters"
      />
    </AppForm>
    <p v-if="store.expensesPeriod" class="care-muted">
      {{
        store.expensesPeriod.from
          ? `Consulta de ${formatDate(store.expensesPeriod.from)} até ${formatDate(store.expensesPeriod.to)}`
          : "Consulta de todo o período"
      }}
      · {{ store.expensesPeriod.timezone }}. Pela data da despesa; quando não
      informada, pela data de cadastro. Os pagamentos apresentados correspondem
      às despesas deste período.
    </p>
    <div v-if="store.expensesSummary" class="expenses-summary">
      <AppCard title="Despesas confirmadas"
        ><strong class="care-metric">{{
          money(store.expensesSummary.confirmed_cents)
        }}</strong></AppCard
      >
      <AppCard title="Pagamentos declarados"
        ><strong class="care-metric">{{
          money(store.expensesSummary.paid_cents)
        }}</strong></AppCard
      >
      <AppCard title="A pagar"
        ><strong class="care-metric">{{
          money(store.expensesSummary.unpaid_cents)
        }}</strong></AppCard
      >
    </div>
    <p v-if="store.expensesSummary" class="care-muted">
      Os totais incluem somente despesas confirmadas. Propostas ainda não
      aceitas e despesas canceladas ficam fora do saldo. Os pagamentos são
      declarações dos participantes. O filtro por participante inclui o valor
      integral das despesas encontradas.
    </p>
    <section
      v-for="section in sections"
      :key="section.key"
      class="expenses-section"
    >
      <template v-if="section.entries.length">
        <h2>{{ section.label }}</h2>
        <ExpenseCard
          v-for="entry in section.entries"
          :key="entry.id"
          :entry="entry"
          :user-id="auth.user?.id"
          :busy="!!requestStatus.pending"
          @edit="edit(entry)"
          @cancel="cancellation = entry"
          @pay="payment = { entry, share: $event }"
          @download="
            store
              .downloadPaymentReceipt(entry.care_recipient_id, entry.id, $event)
              .catch(() => {})
          "
        />
      </template>
    </section>
    <p
      v-if="
        !requestStatus.pending && !requestStatus.error && !store.expenses.length
      "
      class="care-empty"
    >
      {{
        recipient
          ? "Nenhuma despesa encontrada com os filtros selecionados."
          : "Nenhum assistido com acesso financeiro neste grupo."
      }}
    </p>
    <nav
      v-if="store.expensesPagination?.last_page > 1"
      class="care-pagination"
      aria-label="Páginas de despesas"
    >
      <AppButton
        :disabled="!!requestStatus.pending || page === 1"
        @click="load(page - 1)"
      >
        Anterior
      </AppButton>
      <span>Página {{ page }} de {{ store.expensesPagination.last_page }}</span>
      <AppButton
        :disabled="
          !!requestStatus.pending || page >= store.expensesPagination.last_page
        "
        @click="load(page + 1)"
      >
        Próxima
      </AppButton>
    </nav>
    <CareEntryDialog
      :open="entryOpen"
      :entry="selectedEntry"
      :recipient-id="recipient?.id"
      :members="team.activeMembers"
      :available-kinds="['expense']"
      @close="entryOpen = false"
      @saved="load"
    />
    <CarePaymentDialog
      :payment="payment"
      :recipient-id="payment?.entry.care_recipient_id"
      @close="payment = null"
      @saved="load"
    />
    <AppConfirmDialog
      :open="!!cancellation"
      title="Solicitar cancelamento da despesa"
      message="O cancelamento depende dos aceites necessários. O histórico será preservado."
      :loading="!!requestStatus.pending"
      :error="requestStatus.error"
      @cancel="cancellation = null"
      @confirm="cancel"
    />
  </CareShell>
</template>
<script setup>
import { AppForm, AppSelect, AppDate } from "@/components/forms";
import { ref, computed, watch } from "vue";
import { CareEntryDialog, CareShell } from "@/modules/care/ui.js";
import ExpenseCard from "../components/ExpenseCard.vue";
import CarePaymentDialog from "../components/CarePaymentDialog.vue";
import { AppCard, AppButton, AppConfirmDialog } from "@/components/ui";
import { money, useCareStore } from "@/modules/care";
import { useExpensesStore } from "../state/expenses.js";
import { useAuthStore } from "@/modules/auth";
import { useOrganizationMembersStore } from "@/modules/groups";
import { expenseStatusLabels } from "../utils/status.js";
const careStore = useCareStore();
const store = useExpensesStore(),
  auth = useAuthStore(),
  team = useOrganizationMembersStore();
const requestStatus = computed(() => ({
  pending: store.pending || careStore.pending,
  error: store.error || careStore.error,
}));
const entryOpen = ref(false),
  selectedEntry = ref(null),
  payment = ref(null),
  cancellation = ref(null);
const period = ref("month"),
  from = ref(""),
  to = ref("");
const appliedPeriod = ref({ period: "month" });
const category = ref(""),
  participant = ref(""),
  status = ref(""),
  page = ref(1);
const categoryOptions = computed(() => [
  { value: "", label: "Todas as categorias" },
  ...store.expensesCategories.map((value) => ({ value, label: value })),
]);
const participantOptions = computed(() => [
  { value: "", label: "Todos os participantes" },
  ...store.expensesParticipants.map(({ id, name }) => ({
    value: id,
    label: name,
  })),
]);
const statusOptions = [
  { value: "", label: "Todas as situações" },
  ...Object.entries(expenseStatusLabels).map(([value, label]) => ({
    value,
    label,
  })),
];
function formatDate(value) {
  return value?.split("-").reverse().join("/");
}
function changePeriod() {
  if (period.value === "custom") {
    from.value = store.expensesPeriod?.from || "";
    to.value = store.expensesPeriod?.to || "";
    return;
  }
  applyPeriod();
}
function applyPeriod() {
  if (
    period.value === "custom" &&
    (!from.value || !to.value || to.value < from.value)
  )
    return;
  appliedPeriod.value =
    period.value === "custom"
      ? { period: "custom", from: from.value, to: to.value }
      : { period: period.value };
  load(1);
}
function changeFilters() {
  load(1);
}
const recipient = computed(() => store.expensesRecipients[0]);
const sections = computed(() =>
  [
    { key: "confirmed", label: "Despesas confirmadas" },
    { key: "proposals", label: "Propostas aguardando aceite" },
    {
      key: "history",
      label: "Histórico de recusas, retiradas e cancelamentos",
    },
  ].map((section) => ({
    ...section,
    entries: store.expenses.filter((e) => e.expense_group === section.key),
  })),
);
async function load(next = page.value) {
  careStore.error = "";
  page.value = next;
  const filters = {
    ...appliedPeriod.value,
    ...(category.value && { category: category.value }),
    ...(participant.value && { participant: participant.value }),
    ...(status.value && { status: status.value }),
  };
  const pagination = await store
    .loadExpenses({ ...filters, ...(next > 1 && { page: next }) })
    .catch(() => {});
  if (pagination && next > pagination.last_page) {
    await load(pagination.last_page);
  }
}
function edit(entry = null) {
  store.error = "";
  careStore.error = "";
  selectedEntry.value = entry;
  entryOpen.value = true;
}
async function cancel() {
  if (!cancellation.value || requestStatus.value.pending) return;
  const entry = cancellation.value,
    tenant = auth.currentTenant;
  try {
    await careStore.removeEntry(entry.care_recipient_id, entry.id);
    if (auth.currentTenant !== tenant) return;
    cancellation.value = null;
    await load();
  } catch {}
}
watch(
  () => [auth.currentTenant, auth.user?.id],
  async () => {
    period.value = "month";
    appliedPeriod.value = { period: "month" };
    from.value = to.value = "";
    category.value = participant.value = status.value = "";
    page.value = 1;
    entryOpen.value = false;
    selectedEntry.value = payment.value = cancellation.value = null;
    const tenant = auth.currentTenant;
    if (!tenant) return;
    await load();
    if (
      auth.currentTenant === tenant &&
      auth.hasPermission("organization-members.view")
    )
      await team.fetchMembers().catch(() => {});
  },
  { immediate: true },
);
</script>
<style scoped>
.expenses-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: end;
}
.expenses-filters > .app-field {
  flex: 1 1 12rem;
  min-width: 0;
  margin: 0;
}
.expenses-filters > .btn {
  box-sizing: border-box;
  height: 3rem;
  min-height: 3rem;
  margin: 0;
}
.expenses-summary {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem;
}
.expenses-section {
  display: grid;
  gap: 1rem;
}
.expenses-section:empty {
  display: none;
}
@media (max-width: 700px) {
  .expenses-summary {
    grid-template-columns: 1fr;
  }
}
</style>
