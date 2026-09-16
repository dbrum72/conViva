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
    <AutocompleteControl
      v-bind="controlProps"
      @update:model-value="emit('update:modelValue', $event)"
      @update:search-value="emit('update:searchValue', $event)"
      @focus="emit('focus', $event)"
      @blur="emit('blur', $event)"
      @open="emit('open')"
      @close="emit('close')"
    />
  </BaseField>
</template>

<script setup>
import { computed } from "vue";

import AutocompleteControl from "@/components/forms/controls/AutocompleteControl/index.vue";
import BaseField from "@/components/forms/fields/BaseField/index.vue";
import { AUTOCOMPLETE_CONTROL_KEYS } from "@/components/forms/shared/constants/control-keys.js";
import { pick } from "@/components/forms/shared/utils/pick.js";
import { appAutocompleteProps } from "./props.js";

const props = defineProps(appAutocompleteProps);

const emit = defineEmits([
  "update:modelValue",
  "update:searchValue",
  "focus",
  "blur",
  "open",
  "close",
]);

const controlProps = computed(() => pick(props, AUTOCOMPLETE_CONTROL_KEYS));
</script>
