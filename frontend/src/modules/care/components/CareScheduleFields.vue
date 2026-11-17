<template>
  <AppFieldset class="care-form">
    <template #legend>Programação com fuso</template>
    <AppCheckbox
      :label="
        kind === 'medication'
          ? 'Programar doses'
          : kind === 'vaccine'
            ? 'Programar aplicação'
            : 'Definir série ou data isolada com fuso'
      "
      :id="fieldId + '-1'"
      :model-value="!!modelValue"
      @update:model-value="toggle($event)"
    />
    <template v-if="modelValue">
      <AppSelect
        v-if="kind !== 'vaccine'"
        label="Repetição"
        :id="fieldId + '-2'"
        :model-value="modelValue.frequency"
        @update:model-value="update('frequency', $event)"
        :options="[
          { value: 'once', label: 'Data isolada' },
          { value: 'daily', label: 'Diária' },
          { value: 'weekly', label: 'Semanal' },
        ]"
      />
      <AppInput
        :label="
          kind === 'medication'
            ? 'Primeira dose no fuso informado'
            : 'Início no fuso informado'
        "
        :id="fieldId + '-3'"
        type="datetime-local"
        required
        :model-value="modelValue.local_start"
        @update:model-value="update('local_start', $event)"
      />
      <AppFieldset
        v-if="kind === 'medication' && modelValue.frequency !== 'once'"
      >
        <template #legend>Outros horários de dose nos mesmos dias</template>
        <div
          v-for="(time, index) in modelValue.times || []"
          :key="index"
          class="care-actions"
        >
          <AppInput
            :label="`Horário ${index + 2}`"
            :id="fieldId + '-4' + '-' + index"
            type="time"
            required
            :model-value="time"
            :min="modelValue.local_start?.slice(11) || undefined"
            @update:model-value="setTime(index, $event)"
          />
          <AppButton
            variant="outline"
            @click="
              update(
                'times',
                modelValue.times.filter((_, i) => i !== index),
              )
            "
          >
            Remover horário
          </AppButton>
        </div>
        <AppButton
          v-if="(modelValue.times?.length || 0) < 23"
          variant="outline"
          @click="update('times', [...(modelValue.times || []), ''])"
        >
          Adicionar horário
        </AppButton>
      </AppFieldset>
      <AppInput
        label="Fuso horário"
        :id="fieldId + '-5'"
        required
        :model-value="modelValue.timezone"
        :suggestions="timezoneSuggestions"
        @update:model-value="update('timezone', $event)"
      />
      <AppDate
        v-if="modelValue.frequency !== 'once'"
        label="Última data (inclusive)"
        :id="fieldId + '-6'"
        required
        :model-value="modelValue.until"
        @update:model-value="update('until', $event)"
        :show-icon="false"
      />
      <AppInput
        label="Duração em minutos"
        :id="fieldId + '-7'"
        type="number"
        min="0"
        max="1440"
        required
        :model-value="modelValue.duration_minutes"
        @update:model-value="update('duration_minutes', Number($event))"
      />
      <AppFieldset v-if="modelValue.frequency === 'weekly'">
        <template #legend>Dias da semana</template>
        <AppCheckbox
          v-for="(day, index) in days"
          :key="day"
          :label="day"
          :id="fieldId + '-8' + '-' + index"
          :model-value="modelValue.weekdays.includes(index + 1)"
          @update:model-value="weekday(index + 1, $event)"
        />
      </AppFieldset>
      <p class="care-muted">
        O aceite autoriza esta programação até a última data. Alterar horário,
        fuso ou repetição exige nova proposta. Horários inexistentes na mudança
        de horário de verão são omitidos; horários repetidos geram uma única
        ocorrência.
      </p>
    </template>
  </AppFieldset>
</template>
<script setup>
import {
  AppFieldset,
  AppCheckbox,
  AppSelect,
  AppInput,
  AppDate,
} from "@/components/forms";
import { useId } from "vue";
const fieldId = useId();
import { AppButton } from "@/components/ui";
const props = defineProps({
  modelValue: Object,
  kind: String,
  timezone: { type: String, default: "America/Sao_Paulo" },
});
const emit = defineEmits(["update:modelValue"]);
const timezoneSuggestions = [
  "America/Sao_Paulo",
  "America/Manaus",
  "America/Rio_Branco",
  "America/Noronha",
  "Europe/Lisbon",
  "UTC",
];
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
function setTime(index, value) {
  const times = [...props.modelValue.times];
  times[index] = value;
  update("times", times);
}
function toggle(enabled) {
  emit(
    "update:modelValue",
    enabled
      ? {
          frequency: props.kind === "vaccine" ? "once" : "daily",
          timezone: props.timezone,
          local_start: "",
          until: "",
          duration_minutes: ["vaccine", "medication"].includes(props.kind)
            ? 0
            : 30,
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
