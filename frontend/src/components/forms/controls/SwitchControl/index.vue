<template>
  <input
    class="switch-control"
    type="checkbox"
    role="switch"
    :id="id"
    :name="name"
    :checked="modelValue"
    :disabled="disabled"
    :required="required"
    :autofocus="autofocus"
    :aria-checked="modelValue"
    :aria-invalid="ariaInvalid"
    :aria-describedby="ariaDescribedBy"
    @change="emitModelValue"
    @focus="emit('focus', $event)"
    @blur="emit('blur', $event)"
  />
</template>

<script setup>
import { switchControlProps } from "./props.js";

defineProps(switchControlProps);

const emit = defineEmits(["update:modelValue", "focus", "blur"]);

function emitModelValue(event) {
  emit("update:modelValue", event.target.checked);
}
</script>

<style src="./style.css"></style>
