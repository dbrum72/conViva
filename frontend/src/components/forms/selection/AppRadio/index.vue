<template>
  <div
    class="app-radio"
    :class="{
      'app-radio--checked': isChecked,
      'app-radio--invalid': hasError,
      'app-radio--disabled': disabled,
    }"
  >
    <label class="app-radio__label" :for="id">
      <RadioControl
        v-bind="controlProps"
        :aria-invalid="hasError || undefined"
        :aria-describedby="ariaDescribedBy"
        @update:model-value="emit('update:modelValue', $event)"
        @focus="emit('focus', $event)"
        @blur="emit('blur', $event)"
      />

      <span class="app-radio__content">
        <span class="app-radio__text">
          {{ label }}

          <span v-if="required" class="app-radio__required" aria-hidden="true">
            *
          </span>
        </span>

        <span v-if="hint && !hasError" :id="hintId" class="app-radio__hint">
          {{ hint }}
        </span>

        <span v-if="hasError" :id="errorId" class="app-radio__error">
          {{ error }}
        </span>
      </span>
    </label>
  </div>
</template>

<script setup>
import { computed } from "vue";

import RadioControl from "@/components/forms/controls/RadioControl/index.vue";
import { appRadioProps } from "./props.js";
import { pick } from "@/components/forms/shared/utils/pick.js";
import { RADIO_CONTROL_KEYS } from "@/components/forms/shared/constants/control-keys.js";

const props = defineProps(appRadioProps);

const emit = defineEmits(["update:modelValue", "focus", "blur"]);

const isChecked = computed(() => Object.is(props.modelValue, props.value));

const hasError = computed(() => Boolean(props.error));

const hintId = computed(() => (props.id ? `${props.id}-hint` : undefined));

const errorId = computed(() => (props.id ? `${props.id}-error` : undefined));

const ariaDescribedBy = computed(() => {
  if (hasError.value) {
    return errorId.value;
  }

  return props.hint ? hintId.value : undefined;
});

const controlProps = computed(() => pick(props, RADIO_CONTROL_KEYS));
</script>

<style src="./style.css"></style>
