<template>
  <span class="input-icon" aria-hidden="true">
    <slot />
  </span>
</template>

<style scoped>
.input-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;

  width: 1.25rem;
  height: 1.25rem;

  color: var(--color-text-muted);

  flex-shrink: 0;

  pointer-events: none;
}
</style>
