<template>
  <div class="autocomplete-control" @focusout="handleFocusOut">
    <input
      ref="inputRef"
      class="autocomplete-control__input"
      type="text"
      role="combobox"
      :id="id"
      :name="name"
      :value="searchValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :autofocus="autofocus"
      :autocomplete="autocomplete"
      :aria-expanded="isOpen"
      :aria-controls="listboxId"
      :aria-activedescendant="activeDescendant"
      :aria-invalid="ariaInvalid"
      :aria-describedby="ariaDescribedBy"
      @input="handleInput"
      @focus="handleFocus"
      @blur="emit('blur', $event)"
      @keydown="handleKeydown"
    />

    <ul
      v-if="isOpen"
      :id="listboxId"
      class="autocomplete-control__listbox"
      role="listbox"
    >
      <li
        v-for="(option, index) in visibleOptions"
        :id="getOptionId(index)"
        :key="getOptionKey(option)"
        class="autocomplete-control__option"
        :class="{
          'autocomplete-control__option--active': index === activeIndex,
        }"
        role="option"
        :aria-selected="isSelected(option)"
        @mousedown.prevent="selectOption(option)"
        @mouseenter="activeIndex = index"
      >
        {{ getOptionLabel(option) }}
      </li>

      <li
        v-if="!visibleOptions.length"
        class="autocomplete-control__empty"
        role="option"
        aria-disabled="true"
      >
        {{ noResultsText }}
      </li>
    </ul>
  </div>
</template>

<script setup>
import { computed, ref } from "vue";

import { useFieldContext } from "@/composables/useFieldContext.js";
import { autocompleteControlProps } from "./props.js";

const props = defineProps(autocompleteControlProps);

const emit = defineEmits([
  "update:modelValue",
  "update:searchValue",
  "focus",
  "blur",
  "open",
  "close",
]);

const inputRef = ref(null);
const isOpen = ref(false);
const activeIndex = ref(-1);

const { ariaDescribedBy, ariaInvalid } = useFieldContext();

const listboxId = computed(() =>
  props.id ? `${props.id}-listbox` : undefined,
);

const visibleOptions = computed(() => {
  const search = props.searchValue.trim().toLocaleLowerCase();

  if (search.length < props.minSearchLength) {
    return [];
  }

  if (!search) {
    return props.options;
  }

  return props.options.filter((option) =>
    String(getOptionLabel(option)).toLocaleLowerCase().includes(search),
  );
});

const activeDescendant = computed(() => {
  if (
    !isOpen.value ||
    activeIndex.value < 0 ||
    activeIndex.value >= visibleOptions.value.length
  ) {
    return undefined;
  }

  return getOptionId(activeIndex.value);
});

function getOptionLabel(option) {
  if (option !== null && typeof option === "object") {
    return option[props.optionLabel];
  }

  return option;
}

function getOptionValue(option) {
  if (option !== null && typeof option === "object") {
    return option[props.optionValue];
  }

  return option;
}

function getOptionKey(option) {
  return getOptionValue(option);
}

function getOptionId(index) {
  return props.id ? `${props.id}-option-${index}` : undefined;
}

function isSelected(option) {
  return Object.is(getOptionValue(option), props.modelValue);
}

function openList() {
  if (props.disabled || isOpen.value) {
    return;
  }

  isOpen.value = true;
  activeIndex.value = visibleOptions.value.length ? 0 : -1;

  emit("open");
}

function closeList() {
  if (!isOpen.value) {
    return;
  }

  isOpen.value = false;
  activeIndex.value = -1;

  emit("close");
}

function handleInput(event) {
  emit("update:searchValue", event.target.value);
  openList();
}

function handleFocus(event) {
  emit("focus", event);

  if (props.openOnFocus) {
    openList();
  }
}

function handleFocusOut(event) {
  const nextTarget = event.relatedTarget;

  if (nextTarget && event.currentTarget.contains(nextTarget)) {
    return;
  }

  closeList();
}

function handleKeydown(event) {
  if (event.key === "ArrowDown") {
    event.preventDefault();

    if (!isOpen.value) {
      openList();
      return;
    }

    if (!visibleOptions.value.length) {
      return;
    }

    activeIndex.value = (activeIndex.value + 1) % visibleOptions.value.length;

    return;
  }

  if (event.key === "ArrowUp") {
    event.preventDefault();

    if (!isOpen.value) {
      openList();
      return;
    }

    if (!visibleOptions.value.length) {
      return;
    }

    activeIndex.value =
      (activeIndex.value - 1 + visibleOptions.value.length) %
      visibleOptions.value.length;

    return;
  }

  if (event.key === "Enter") {
    if (
      !isOpen.value ||
      activeIndex.value < 0 ||
      activeIndex.value >= visibleOptions.value.length
    ) {
      return;
    }

    event.preventDefault();

    selectOption(visibleOptions.value[activeIndex.value]);

    return;
  }

  if (event.key === "Escape") {
    event.preventDefault();
    closeList();
  }
}

function selectOption(option) {
  emit("update:modelValue", getOptionValue(option));

  emit("update:searchValue", String(getOptionLabel(option)));

  closeList();
}
</script>

<style src="./style.css"></style>
