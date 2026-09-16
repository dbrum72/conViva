<template>
  <BaseField
    :id="id"
    :label="label"
    :hint="hint"
    :error="error"
    :required="required"
    :disabled="disabled"
    :readonly="readonly"
  >
    <template v-if="$slots.prepend" #prepend>
      <slot name="prepend" />
    </template>

    <InputControl
      v-bind="controlProps"
      @update:model-value="emit('update:modelValue', $event)"
      @focus="emit('focus', $event)"
      @blur="emit('blur', $event)"
    />

    <template v-if="$slots.append" #append>
      <slot name="append" />
    </template>
  </BaseField>
</template>

<script setup>
import { computed } from "vue";

import BaseField from "@/components/forms/fields/BaseField/index.vue";
import InputControl from "@/components/forms/controls/InputControl/index.vue";
import { INPUT_CONTROL_KEYS } from "@/components/forms/shared/constants/control-keys.js";
import { pick } from "@/components/forms/shared/utils/pick.js";
import { appInputProps } from "./props.js";

const props = defineProps(appInputProps);

const emit = defineEmits(["update:modelValue", "focus", "blur"]);

const controlProps = computed(() => pick(props, INPUT_CONTROL_KEYS));
</script>
