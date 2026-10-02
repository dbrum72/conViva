<template>
  <AppDialog
    :open="open"
    :title="canManage ? 'Minha disponibilidade' : 'Disponibilidade do grupo'"
    :busy="!!store.pending"
    @close="$emit('close')"
  >
    <div class="availability-content">
      <p class="care-muted availability-context">
        <strong>{{ recipientName }}</strong> · {{ groupName }}
      </p>
      <p v-if="canManage" class="care-muted availability-notice">
        Informe um afastamento ou período em que não poderá prestar cuidados.
        Durante esse período, sua execução ficará impedida e ninguém poderá
        incluir uma responsabilidade compartilhada com você. Vale somente para
        este assistido/grupo. Cuidados já aprovados ficam preservados.
      </p>
      <p v-else class="care-muted">
        Consulte os períodos e motivos de indisponibilidade dos participantes
        deste assistido. Apenas o próprio autor pode cancelar seu afastamento.
      </p>
      <form v-if="canManage" class="availability-form" @submit.prevent="save">
        <fieldset class="availability-fields" :disabled="!!store.pending">
          <legend>Novo período de indisponibilidade</legend>
          <div class="availability-dates">
            <label class="care-field"
              >Início<input v-model="start" type="date" required
            /></label>
            <label class="care-field"
              >Término<input
                v-model="end"
                type="date"
                :min="start || undefined"
                required
            /></label>
          </div>
          <p class="care-muted availability-timezone">
            O bloqueio abrange dias completos, incluindo as datas de início e
            término, no fuso {{ timezone }}.
          </p>
          <label class="care-field"
            >Motivo da indisponibilidade (opcional)<textarea
              v-model="reason"
              rows="3"
              maxlength="2000"
            />
          </label>
          <small class="care-muted availability-hint"
            >Visível para todos com permissão de consulta a este assistido. Até
            2.000 caracteres.</small
          >
          <div class="availability-submit">
            <AppButton type="submit" :disabled="!!store.pending"
              >Informar indisponibilidade</AppButton
            >
          </div>
        </fieldset>
      </form>
      <p
        v-if="store.unavailabilityError"
        role="alert"
        class="availability-error"
      >
        {{ store.unavailabilityError }}
      </p>
      <p v-if="saved" role="status" class="availability-success">
        Indisponibilidade registrada para este assistido.
      </p>
      <section class="availability-history" aria-label="Afastamentos do grupo">
        <h3>Afastamentos do grupo</h3>
        <ul v-if="store.unavailabilities.length" class="availability-list">
          <li
            v-for="period in store.unavailabilities"
            :key="period.id"
            class="availability-period"
          >
            <div class="availability-period-heading">
              <strong>{{ period.user?.name }}</strong>
              <span class="availability-badge">{{
                period.cancelled_at ? "Cancelado" : "Informado"
              }}</span>
            </div>
            <p class="availability-range">
              {{ dateLabel(period.starts_at, { dateStyle: "short" }) }} até
              {{ dateLabel(period.ends_at, { dateStyle: "short" }) }}
            </p>
            <small class="care-muted">Fuso: {{ period.timezone }}</small>
            <p v-if="period.reason" class="care-pre availability-reason">
              Motivo: {{ period.reason }}
            </p>
            <p v-if="period.cancelled_at" class="care-muted">
              Cancelado em {{ stamp(period.cancelled_at, period.timezone) }}
            </p>
            <AppButton
              v-else-if="period.can_cancel"
              variant="outline"
              :disabled="!!store.pending"
              @click="cancelling = period.id"
              >Cancelar período</AppButton
            >
          </li>
        </ul>
        <p
          v-else-if="!store.pending && !store.unavailabilityError"
          class="care-muted availability-empty"
        >
          Nenhum período informado neste grupo.
        </p>
        <div
          v-if="store.unavailabilityPage?.last_page > 1"
          class="care-actions"
        >
          <AppButton
            :disabled="
              !!store.pending || store.unavailabilityPage.current_page <= 1
            "
            @click="load(store.unavailabilityPage.current_page - 1)"
            >Anterior</AppButton
          >
          <span
            >Página {{ store.unavailabilityPage.current_page }} de
            {{ store.unavailabilityPage.last_page }}</span
          >
          <AppButton
            :disabled="
              !!store.pending ||
              store.unavailabilityPage.current_page >=
                store.unavailabilityPage.last_page
            "
            @click="load(store.unavailabilityPage.current_page + 1)"
            >Próxima</AppButton
          >
        </div>
      </section>
    </div>
    <AppConfirmDialog
      :open="cancelling !== null"
      title="Cancelar minha indisponibilidade"
      message="O período deixará de bloquear cuidados. Seu histórico será preservado e as permissões atuais continuarão valendo."
      :loading="!!store.pending"
      :error="store.unavailabilityError"
      @cancel="cancelling = null"
      @confirm="cancel"
    />
  </AppDialog>
