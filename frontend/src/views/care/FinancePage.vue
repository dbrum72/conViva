<template>
  <CareShell
    title="Despesas compartilhadas"
    subtitle="Organize os gastos de cuidado e acompanhe os pagamentos registrados."
    @retry="store.loadExpenses().catch(() => {})"
    ><AppCard v-if="!store.pending && !store.error" title="Total registrado"
      ><strong class="care-metric">{{
        money(store.expenses.reduce((n, e) => n + e.amount_cents, 0))
      }}</strong></AppCard
    ><AppCard v-for="e in store.expenses" :key="e.id"
      ><div class="care-list-row">
        <div>
          <RouterLink
            :to="{ name: 'recipient', params: { id: e.recipient.id } }"
            ><h2>{{ e.title }}</h2></RouterLink
          >
          <p>{{ e.recipient.name }}</p>
        </div>
        <strong>{{ money(e.amount_cents) }}</strong>
      </div>
      <div v-for="s in e.shares" :key="s.id" class="care-list-row">
        <span>{{ s.user?.name }}</span
        ><span
          >{{ money(s.amount_cents) }} ·
          <span
            class="care-pill"
            :class="s.paid_at ? 'care-payment-paid' : 'care-payment-pending'"
            >{{ s.paid_at ? "Pago" : "Pendente" }}</span
          ></span
        >
      </div></AppCard
    >
    <p
      v-if="!store.expenses.length && !store.pending && !store.error"
      class="care-empty"
    >
      Nenhuma despesa disponível. Registre despesas na área de um assistido.
    </p></CareShell
  >
</template>
<script setup>
import { onMounted } from "vue";
import CareShell from "@/components/care/CareShell.vue";
import { AppCard } from "@/components/ui";
import { useCareStore } from "@/state/care";
import { money } from "@/utils/care";
const store = useCareStore();
onMounted(() => store.loadExpenses().catch(() => {}));
</script>
