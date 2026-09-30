<template>
  <CareShell
    title="Despesas compartilhadas"
    subtitle="Cadastre despesas, acompanhe os aceites, o rateio e os pagamentos do assistido neste grupo."
    @retry="load"
  >
    <template #actions>
      <AppButton
        v-if="recipient?.can_create"
        variant="modal"
        :disabled="!!store.pending"
        @click="edit()"
        >Nova despesa</AppButton
      >
    </template>
    <p v-if="recipient" class="care-muted">{{ recipient.name }}</p>
    <form class="finance-filters" @submit.prevent="applyPeriod">
      <label class="care-field" for="finance-period"
        >Período
        <select
          id="finance-period"
          v-model="period"
          :disabled="!!store.pending"
          @change="changePeriod"
        >
          <option value="month">Este mês</option>
          <option value="today">Hoje</option>
          <option value="week">Esta semana</option>
          <option value="last_7">Últimos 7 dias</option>
          <option value="last_30">Últimos 30 dias</option>
          <option value="last_month">Mês anterior</option>
          <option value="year">Este ano</option>
          <option value="all">Todo o período</option>
          <option value="custom">Personalizado</option>
        </select>
      </label>
      <template v-if="period === 'custom'">
        <label class="care-field" for="finance-from"
          >De<input
            id="finance-from"
            type="date"
            v-model="from"
            required
            :disabled="!!store.pending"
        /></label>
        <label class="care-field" for="finance-to"
          >Até<input
            id="finance-to"
            type="date"
            v-model="to"
            :min="from"
            required
            :disabled="!!store.pending"
        /></label>
        <AppButton type="submit" variant="outline" :disabled="!!store.pending"
          >Consultar</AppButton
        >
      </template>
    </form>
    <p v-if="store.financePeriod" class="care-muted">
      {{
        store.financePeriod.from
          ? `Consulta de ${formatDate(store.financePeriod.from)} até ${formatDate(store.financePeriod.to)}`
          : "Consulta de todo o período"
      }}
      · {{ store.financePeriod.timezone }}. Pela data da despesa; quando não
      informada, pela data de cadastro. Os pagamentos apresentados correspondem
      às despesas deste período.
    </p>
    <div v-if="store.financeSummary" class="finance-summary">
      <AppCard title="Despesas confirmadas"
        ><strong class="care-metric">{{
          money(store.financeSummary.confirmed_cents)
        }}</strong></AppCard
      >
      <AppCard title="Pagamentos registrados"
        ><strong class="care-metric">{{
          money(store.financeSummary.paid_cents)
        }}</strong></AppCard
      >
      <AppCard title="A pagar"
        ><strong class="care-metric">{{
          money(store.financeSummary.unpaid_cents)
        }}</strong></AppCard
      >
    </div>
    <p v-if="store.financeSummary" class="care-muted">
      Os totais incluem somente despesas confirmadas. Propostas ainda não
      aceitas e despesas canceladas não compõem o saldo. Pagamentos informados
      não são confirmação bancária.
    </p>
    <section
      v-for="section in sections"
      :key="section.key"
      class="finance-section"
    >
      <template v-if="section.entries.length">
        <h2>{{ section.label }}</h2>
        <ExpenseCard
          v-for="entry in section.entries"
          :key="entry.id"
          :entry="entry"
          :user-id="auth.user?.id"
          :busy="!!store.pending"
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
      v-if="!store.pending && !store.error && !store.expenses.length"
      class="care-empty"
    >
      {{
        recipient
          ? "Nenhuma despesa encontrada no período consultado."
          : "Nenhum assistido com acesso financeiro neste grupo."
      }}
    </p>
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
      :loading="!!store.pending"
      :error="store.error"
      @cancel="cancellation = null"
      @confirm="cancel"
    />
  </CareShell>
</template>
<script setup>
import { ref, computed, watch } from "vue";
import CareShell from "@/components/care/CareShell.vue";
import ExpenseCard from "@/components/care/ExpenseCard.vue";
import CareEntryDialog from "@/components/care/CareEntryDialog.vue";
import CarePaymentDialog from "@/components/care/CarePaymentDialog.vue";
import { AppCard, AppButton, AppConfirmDialog } from "@/components/ui";
import { useCareStore } from "@/state/care";
import { useAuthStore } from "@/state/auth";
import { useOrganizationMembersStore } from "@/state/organization-members";
import { money } from "@/utils/care";
const store = useCareStore(),
  auth = useAuthStore(),
  team = useOrganizationMembersStore();
const entryOpen = ref(false),
  selectedEntry = ref(null),
  payment = ref(null),
  cancellation = ref(null);
const period = ref("month"),
  from = ref(""),
  to = ref("");
const appliedPeriod = ref({ period: "month" });
function formatDate(value) {
  return value?.split("-").reverse().join("/");
}
function changePeriod() {
  if (period.value === "custom") {
    from.value = store.financePeriod?.from || "";
    to.value = store.financePeriod?.to || "";
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
  load();
}
const recipient = computed(() => store.financeRecipients[0]);
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
    entries: store.expenses.filter((e) => e.finance_group === section.key),
  })),
);
async function load() {
  await store.loadExpenses(appliedPeriod.value).catch(() => {});
}
function edit(entry = null) {
  store.error = "";
  selectedEntry.value = entry;
  entryOpen.value = true;
}
async function cancel() {
  if (!cancellation.value || store.pending) return;
  const entry = cancellation.value,
    tenant = auth.currentTenant;
  try {
    await store.removeEntry(entry.care_recipient_id, entry.id);
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
.finance-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: end;
}
.finance-filters .care-field {
  flex: 1 1 12rem;
  min-width: 0;
  margin: 0;
}
.finance-filters input,
.finance-filters select,
.finance-filters :deep(.btn) {
  box-sizing: border-box;
  height: 3rem;
  min-height: 3rem;
  margin: 0;
}
.finance-summary {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem;
}
.finance-section {
  display: grid;
  gap: 1rem;
}
.finance-section:empty {
  display: none;
}
@media (max-width: 700px) {
  .finance-summary {
    grid-template-columns: 1fr;
  }
}
</style>
