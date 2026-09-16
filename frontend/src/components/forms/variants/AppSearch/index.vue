<template>
  <AppInput
    v-bind="inputProps"
    @update:model-value="updateValue"
    @focus="emit('focus', $event)"
    @blur="emit('blur', $event)"
    @keydown.esc="clear"
  >
    <template #prepend>
      <InputIcon>
        <AppIcon name="search" :size="18" />
      </InputIcon>
    </template>

    <template v-if="clearable && hasValue" #append>
      <InputIconButton
        :aria-label="clearLabel"
        :disabled="disabled"
        @click="clear"
      >
        <InputIcon>
          <AppIcon name="close" :size="18" />
        </InputIcon>
      </InputIconButton>
    </template>
  </AppInput>
</template>

<script setup>
import { computed } from "vue";

import AppInput from "@/components/forms/fields/AppInput/index.vue";
import { InputIcon, InputIconButton } from "@/components/forms/internal";

import { appSearchProps } from "./props.js";

const props = defineProps(appSearchProps);

const emit = defineEmits(["update:modelValue", "focus", "blur"]);

const hasValue = computed(() => {
  if (props.modelValue === null || props.modelValue === undefined) {
    return false;
  }

  return String(props.modelValue).length > 0;
});

const inputProps = computed(() => {
  const { clearable, clearLabel, searchLabel, ...forwardedProps } = props;

  return forwardedProps;
});

function updateValue(value) {
  emit("update:modelValue", value);
}

function clear() {
  if (props.disabled || props.readonly) {
    return;
  }

  emit("update:modelValue", "");
}
</script>

<style src="./style.css"></style>
