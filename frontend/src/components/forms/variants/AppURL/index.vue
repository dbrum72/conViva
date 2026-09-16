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
import { appUrlProps } from "./props.js";

const props = defineProps(appUrlProps);

const emit = defineEmits(["update:modelValue", "focus", "blur"]);

const variantConfig = computed(() => ({
  type: "url",
  autocomplete: "url",
  inputmode: "url",
  icon: "link",
  iconSize: 18,
  showIcon: props.showIcon,
}));

const inputProps = computed(() => {
  const {
    modelValue,
    showIcon,
    type,
    autocomplete,
    inputmode,
    ...forwardedProps
  } = props;

  return forwardedProps;
});
</script>

<style src="./style.css"></style>
