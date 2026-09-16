<template>
  <section
    v-if="entry.proposals?.length"
    class="care-decisions"
    aria-label="Propostas e decisões"
  >
    <details :open="entry.proposals[0]?.status === 'pending'">
      <summary>
        Propostas e decisões · {{ labels[entry.proposals[0]?.status] }}
      </summary>
      <article
        v-for="proposal in entry.proposals"
        :key="proposal.id"
        class="care-proposal"
      >
        <h4>
          Versão {{ proposal.version }} ·
          {{
            proposal.operation === "cancel"
              ? "Cancelamento"
              : "Registro / alteração"
          }}
          · {{ labels[proposal.status] }}
        </h4>
        <p v-if="proposal.status === 'pending'">
          {{
            entry.revision
              ? "A versão anterior continua válida enquanto esta proposta é analisada."
              : "Este cuidado ainda não está confirmado e não entra na agenda."
          }}
        </p>
        <dl v-if="proposal.operation === 'save'" class="care-details">
          <dt>Título proposto</dt>
          <dd>{{ proposal.payload.data.title }}</dd>
          <dt>Descrição</dt>
          <dd>{{ proposal.payload.data.description || "—" }}</dd>
          <dt>Quando</dt>
          <dd>{{ dateTime(proposal.payload.data.due_at) }}</dd>
          <dt v-if="proposal.payload.data.ends_at">Término</dt>
          <dd v-if="proposal.payload.data.ends_at">
            {{ dateTime(proposal.payload.data.ends_at) }}
          </dd>
          <dt v-if="proposal.payload.data.amount_cents">Valor</dt>
          <dd v-if="proposal.payload.data.amount_cents">
            {{ money(proposal.payload.data.amount_cents) }}
          </dd>
          <template
            v-for="(value, key) in proposal.payload.data.details"
            :key="key"
            ><dt>{{ detailLabels[key] || key }}</dt>
            <dd>{{ value || "—" }}</dd></template
          >
          <template
            v-for="share in proposal.payload.shares"
            :key="share.user_id"
            ><dt>
              Parcela ·
              {{
                share.user_id === userId
                  ? "Você"
                  : participantName(proposal, share.user_id)
              }}
            </dt>
            <dd>{{ money(share.amount_cents) }}</dd></template
          >
        </dl>
        <ul v-if="proposal.decisions.length">
          <li v-for="decision in proposal.decisions" :key="decision.id">
            <strong>{{ decision.user?.name }}</strong
            >: {{ labels[decision.status] }}
            <span v-if="decision.decided_at">
              · {{ dateTime(decision.decided_at) }}</span
            >
            <p v-if="decision.reason" class="care-pre">
              Motivo da recusa: {{ decision.reason }}
            </p>
          </li>
        </ul>
        <p v-else>Registro próprio, sem obrigação para outro participante.</p>
        <form
          v-if="
            canEdit &&
            proposal.status === 'pending' &&
            proposal.decisions.some(
              (d) => d.user_id === userId && d.status === 'pending',
            )
          "
          @submit.prevent="reject(proposal.id)"
          class="care-form"
        >
          <AppButton
            variant="action"
            type="button"
            :disabled="busy"
            @click="$emit('decide', proposal.id, { decision: 'accepted' })"
            >Aceitar proposta</AppButton
          >
          <label class="care-field"
            >Motivo da recusa<textarea
              v-model="reasons[proposal.id]"
              maxlength="2000"
              required
              rows="3"
            />
          </label>
          <AppButton
            variant="action"
            type="submit"
            :disabled="busy || !reasons[proposal.id]?.trim()"
            >Recusar com justificativa</AppButton
          >
        </form>
        <AppButton
          variant="action"
          v-if="
            canEdit &&
            entry.created_by === userId &&
            proposal.status === 'pending'
          "
          :disabled="busy"
          @click="$emit('withdraw', proposal.id)"
          >Retirar proposta</AppButton
        >
      </article>
    </details>
  </section>
</template>
<script setup>
import { reactive } from "vue";
import { AppButton } from "@/components/ui";
import { dateTime, money } from "@/utils/care";
const props = defineProps({
  entry: { type: Object, required: true },
  userId: { type: Number, required: true },
  canEdit: Boolean,
  busy: Boolean,
});
const emit = defineEmits(["decide", "withdraw"]);
const reasons = reactive({});
const labels = {
  pending: "Aguardando aceite",
  accepted: "Aceita",
  rejected: "Recusada",
  withdrawn: "Retirada",
};
const detailLabels = {
  dose: "Dose",
  frequency: "Frequência",
  route: "Via",
  food: "Alimento",
  quantity: "Quantidade",
  provider: "Profissional/local",
};
function participantName(proposal, id) {
  return (
    proposal.decisions.find((d) => d.user_id === id)?.user?.name ||
    props.entry.author?.name ||
    "Autor"
  );
}
function reject(id) {
  if (reasons[id]?.trim())
    emit("decide", id, { decision: "rejected", reason: reasons[id].trim() });
}
</script>
<style scoped>
.care-decisions {
  margin-block: 1rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: 1rem;
  background: var(--color-surface-soft);
}
summary {
  cursor: pointer;
  font-weight: 600;
  color: var(--color-brand);
}
.care-proposal {
  padding-block: 1rem;
  border-bottom: 1px solid var(--color-border);
}
.care-proposal:last-child {
  border-bottom: 0;
}
h4 {
  margin-block: 0 0.75rem;
}
ul {
  padding-left: 1.25rem;
}
li {
  margin-block: 0.5rem;
}
</style>
