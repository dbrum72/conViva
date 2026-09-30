<template>
  <span class="decision-status-badge" :class="`decision-status-badge--${status}`">
    {{ labels[status] || status }}
  </span>
</template>

<script setup>
defineProps({ status: { type: String, required: true } });
const labels = {
  pending: "Aguardando aceite",
  accepted: "Aceita",
  rejected: "Recusada",
  withdrawn: "Retirada",
};
</script>

<style scoped>
.decision-status-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.65rem;
  border-radius: 999px;
  background: var(--color-surface-muted);
  color: var(--color-text-soft);
  font-size: 0.8125rem;
  font-weight: 600;
  line-height: 1.5;
  vertical-align: middle;
}
.decision-status-badge--pending {
  background: var(--color-warning);
  color: var(--color-on-warning);
}
.decision-status-badge--accepted {
  background: var(--color-accent);
  color: var(--color-on-accent);
}
.decision-status-badge--rejected {
  background: var(--color-info-soft);
  color: var(--color-brand-active);
}
</style>
