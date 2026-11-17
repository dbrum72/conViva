<template>
  <input
    v-bind="$attrs"
    class="input-control"
    :value="modelValue"
    :type="type"
    :id="id"
    :name="name"
    :placeholder="placeholder"
    :disabled="disabled"
    :readonly="readonly"
    :required="required"
    :autofocus="autofocus"
    :autocomplete="autocomplete"
    :maxlength="maxlength"
    :minlength="minlength"
    :inputmode="inputmode"
    :min="min"
    :max="max"
    :step="step"
    :list="suggestions?.length ? suggestionsId : undefined"
    :aria-invalid="ariaInvalid"
    :aria-describedby="ariaDescribedBy"
    @input="emitModelValue"
    @focus="emit('focus', $event)"
    @blur="emit('blur', $event)"
  />
  <datalist v-if="suggestions?.length" :id="suggestionsId">
    <option
      v-for="suggestion in suggestions"
      :key="suggestion"
      :value="suggestion"
    />
  </datalist>
</template>

<script setup>
import { useId } from "vue";
import { useFieldContext } from "@/composables/useFieldContext.js";
import { inputControlProps } from "./props.js";

defineProps(inputControlProps);
defineOptions({ inheritAttrs: false });
const suggestionsId = useId();

const emit = defineEmits(["update:modelValue", "focus", "blur"]);

const { ariaDescribedBy, ariaInvalid } = useFieldContext();

function emitModelValue(event) {
  emit("update:modelValue", event.target.value);
}
</script>

<style src="./style.css"></style>
