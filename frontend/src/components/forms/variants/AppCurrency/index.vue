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
    <template v-if="showCurrency || $slots.prepend" #prepend>
      <slot name="prepend">
        <InputIcon v-if="showCurrency">
          <span aria-hidden="true">
            {{ currencySymbol }}
          </span>
        </InputIcon>
      </slot>
    </template>

    <template v-if="$slots.append" #append>
      <slot name="append" />
    </template>
  </AppInput>
</template>

<script setup>
import { computed, ref, watch } from "vue";

import AppInput from "@/components/forms/fields/AppInput/index.vue";
import InputIcon from "@/components/forms/internal/InputIcon/index.vue";
import {
  formatNumber,
  processNumber,
} from "@/components/forms/shared/number/index.js";
import { appCurrencyProps } from "./props.js";

const props = defineProps(appCurrencyProps);

const emit = defineEmits(["update:modelValue", "focus", "blur"]);

const isFocused = ref(false);
const draftValue = ref("");

const numberOptions = computed(() => ({
  decimalSeparator: resolveDecimalSeparator(props.locale),
  groupSeparator: resolveGroupSeparator(props.locale),
  allowNegative: props.allowNegative,
  precision: props.precision,
  min: props.min,
  max: props.max,
  locale: props.locale,
  useGrouping: props.useGrouping,
  minimumFractionDigits: props.precision,
  maximumFractionDigits: props.precision,
}));

const currencySymbol = computed(() => {
  if (!props.showCurrency) {
    return "";
  }

  try {
    const parts = new Intl.NumberFormat(props.locale, {
      style: "currency",
      currency: props.currency,
      currencyDisplay: "narrowSymbol",
    }).formatToParts(0);

    return (
      parts.find((part) => part.type === "currency")?.value ?? props.currency
    );
  } catch {
    return props.currency;
  }
});

const displayValue = computed(() => {
  if (isFocused.value) {
    return draftValue.value;
  }

  return formatNumber(props.modelValue, {
    locale: props.locale,
    minimumFractionDigits: props.precision,
    maximumFractionDigits: props.precision,
    useGrouping: props.useGrouping,
  });
});

const inputProps = computed(() => {
  const {
    modelValue,
    locale,
    currency,
    precision,
    min,
    max,
    allowNegative,
    allowEmpty,
    useGrouping,
    showCurrency,
    shiftDecimal,
    ...forwardedProps
  } = props;

  return forwardedProps;
});

watch(
  () => props.modelValue,
  (value) => {
    if (!isFocused.value) {
      draftValue.value = formatNumber(value, {
        locale: props.locale,
        minimumFractionDigits: props.precision,
        maximumFractionDigits: props.precision,
        useGrouping: false,
      });
    }
  },
  {
    immediate: true,
  },
);

function handleInput(value) {
  if (props.shiftDecimal) {
    handleShiftDecimalInput(value);
    return;
  }

  draftValue.value = value;

  const result = processNumber(value, numberOptions.value);

  if (result.parsed === null) {
    emit("update:modelValue", props.allowEmpty ? null : 0);

    return;
  }

  emit("update:modelValue", result.clamped);
}

function handleShiftDecimalInput(value) {
  const digits = String(value ?? "").replace(/\D/g, "");

  if (!digits) {
    draftValue.value = "";
    emit("update:modelValue", props.allowEmpty ? null : 0);
    return;
  }

  const parsed = Number(digits) / 10 ** props.precision;
  const clamped = Math.min(
    props.max ?? Number.POSITIVE_INFINITY,
    Math.max(props.min ?? Number.NEGATIVE_INFINITY, parsed),
  );

  draftValue.value = formatNumber(clamped, {
    locale: props.locale,
    minimumFractionDigits: props.precision,
    maximumFractionDigits: props.precision,
    useGrouping: props.useGrouping,
  });

  emit("update:modelValue", clamped);
}

function handleFocus(event) {
  isFocused.value = true;

  draftValue.value = formatNumber(props.modelValue, {
    locale: props.locale,
    minimumFractionDigits: props.precision,
    maximumFractionDigits: props.precision,
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
    locale: props.locale,
    minimumFractionDigits: props.precision,
    maximumFractionDigits: props.precision,
    useGrouping: false,
  });

  emit("blur", event);
}

function resolveDecimalSeparator(locale) {
  return resolveSeparator(locale, "decimal", ",");
}

function resolveGroupSeparator(locale) {
  return resolveSeparator(locale, "group", ".");
}

function resolveSeparator(locale, type, fallback) {
  try {
    const parts = new Intl.NumberFormat(locale).formatToParts(1234.5);

    return parts.find((part) => part.type === type)?.value ?? fallback;
  } catch {
    return fallback;
  }
}
</script>

<style src="./style.css"></style>
