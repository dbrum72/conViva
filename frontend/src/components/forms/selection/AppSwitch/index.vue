<template>
  <div
    class="app-switch"
    :class="{
      'app-switch--checked': modelValue,
      'app-switch--invalid': hasError,
      'app-switch--disabled': disabled,
    }"
  >
    <label class="app-switch__label" :for="id">
      <SwitchControl
        v-bind="controlProps"
        :aria-invalid="hasError || undefined"
        :aria-describedby="ariaDescribedBy"
        @update:model-value="emit('update:modelValue', $event)"
        @focus="emit('focus', $event)"
        @blur="emit('blur', $event)"
      />

      <span class="app-switch__content">
        <span class="app-switch__text">
          {{ label }}

          <span v-if="required" class="app-switch__required" aria-hidden="true">
            *
          </span>
        </span>

        <span v-if="hint && !hasError" :id="hintId" class="app-switch__hint">
          {{ hint }}
        </span>

        <span v-if="hasError" :id="errorId" class="app-switch__error">
          {{ error }}
        </span>
      </span>
    </label>
  </div>
</template>

<script setup>
import { computed } from "vue";

import SwitchControl from "@/components/forms/controls/SwitchControl/index.vue";
import { appSwitchProps } from "./props.js";
import { pick } from "@/components/forms/shared/utils/pick.js";
import { SWITCH_CONTROL_KEYS } from "@/components/forms/shared/constants/control-keys.js";

const props = defineProps(appSwitchProps);

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

const controlProps = computed(() => pick(props, SWITCH_CONTROL_KEYS));
</script>

<style src="./style.css"></style>
