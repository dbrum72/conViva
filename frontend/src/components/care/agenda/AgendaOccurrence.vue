<template>
  <article
    class="agenda-occurrence"
    :class="[
      `agenda-kind-${item.kind}`,
      { 'agenda-is-overdue': item.is_overdue },
    ]"
  >
    <div class="agenda-occurrence-meta">
      <span class="agenda-kind-label"
        ><i aria-hidden="true" />{{
          item.kind === "unavailability"
            ? "Indisponibilidade"
            : entryKinds[item.kind]
        }}</span
      ><span v-if="item.kind !== 'unavailability'" class="agenda-status">{{
        item.is_overdue
          ? "Vencido · sem execução registrada"
          : agendaStates[item.status]
      }}</span>
    </div>
    <p v-if="item.kind === 'unavailability'" class="agenda-occurrence-time">
      {{ dateLabel(item.starts_at, { dateStyle: "short" }) }} —
      {{ dateLabel(item.ends_at, { dateStyle: "short" }) }} · Dias completos
    </p>
    <p v-else class="agenda-occurrence-time">
      {{ timeLabel(item.due_at, timezone) }} —
      {{ timeLabel(item.ends_at, timezone)
      }}<span
        v-if="
          dateKey(item.due_at, timezone) !== dateKey(item.ends_at, timezone)
        "
      >
        · até {{ dateLabel(dateKey(item.ends_at, timezone)) }}</span
      >
    </p>
    <h3>{{ item.title }}</h3>
    <p class="agenda-occurrence-executor">
      {{ item.participant_name || executor || "Sem executor designado" }}
    </p>
    <p v-if="item.kind === 'unavailability'" class="agenda-occurrence-note">
      Assistido: {{ item.recipient.name }}
    </p>
    <p
      v-if="item.kind === 'unavailability' && item.reason"
      class="agenda-occurrence-note care-pre"
    >
      Motivo: {{ item.reason }}
    </p>
    <p v-if="item.timezone !== timezone" class="agenda-occurrence-note">
      Fuso da série: {{ item.timezone }}
    </p>
    <p v-if="item.conflict" class="agenda-occurrence-warning">
      Sobreposição com outro cuidado visível. Confira os horários.
    </p>
    <p
      v-if="item.availability_conflict && !item.execution_block"
      class="agenda-occurrence-warning"
      role="status"
    >
      {{ item.availability_conflict }}
    </p>
    <p
      v-if="item.execution_block"
      class="agenda-occurrence-warning"
      role="status"
    >
      {{ item.execution_block }}
    </p>
    <p v-if="item.pending_change" class="agenda-occurrence-note">
      Alteração aguardando decisão; programação vigente preservada.
    </p>
    <p v-if="item.administratively_completed" class="agenda-occurrence-note">
      Cadastro encerrado. A execução de cada ocorrência é registrada
      separadamente.
    </p>
    <p v-if="item.execution" class="agenda-occurrence-note">
      Executado por {{ item.execution.author }} em
      {{ stamp(item.execution.occurred_at) }}. Registrado em
      {{ stamp(item.execution.recorded_at) }}.
    </p>
    <div
      v-if="item.kind !== 'unavailability'"
      class="agenda-occurrence-actions"
    >
      <RouterLink
        :to="
          item.kind === 'expense'
            ? { name: 'finance' }
            : { name: 'recipient', params: { id: item.care_recipient_id } }
        "
        >Ver {{ item.kind === "expense" ? "despesa" : "cuidado" }} de
        {{ item.recipient.name }} <span aria-hidden="true">↗</span></RouterLink
      ><button
        v-if="item.can_execute"
        type="button"
        :disabled="busy"
        @click="$emit('execute', item)"
      >
        Registrar execução</button
      ><button
        v-if="item.can_cancel"
        type="button"
        :disabled="busy"
        @click="$emit('cancel', item)"
      >
        Propor cancelamento
      </button>
    </div>
  </article>
</template>
<script setup>
import { entryKinds } from "@/utils/care";
import { agendaStates, dateKey, dateLabel, timeLabel } from "@/utils/agenda";
const props = defineProps({
  item: Object,
  timezone: String,
  executor: String,
  busy: Boolean,
});
defineEmits(["execute", "cancel"]);
function stamp(value) {
  return new Date(value).toLocaleString("pt-BR", {
    timeZone: props.timezone,
    dateStyle: "short",
    timeStyle: "short",
  });
}
</script>
