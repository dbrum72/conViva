<template>
  <AppDialog
    :open="!!payment"
    :title="
      payment?.share.paid_at ? 'Anexar comprovante' : 'Registrar pagamento'
    "
    :busy="!!store.pending"
    :error="store.error"
    @close="$emit('close')"
  >
    <form class="care-form" @submit.prevent="submit">
      <p>
        {{ payment?.entry.title }} · {{ money(payment?.share.amount_cents) }}
      </p>
      <p>
        Você registra somente o pagamento da sua parcela. Este registro não é
        uma confirmação bancária.
      </p>
      <AppFileUpload
        v-if="payment?.share.can_attach_receipt"
        id="payment-receipt"
        name="receipt"
        v-model="files"
        label="Recibo ou comprovante de pagamento"
        hint="Opcional ao registrar pagamento. PDF, JPG, PNG ou WebP, até 20 MB."
        accept=".pdf,.jpg,.jpeg,.png,.webp"
        :max-file-size="20 * 1024 * 1024"
        :disabled="!!store.pending"
        :required="!!payment?.share.paid_at"
        :error="fileError"
        @reject="fileError = 'Selecione um arquivo de até 20 MB.'"
        @update:model-value="fileError = ''"
      />
      <p v-else>
        Para anexar um comprovante, é necessário acesso de edição à área de
        documentos.
      </p>
      <div class="care-actions">
        <AppButton
          variant="cancel"
          :disabled="!!store.pending"
          @click="$emit('close')"
          >Cancelar</AppButton
        >
        <AppButton
          type="submit"
          variant="primary"
          :loading="!!store.pending"
          :disabled="
            !!store.pending || (!!payment?.share.paid_at && !files.length)
          "
          >{{
            payment?.share.paid_at
              ? "Salvar comprovante"
              : "Registrar pagamento"
          }}</AppButton
        >
      </div>
    </form>
  </AppDialog>
</template>
<script setup>
import { ref, watch } from "vue";
import { AppDialog, AppButton } from "@/components/ui";
import { AppFileUpload } from "@/components/forms";
import { useCareStore } from "@/state/care";
import { money } from "@/utils/care";
const props = defineProps({ payment: Object, recipientId: [Number, String] });
const emit = defineEmits(["close", "saved"]);
const store = useCareStore(),
  files = ref([]),
  fileError = ref("");
watch(
  () => props.payment,
  () => {
    files.value = [];
    fileError.value = "";
  },
);
async function submit() {
  if (
    store.pending ||
    !props.payment ||
    (props.payment.share.paid_at && !files.value.length)
  )
    return;
  try {
    await store.pay(
      props.recipientId,
      props.payment.entry.id,
      props.payment.share.id,
      files.value[0] ?? null,
    );
    emit("saved");
    emit("close");
  } catch {
    /* Keep the selected file and server error available for retry. */
  }
}
</script>
