<template>
  <input
    class="radio-control"
    type="radio"
    :id="id"
    :name="name"
    :value="value"
    :checked="isChecked"
    :disabled="disabled"
    :required="required"
    :autofocus="autofocus"
    :aria-invalid="ariaInvalid"
    :aria-describedby="ariaDescribedBy"
    @change="emitModelValue"
    @focus="emit('focus', $event)"
    @blur="emit('blur', $event)"
  />
</template>

<script setup>
import { computed } from "vue";

import { radioControlProps } from "./props.js";

const props = defineProps(radioControlProps);

const emit = defineEmits(["update:modelValue", "focus", "blur"]);

const isChecked = computed(() => Object.is(props.modelValue, props.value));

function emitModelValue() {
  emit("update:modelValue", props.value);
}
</script>

<style src="./style.css"></style>
