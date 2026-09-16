<template>
  <AppInput
    v-bind="inputProps"
    :type="inputType"
    @update:model-value="emit('update:modelValue', $event)"
    @focus="emit('focus', $event)"
    @blur="emit('blur', $event)"
  >
    <template v-if="$slots.prepend" #prepend>
      <slot name="prepend" />
    </template>

    <template v-if="showToggle || $slots.append" #append>
      <slot name="append">
        <InputIconButton
          v-if="showToggle"
          :aria-label="toggleLabel"
          :disabled="disabled"
          @click="toggleVisibility"
        >
          <InputIcon>
            <AppIcon :name="isVisible ? 'eye-off' : 'eye'" :size="18" />
          </InputIcon>
        </InputIconButton>
      </slot>
    </template>
  </AppInput>
</template>

<script setup>
import { computed, ref } from "vue";

import AppInput from "@/components/forms/fields/AppInput/index.vue";
import { appPasswordProps } from "./props.js";

import AppIcon from "@/components/ui/AppIcon/index.vue";
import { InputIcon, InputIconButton } from "@/components/forms/internal";

const props = defineProps(appPasswordProps);

const emit = defineEmits(["update:modelValue", "focus", "blur"]);

const isVisible = ref(false);

const inputType = computed(() => (isVisible.value ? "text" : "password"));

const toggleLabel = computed(() =>
  isVisible.value ? props.visibleLabel : props.hiddenLabel,
);

/*
 * Encaminha ao AppInput todas as props públicas do campo,
 * removendo apenas as props exclusivas do AppPassword.
 */
const inputProps = computed(() => {
  const { showToggle, visibleLabel, hiddenLabel, type, ...appInputProps } =
    props;

  return appInputProps;
});

function toggleVisibility() {
  if (props.disabled) {
    return;
  }

  isVisible.value = !isVisible.value;
}
</script>

<style src="./style.css"></style>
