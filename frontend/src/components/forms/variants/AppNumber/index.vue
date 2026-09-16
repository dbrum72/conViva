<template>
  <AppInput
    v-bind="inputProps"
    type="text"
    inputmode="decimal"
    :model-value="displayValue"
    @update:model-value="handleInput"
    @focus="handleFocus"
    @blur="handleBlur"
  >
    <template v-if="$slots.prepend" #prepend>
      <slot name="prepend" />
    </template>

    <template v-if="$slots.append" #append>
      <slot name="append" />
    </template>
  </AppInput>
</template>

<script setup>
import { computed, ref, watch } from "vue";

import AppInput from "@/components/forms/fields/AppInput/index.vue";
import {
  formatNumber,
  processNumber,
} from "@/components/forms/shared/number/index.js";
import { appNumberProps } from "./props.js";

const props = defineProps(appNumberProps);

const emit = defineEmits(["update:modelValue", "focus", "blur"]);

const isFocused = ref(false);
const draftValue = ref("");

const numberOptions = computed(() => ({
  decimalSeparator: ",",
  groupSeparator: ".",
  allowNegative: props.min === undefined || props.min < 0,
  precision: resolvePrecision(props.step),
  min: props.min,
  max: props.max,
  locale: "pt-BR",
  useGrouping: true,
}));

const displayValue = computed(() => {
  if (isFocused.value) {
    return draftValue.value;
  }

  return formatNumber(props.modelValue, {
    locale: numberOptions.value.locale,
    maximumFractionDigits: numberOptions.value.precision,
    useGrouping: numberOptions.value.useGrouping,
  });
});

const inputProps = computed(() => {
  const {
    modelValue,
    type,
    inputmode,
    min,
    max,
    step,
    allowEmpty,
    showIcon,
    ...forwardedProps
  } = props;

  return forwardedProps;
});

watch(
  () => props.modelValue,
  (value) => {
    if (!isFocused.value) {
      draftValue.value = formatNumber(value, {
        locale: numberOptions.value.locale,
        maximumFractionDigits: numberOptions.value.precision,
        useGrouping: false,
      });
    }
  },
  {
    immediate: true,
  },
);

function handleInput(value) {
  draftValue.value = value;

  const result = processNumber(value, numberOptions.value);

  if (result.parsed === null) {
    emit("update:modelValue", props.allowEmpty ? null : 0);

    return;
  }

  emit("update:modelValue", result.clamped);
}

function handleFocus(event) {
  isFocused.value = true;

  draftValue.value = formatNumber(props.modelValue, {
    locale: numberOptions.value.locale,
    maximumFractionDigits: numberOptions.value.precision,
    useGrouping: false,
  });

  emit("focus", event);
}

function handleBlur(event) {
  isFocused.value = false;

  const result = processNumber(draftValue.value, numberOptions.value);

  if (result.parsed === null) {
    const emptyValue = props.allowEmpty ? null : 0;

    emit("update:modelValue", emptyValue);
    draftValue.value = "";

    emit("blur", event);

    return;
  }

  emit("update:modelValue", result.clamped);

  draftValue.value = formatNumber(result.clamped, {
    locale: numberOptions.value.locale,
    maximumFractionDigits: numberOptions.value.precision,
    useGrouping: false,
  });

  emit("blur", event);
}

function resolvePrecision(step) {
  if (step === "any") {
    return 6;
  }

  const numericStep = Number(step);

  if (!Number.isFinite(numericStep)) {
    return 0;
  }

  const stepText = String(numericStep);
  const decimalIndex = stepText.indexOf(".");

  return decimalIndex === -1 ? 0 : stepText.length - decimalIndex - 1;
}
</script>

<style src="./style.css"></style>
