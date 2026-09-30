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
        ><i aria-hidden="true" />{{ entryKinds[item.kind] }}</span
      ><span class="agenda-status">{{
        item.is_overdue
          ? "Vencido · sem execução registrada"
          : agendaStates[item.status]
      }}</span>
    </div>
    <p class="agenda-occurrence-time">
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
      {{ executor || "Sem executor designado" }}
    </p>
    <p v-if="item.timezone !== timezone" class="agenda-occurrence-note">
      Fuso da série: {{ item.timezone }}
    </p>
    <p v-if="item.conflict" class="agenda-occurrence-warning">
      Sobreposição com outro cuidado visível. Confira os horários.
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
    <div class="agenda-occurrence-actions">
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
