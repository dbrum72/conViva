<template>
  <div class="app-tabs" role="tablist" :aria-label="ariaLabel">
    <button
      v-for="item in items"
      :key="item.value"
      ref="tabRefs"
      type="button"
      class="app-tabs__tab"
      :class="{
        'app-tabs__tab--active': item.value === modelValue,
      }"
      role="tab"
      :aria-selected="item.value === modelValue ? 'true' : 'false'"
      :tabindex="item.value === modelValue ? 0 : -1"
      @click="select(item.value)"
      @keydown="handleKeydown($event, item.value)"
    >
      {{ item.label }}
    </button>
  </div>
</template>

<script setup>
import { nextTick, ref } from "vue";

import { appTabsProps } from "./props.js";

const props = defineProps(appTabsProps);

const emit = defineEmits(["update:modelValue"]);

const tabRefs = ref([]);

function findIndex(value) {
  return props.items.findIndex((item) => item.value === value);
}

function select(value, { focus = false } = {}) {
  if (value === props.modelValue) {
    return;
  }

  emit("update:modelValue", value);

  if (!focus) {
    return;
  }

  nextTick(() => {
    const index = findIndex(value);

    tabRefs.value[index]?.focus();
  });
}

function selectByIndex(index) {
  const item = props.items[index];

  if (!item) {
    return;
  }

  select(item.value, {
    focus: true,
  });
}

function handleKeydown(event, value) {
  const currentIndex = findIndex(value);

  if (currentIndex === -1 || props.items.length === 0) {
    return;
  }

  let targetIndex = null;

  if (event.key === "ArrowRight") {
    targetIndex = (currentIndex + 1) % props.items.length;
  }

  if (event.key === "ArrowLeft") {
    targetIndex = (currentIndex - 1 + props.items.length) % props.items.length;
  }

  if (event.key === "Home") {
    targetIndex = 0;
  }

  if (event.key === "End") {
    targetIndex = props.items.length - 1;
  }

  if (targetIndex === null) {
    return;
  }

  event.preventDefault();

  selectByIndex(targetIndex);
}
</script>

<style src="./style.css"></style>
