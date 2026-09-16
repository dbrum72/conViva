<template>
  <fieldset
    class="checkbox-group"
    :class="[
      `checkbox-group--${orientation}`,
      {
        'checkbox-group--invalid': hasError,
        'checkbox-group--disabled': disabled,
      },
    ]"
    :disabled="disabled"
    :aria-describedby="ariaDescribedBy"
    :aria-invalid="hasError || undefined"
  >
    <legend v-if="label" class="checkbox-group__legend">
      {{ label }}

      <span v-if="required" class="checkbox-group__required" aria-hidden="true">
        *
      </span>
    </legend>

    <div
      class="checkbox-group__options"
      role="group"
      :aria-required="required || undefined"
    >
      <AppCheckbox
        v-for="(option, index) in options"
        :id="getOptionId(index)"
        :key="getOptionKey(option, index)"
        :model-value="isSelected(option)"
        :name="name"
        :label="getOptionLabel(option)"
        :disabled="disabled || isOptionDisabled(option)"
        @update:model-value="handleOptionChange(option, $event)"
        @focus="emit('focus', $event)"
        @blur="emit('blur', $event)"
      />
    </div>

    <p v-if="hint && !hasError" :id="hintId" class="checkbox-group__hint">
      {{ hint }}
    </p>

    <p v-if="hasError" :id="errorId" class="checkbox-group__error">
      {{ error }}
    </p>
  </fieldset>
</template>

<script setup>
import { computed } from "vue";

import AppCheckbox from "@/components/forms/selection/AppCheckbox/index.vue";
import { checkboxGroupProps } from "./props.js";

const props = defineProps(checkboxGroupProps);

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

function isSelected(option) {
  const value = getOptionValue(option);

  return props.modelValue.some((selectedValue) =>
    Object.is(selectedValue, value),
  );
}

function handleOptionChange(option, checked) {
  const value = getOptionValue(option);

  if (checked) {
    if (isSelected(option)) {
      return;
    }

    emit("update:modelValue", [...props.modelValue, value]);

    return;
  }

  emit(
    "update:modelValue",
    props.modelValue.filter(
      (selectedValue) => !Object.is(selectedValue, value),
    ),
  );
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
