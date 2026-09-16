<template>
  <InputVariant
    :model-value="modelValue"
    :input-props="inputProps"
    :config="variantConfig"
    @update:model-value="emit('update:modelValue', $event)"
    @focus="emit('focus', $event)"
    @blur="emit('blur', $event)"
  >
    <template v-if="$slots.prepend" #prepend>
      <slot name="prepend" />
    </template>

    <template v-if="$slots.append" #append>
      <slot name="append" />
    </template>
  </InputVariant>
</template>

<script setup>
import { computed } from "vue";

import { InputVariant } from "@/components/forms/internal";
import { appDateProps } from "./props.js";

const props = defineProps(appDateProps);

const emit = defineEmits(["update:modelValue", "focus", "blur"]);

const variantConfig = computed(() => ({
  type: "date",
  autocomplete: props.autocomplete,
  inputmode: undefined,
  icon: "calendar",
  iconSize: 18,
  showIcon: props.showIcon,
}));

const inputProps = computed(() => {
  const {
    modelValue,
    showIcon,
    type,
    inputmode,
    autocomplete,
    ...forwardedProps
  } = props;

  return forwardedProps;
});
</script>

<style src="./style.css"></style>
