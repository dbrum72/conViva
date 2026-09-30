<template>
  <fieldset class="care-form">
    <legend>Programação com fuso</legend>
    <label class="care-check"
      ><input
        type="checkbox"
        :checked="!!modelValue"
        @change="toggle($event.target.checked)"
      />Definir série ou data isolada com fuso</label
    >
    <template v-if="modelValue">
      <label class="care-field"
        >Repetição<select
          :value="modelValue.frequency"
          @change="update('frequency', $event.target.value)"
        >
          <option value="once">Data isolada</option>
          <option value="daily">Diária</option>
          <option value="weekly">Semanal</option>
        </select></label
      >
      <label class="care-field"
        >Início no fuso informado<input
          type="datetime-local"
          required
          :value="modelValue.local_start"
          @input="update('local_start', $event.target.value)"
      /></label>
      <label class="care-field"
        >Fuso horário<input
          required
          :value="modelValue.timezone"
          list="care-timezones"
          @input="update('timezone', $event.target.value)"
      /></label>
      <datalist id="care-timezones">
        <option value="America/Sao_Paulo" />
        <option value="America/Manaus" />
        <option value="America/Rio_Branco" />
        <option value="America/Noronha" />
        <option value="Europe/Lisbon" />
        <option value="UTC" />
      </datalist>
      <label class="care-field"
        >Última data (inclusive)<input
          type="date"
          required
          :value="modelValue.until"
          @input="update('until', $event.target.value)"
      /></label>
      <label class="care-field"
        >Duração em minutos<input
          type="number"
          min="0"
          max="1440"
          required
          :value="modelValue.duration_minutes"
          @input="update('duration_minutes', Number($event.target.value))"
      /></label>
      <fieldset v-if="modelValue.frequency === 'weekly'">
        <legend>Dias da semana</legend>
        <label v-for="(day, index) in days" :key="day" class="care-check"
          ><input
            type="checkbox"
            :checked="modelValue.weekdays.includes(index + 1)"
            @change="weekday(index + 1, $event.target.checked)"
          />{{ day }}</label
        >
      </fieldset>
      <p class="care-muted">
        O aceite autoriza esta programação até a última data. Alterar horário,
        fuso ou repetição exige nova proposta. Horários inexistentes na mudança
        de horário de verão são omitidos; horários repetidos geram uma única
        ocorrência.
      </p>
    </template>
  </fieldset>
</template>
<script setup>
const props = defineProps({
  modelValue: Object,
  timezone: { type: String, default: "America/Sao_Paulo" },
});
const emit = defineEmits(["update:modelValue"]);
const days = [
  "Segunda",
  "Terça",
  "Quarta",
  "Quinta",
  "Sexta",
  "Sábado",
  "Domingo",
];
function update(field, value) {
  emit("update:modelValue", { ...props.modelValue, [field]: value });
}
function toggle(enabled) {
  emit(
    "update:modelValue",
    enabled
      ? {
          frequency: "daily",
          timezone: props.timezone,
          local_start: "",
          until: "",
          duration_minutes: 30,
          weekdays: [],
        }
      : null,
  );
}
function weekday(day, checked) {
  update(
    "weekdays",
    checked
      ? [...props.modelValue.weekdays, day]
      : props.modelValue.weekdays.filter((d) => d !== day),
  );
}
</script>
