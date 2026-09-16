<template>
  <Teleport to="body">
    <div v-if="open" class="app-dialog" @click.self="handleBackdrop">
      <section
        ref="panelRef"
        class="app-dialog__panel"
        :class="`app-dialog__panel--${size}`"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="titleId"
        tabindex="-1"
      >
        <header class="app-dialog__header">
          <h2
            :id="titleId"
            ref="titleRef"
            class="app-dialog__title"
            tabindex="-1"
          >
            {{ title }}
          </h2>

          <AppButton
            type="button"
            variant="ghost"
            aria-label="Fechar"
            :disabled="busy"
            @click="close"
          >
            <AppIcon name="close" aria-hidden="true" />
          </AppButton>
        </header>

        <div class="app-dialog__body">
          <p v-if="error" class="app-dialog__error" role="alert">{{ error }}</p>
          <slot />
        </div>

        <footer v-if="$slots.footer" class="app-dialog__footer">
          <slot name="footer" />
        </footer>
      </section>
    </div>
  </Teleport>
</template>

<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from "vue";

import AppButton from "@/components/ui/AppButton/index.vue";
import AppIcon from "@/components/ui/AppIcon/index.vue";

import { appDialogProps } from "./props.js";

const props = defineProps(appDialogProps);

const emit = defineEmits(["close"]);

const panelRef = ref(null);
const titleRef = ref(null);

const previousActiveElement = ref(null);

const titleId = `app-dialog-title-${Math.random().toString(36).slice(2)}`;

const focusableSelector = [
  'a[href]:not([tabindex="-1"])',
  'button:not([disabled]):not([tabindex="-1"])',
  'input:not([disabled]):not([type="hidden"]):not([tabindex="-1"])',
  'select:not([disabled]):not([tabindex="-1"])',
  'textarea:not([disabled]):not([tabindex="-1"])',
  '[tabindex]:not([tabindex="-1"])',
].join(",");

function close() {
  if (props.busy) return;
  emit("close");
}

function handleBackdrop() {
  if (!props.closeOnBackdrop) {
    return;
  }

  close();
}

function getFocusableElements() {
  if (!panelRef.value) {
    return [];
  }

  return Array.from(panelRef.value.querySelectorAll(focusableSelector));
}

function focusInitialElement() {
  if (!panelRef.value) {
    return;
  }

  const autofocusElement = panelRef.value.querySelector("[autofocus]");

  if (autofocusElement) {
    autofocusElement.focus();
    return;
  }

  const focusableElements = getFocusableElements();

  if (focusableElements.length) {
    focusableElements[0].focus();
    return;
  }

  titleRef.value?.focus();
}

function trapFocus(event) {
  const panel = panelRef.value;

  if (!panel) {
    return;
  }

  const focusableElements = getFocusableElements();

  if (!focusableElements.length) {
    event.preventDefault();

    titleRef.value?.focus();

    return;
  }

  const firstElement = focusableElements[0];

  const lastElement = focusableElements[focusableElements.length - 1];

  const activeElement = document.activeElement;

  if (event.shiftKey) {
    if (activeElement === firstElement || !panel.contains(activeElement)) {
      event.preventDefault();

      lastElement.focus();
    }

    return;
  }

  if (activeElement === lastElement || !panel.contains(activeElement)) {
    event.preventDefault();

    firstElement.focus();
  }
}

function handleKeydown(event) {
  const panels = document.querySelectorAll(
    '[role="dialog"][aria-modal="true"]',
  );
  if (panels[panels.length - 1] !== panelRef.value) return;
  if (event.key === "Escape" && props.closeOnEscape) {
    event.preventDefault();

    close();

    return;
  }

  if (event.key === "Tab") {
    trapFocus(event);
  }
}

function addKeyboardListener() {
  document.addEventListener("keydown", handleKeydown);
}

function removeKeyboardListener() {
  document.removeEventListener("keydown", handleKeydown);
}

watch(
  () => props.open,

  async (open) => {
    if (open) {
      previousActiveElement.value = document.activeElement;

      addKeyboardListener();

      await nextTick();

      focusInitialElement();

      return;
    }

    removeKeyboardListener();

    await nextTick();

    previousActiveElement.value?.focus?.();

    previousActiveElement.value = null;
  },

  {
    immediate: true,
  },
);

onBeforeUnmount(() => {
  removeKeyboardListener();
});
</script>

<style src="./style.css"></style>
