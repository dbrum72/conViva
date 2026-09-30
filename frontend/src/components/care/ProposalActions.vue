<template>
  <div class="care-actions" :aria-label="`Ações para ${proposal.title}`">
    <AppButton
      v-if="proposal.can_accept"
      variant="action"
      :disabled="busy"
      @click="$emit('respond', { decision: 'accepted' })"
      >Aceitar proposta</AppButton
    >
    <AppButton
      v-if="proposal.can_reject && !rejecting"
      variant="danger"
      :disabled="busy"
      @click="rejecting = true"
      >Recusar proposta</AppButton
    >
    <AppButton
      v-if="proposal.can_withdraw"
      variant="outline"
      :disabled="busy"
      @click="$emit('withdraw')"
      >Retirar minha proposta</AppButton
    >
  </div>
  <form
    v-if="proposal.can_reject && rejecting"
    class="care-form"
    @submit.prevent="reject"
  >
    <label class="care-field"
      >Motivo da recusa<textarea
        v-model="reason"
        required
        maxlength="2000"
        rows="3"
        :disabled="busy"
      />
    </label>
    <p>
      A justificativa ficará registrada junto à sua decisão. A versão vigente
      será preservada.
    </p>
    <div class="care-actions">
      <AppButton
        type="submit"
        variant="danger"
        :disabled="busy || !reason.trim()"
        >Confirmar recusa</AppButton
      >
      <AppButton variant="cancel" :disabled="busy" @click="rejecting = false"
        >Cancelar</AppButton
      >
    </div>
  </form>
</template>
<script setup>
import { ref } from "vue";
import { AppButton } from "@/components/ui";
const props = defineProps({
  proposal: { type: Object, required: true },
  busy: Boolean,
});
const emit = defineEmits(["respond", "withdraw"]);
const rejecting = ref(false),
  reason = ref("");
function reject() {
  if (!props.busy && props.proposal.can_reject && reason.value.trim())
    emit("respond", { decision: "rejected", reason: reason.value.trim() });
}
</script>
