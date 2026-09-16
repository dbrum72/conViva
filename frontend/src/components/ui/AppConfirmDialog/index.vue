<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="app-confirm-dialog"
      role="presentation"
      @click.self="handleCancel"
    >
      <section
        ref="panelRef"
        class="app-confirm-dialog__panel"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="titleId"
        :aria-describedby="messageId"
      >
        <header class="app-confirm-dialog__header">
          <h2 :id="titleId" class="app-confirm-dialog__title">
            {{ title }}
          </h2>
        </header>

        <div class="app-confirm-dialog__body">
          <p v-if="error" role="alert">{{ error }}</p>
          <p :id="messageId" class="app-confirm-dialog__message">
            {{ message }}
          </p>
        </div>

        <footer class="app-confirm-dialog__actions">
          <AppButton
            type="button"
            variant="ghost"
            :disabled="loading"
            @click="handleCancel"
          >
            {{ cancelLabel }}
          </AppButton>

          <AppButton
            type="button"
            variant="action"
            :loading="loading"
            :disabled="loading"
            @click="handleConfirm"
          >
            {{ confirmLabel }}
          </AppButton>
        </footer>
      </section>
    </div>
  </Teleport>
</template>

<script setup>
import { nextTick, onBeforeUnmount, ref, useId, watch } from "vue";

import AppButton from "@/components/ui/AppButton/index.vue";

import { appConfirmDialogProps } from "./props.js";

const props = defineProps(appConfirmDialogProps);

const emit = defineEmits(["confirm", "cancel"]);

const titleId = `confirm-title-${useId()}`;
const messageId = `confirm-message-${useId()}`;
const panelRef = ref(null);
let previousFocus = null;

function handleKeydown(event) {
  const panels = document.querySelectorAll(
    '[role="dialog"][aria-modal="true"]',
  );
  if (panels[panels.length - 1] !== panelRef.value) return;
  if (event.key === "Escape") {
    event.preventDefault();
    handleCancel();
  }
  if (event.key === "Tab") {
    const buttons = [
      ...panelRef.value.querySelectorAll("button:not([disabled])"),
    ];
    const first = buttons[0];
    const last = buttons[buttons.length - 1];
    if (!first) {
      event.preventDefault();
      return;
    }
    if (
      event.shiftKey &&
      (document.activeElement === first ||
        !panelRef.value.contains(document.activeElement))
    ) {
      event.preventDefault();
      last.focus();
    } else if (
      !event.shiftKey &&
      (document.activeElement === last ||
        !panelRef.value.contains(document.activeElement))
    ) {
      event.preventDefault();
      first.focus();
    }
  }
}

watch(
  () => props.open,
  async (open) => {
    if (open) {
      previousFocus = document.activeElement;
      document.addEventListener("keydown", handleKeydown);
      await nextTick();
      panelRef.value?.querySelector("button")?.focus();
    } else {
      document.removeEventListener("keydown", handleKeydown);
      await nextTick();
      previousFocus?.focus();
      previousFocus = null;
    }
  },
  { immediate: true },
);
onBeforeUnmount(() => document.removeEventListener("keydown", handleKeydown));

function handleConfirm() {
  if (props.loading) {
    return;
  }

  emit("confirm");
}

function handleCancel() {
  if (props.loading) {
    return;
  }

  emit("cancel");
}
</script>

<style src="./style.css"></style>
