<template>
  <AppInput
    v-bind="resolvedInputProps"
    :type="normalizedConfig.type"
    :autocomplete="normalizedConfig.autocomplete"
    :inputmode="normalizedConfig.inputmode"
    @update:model-value="emit('update:modelValue', $event)"
    @focus="emit('focus', $event)"
    @blur="emit('blur', $event)"
  >
    <template v-if="normalizedConfig.showIcon || $slots.prepend" #prepend>
      <slot name="prepend">
        <InputIcon v-if="normalizedConfig.showIcon && normalizedConfig.icon">
          <AppIcon
            :name="normalizedConfig.icon"
            :size="normalizedConfig.iconSize"
          />
        </InputIcon>
      </slot>
    </template>

    <template v-if="$slots.append" #append>
      <slot name="append" />
    </template>
  </AppInput>
</template>

<script setup>
import { computed } from "vue";

import { AppInput } from "@/components/forms";
import { InputIcon } from "@/components/forms/internal";
import { AppIcon } from "@/components/ui";

import { inputVariantProps } from "./props.js";

const props = defineProps(inputVariantProps);

const emit = defineEmits(["update:modelValue", "focus", "blur"]);

const normalizedConfig = computed(() => ({
  type: "text",
  autocomplete: undefined,
  inputmode: undefined,
  icon: "",
  iconSize: 18,
  showIcon: true,
  ...props.config,
}));

const resolvedInputProps = computed(() => ({
  ...props.inputProps,
  modelValue: props.modelValue,
}));
</script>
