<template>
  <button
    type="button"
    class="input-icon-button"
    :disabled="disabled"
    :aria-label="ariaLabel"
    @click="$emit('click', $event)"
  >
    <slot />
  </button>
</template>

<script setup>
defineProps({
  ariaLabel: {
    type: String,
    required: true,
  },

  disabled: {
    type: Boolean,
    default: false,
  },
});

defineEmits(["click"]);
</script>

<style scoped>
.input-icon-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;

  width: 2rem;
  height: 2rem;

  padding: 0;

  background: transparent;
  border: 0;
  border-radius: var(--radius-md);

  color: var(--color-text-muted);

  cursor: pointer;

  transition:
    background-color var(--duration-fast) var(--ease-standard),
    color var(--duration-fast) var(--ease-standard);
}

.input-icon-button:hover:not(:disabled) {
  background: var(--color-surface-muted);
  color: var(--color-text);
}

.input-icon-button:focus-visible {
  outline: none;
  box-shadow: var(--focus-ring);
}

.input-icon-button:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}
</style>
