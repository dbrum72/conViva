<template>
  <AppCard>
    <div class="care-list-row">
      <div>
        <h3>{{ entry.title }}</h3>
        <p class="care-muted">
          {{ entry.recipient.name }} · {{ entry.author?.name }} ·
          {{ dateTime(entry.due_at) }}
        </p>
      </div>
      <span
        class="care-pill"
        :class="
          entry.status === 'completed'
            ? 'care-payment-paid'
            : 'care-payment-pending'
        "
        >{{ labels[entry.status] || entry.status }}</span
      >
    </div>
    <p class="care-pre">{{ entry.description }}</p>
    <strong>{{ money(entry.amount_cents) }}</strong>
    <div v-for="share in entry.shares" :key="share.id" class="care-list-row">
      <div>
        <p>{{ share.user?.name }} · {{ money(share.amount_cents) }}</p>
        <span
          class="care-pill"
          :class="share.paid_at ? 'care-payment-paid' : 'care-payment-pending'"
          >{{
            share.paid_at
              ? "Pago"
              : entry.finance_group === "confirmed"
                ? "Pagamento pendente"
                : "Sem pagamento registrado"
          }}</span
        >
        <p v-if="share.paid_at" class="care-muted">
          Pagamento registrado em {{ dateTime(share.paid_at) }}
        </p>
      </div>
      <div class="care-actions">
        <AppButton
          v-if="share.can_pay"
          variant="modal"
          :disabled="busy"
          @click="$emit('pay', share)"
          >Registrar pagamento</AppButton
        >
        <AppButton
          v-if="share.paid_at && share.can_attach_receipt"
          variant="modal"
          :disabled="busy"
          @click="$emit('pay', share)"
          >Anexar comprovante</AppButton
        >
        <AppButton
          v-if="share.receipt"
          variant="outline"
          :disabled="busy"
          @click="$emit('download', share)"
          >Baixar comprovante · {{ share.receipt.filename }}</AppButton
        >
      </div>
    </div>
    <CareDecisions :entry="entry" :user-id="userId" />
    <div v-if="entry.can_change" class="care-actions">
      <AppButton variant="modal" :disabled="busy" @click="$emit('edit')"
        >Editar despesa</AppButton
      >
      <AppButton variant="outline" :disabled="busy" @click="$emit('cancel')"
        >Solicitar cancelamento</AppButton
      >
    </div>
  </AppCard>
</template>
<script setup>
import { AppCard, AppButton } from "@/components/ui";
import CareDecisions from "./CareDecisions.vue";
import { money, dateTime } from "@/utils/care";
defineProps({
  entry: { type: Object, required: true },
  busy: Boolean,
  userId: { type: Number, required: true },
});
defineEmits(["pay", "download", "edit", "cancel"]);
const labels = {
  pending: "Confirmada",
  completed: "Quitada",
  awaiting_approval: "Aguardando aceite",
  rejected: "Proposta recusada",
  withdrawn: "Proposta retirada",
  cancelled: "Cancelada",
};
</script>