</template>
<script setup>
import { ref, watch } from "vue";
import { AppDialog, AppButton, AppConfirmDialog } from "@/components/ui";
import { useCareStore } from "@/state/care";
import { dateLabel } from "@/utils/agenda";
const props = defineProps({
  open: Boolean,
  canManage: { type: Boolean, default: true },
  recipientId: [Number, String],
  recipientName: String,
  groupName: String,
  timezone: String,
});
const emit = defineEmits(["close", "changed"]);
const store = useCareStore();
const start = ref(""),
  end = ref(""),
  reason = ref(""),
  saved = ref(false),
  cancelling = ref(null);
function stamp(value, timezone) {
  return new Date(value).toLocaleString("pt-BR", {
    timeZone: timezone,
    dateStyle: "short",
    timeStyle: "short",
  });
}
async function load(page = 1) {
  await store.loadUnavailabilities(props.recipientId, page).catch(() => {});
}
async function save() {
  saved.value = false;
  try {
    const result = await store.saveUnavailability(props.recipientId, {
      starts_at: start.value,
      ends_at: end.value,
      timezone: props.timezone,
      reason: reason.value.trim() || null,
    });
    if (!result) return;
    start.value = end.value = reason.value = "";
    saved.value = true;
    emit("changed");
  } catch {}
}
async function cancel() {
  try {
    const result = await store.cancelUnavailability(
      props.recipientId,
      cancelling.value,
    );
    if (result) {
      cancelling.value = null;
      emit("changed");
    }
  } catch {}
}
watch(
  () => [props.open, props.recipientId],
  () => {
    start.value = end.value = reason.value = "";
    saved.value = false;
    cancelling.value = null;
    if (props.open && props.recipientId) load();
  },
  { immediate: true },
);
</script>
<style scoped>
.availability-content {
  display: grid;
  gap: 1.25rem;
}
.availability-content p {
  margin: 0;
  line-height: 1.6;
}
.availability-context {
  padding-bottom: 0.85rem;
  border-bottom: 1px solid var(--color-divider);
}
.availability-context strong {
  color: var(--color-text);
}
.availability-notice {
  padding: 0.85rem 1rem;
  border-radius: var(--radius-md);
  background: var(--color-info-soft);
}
.availability-fields {
  display: grid;
  gap: 1rem;
  min-width: 0;
  margin: 0;
  padding: 1.1rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
}
.availability-fields legend {
  padding: 0 0.4rem;
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--color-text);
}
.availability-dates {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}
.availability-fields .care-field {
  display: grid;
  gap: 0.45rem;
  min-width: 0;
  font-size: 0.9rem;
}
.availability-fields input,
.availability-fields textarea {
  width: 100%;
  min-width: 0;
  box-sizing: border-box;
}
.availability-fields textarea {
  resize: vertical;
  min-height: 6rem;
}
.availability-timezone,
.availability-hint {
  font-size: 0.8rem;
  line-height: 1.5;
}
.availability-submit {
  display: flex;
  justify-content: flex-end;
  padding-top: 0.25rem;
}
.availability-history {
  display: grid;
  gap: 0.85rem;
  padding-top: 0.25rem;
}
.availability-history h3 {
  margin: 0;
  font-size: 1.05rem;
  color: var(--color-text);
}
.availability-list {
  display: grid;
  gap: 0.75rem;
  margin: 0;
  padding: 0;
  list-style: none;
}
.availability-period {
  display: grid;
  gap: 0.5rem;
  padding: 1rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-surface-soft);
  overflow-wrap: anywhere;
}
.availability-period-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
}
.availability-badge {
  padding: 0.2rem 0.6rem;
  border-radius: var(--radius-md);
  background: var(--color-surface);
  color: var(--color-text-muted);
  font-size: 0.75rem;
}
.availability-range {
  font-size: 0.9rem;
  font-variant-numeric: tabular-nums;
}
.availability-reason {
  padding-top: 0.6rem;
  border-top: 1px solid var(--color-border);
  font-size: 0.9rem;
}
.availability-period :deep(.btn) {
  justify-self: start;
  margin-top: 0.25rem;
}
.availability-empty {
  padding: 1rem;
  text-align: center;
  border: 1px dashed var(--color-border);
  border-radius: var(--radius-md);
}
.availability-error,
.availability-success {
  padding: 0.75rem 1rem;
  border-radius: var(--radius-md);
}
.availability-error {
  background: var(--color-danger-soft);
  color: var(--color-danger);
}
.availability-success {
  background: var(--color-success-soft);
  color: var(--color-success);
}
@media (max-width: 540px) {
  .availability-dates {
    grid-template-columns: minmax(0, 1fr);
  }
  .availability-submit :deep(button) {
    width: 100%;
  }
  .availability-fields {
    padding: 0.85rem;
  }
}
</style>
