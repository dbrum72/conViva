<template>
  <fieldset
    class="radio-group"
    :class="[
      `radio-group--${orientation}`,
      {
        'radio-group--invalid': hasError,
        'radio-group--disabled': disabled,
      },
    ]"
    :disabled="disabled"
    :aria-describedby="ariaDescribedBy"
    :aria-invalid="hasError || undefined"
  >
    <legend v-if="label" class="radio-group__legend">
      {{ label }}

      <span v-if="required" class="radio-group__required" aria-hidden="true">
        *
      </span>
    </legend>

    <div
      class="radio-group__options"
      role="radiogroup"
      :aria-required="required || undefined"
    >
      <AppRadio
        v-for="(option, index) in options"
        :id="getOptionId(index)"
        :key="getOptionKey(option, index)"
        :model-value="modelValue"
        :name="name"
        :value="getOptionValue(option)"
        :label="getOptionLabel(option)"
        :disabled="disabled || isOptionDisabled(option)"
        @update:model-value="emit('update:modelValue', $event)"
        @focus="emit('focus', $event)"
        @blur="emit('blur', $event)"
      />
    </div>

    <p v-if="hint && !hasError" :id="hintId" class="radio-group__hint">
      {{ hint }}
    </p>

    <p v-if="hasError" :id="errorId" class="radio-group__error">
      {{ error }}
    </p>
  </fieldset>
</template>

<script setup>
import { computed } from "vue";

import AppRadio from "@/components/forms/selection/AppRadio/index.vue";
import { radioGroupProps } from "./props.js";

const props = defineProps(radioGroupProps);

const emit = defineEmits(["update:modelValue", "focus", "blur"]);

const hasError = computed(() => Boolean(props.error));

const hintId = computed(() => (props.id ? `${props.id}-hint` : undefined));

const errorId = computed(() => (props.id ? `${props.id}-error` : undefined));

const ariaDescribedBy = computed(() => {
  if (hasError.value) {
    return errorId.value;
  }

  return props.hint ? hintId.value : undefined;
});

function getOptionLabel(option) {
  if (option !== null && typeof option === "object") {
    return option[props.optionLabel];
  }

  return option;
}

function getOptionValue(option) {
  if (option !== null && typeof option === "object") {
    return option[props.optionValue];
  }

  return option;
}

function isOptionDisabled(option) {
  if (option === null || typeof option !== "object") {
    return false;
  }

  return Boolean(option[props.optionDisabled]);
}

function getOptionId(index) {
  if (!props.id) {
    return undefined;
  }

  return `${props.id}-option-${index}`;
}

function getOptionKey(option, index) {
  const value = getOptionValue(option);

  return value === undefined ? index : `${typeof value}:${String(value)}`;
}
</script>

<style src="./style.css"></style>
