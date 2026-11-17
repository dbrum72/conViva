<template>
  <fieldset class="app-fieldset">
    <legend v-if="$slots.legend">
      <slot name="legend" />
    </legend>
    <slot />
  </fieldset>
</template>

<style>
:where(.app-fieldset) {
  display: grid;
  gap: var(--space-4);
  min-inline-size: 0;
  margin-inline: 0;
}

:where(.app-fieldset > legend) {
  padding-inline: var(--space-2);
  font-weight: var(--font-weight-semibold);
}

:where(.app-fieldset > p, .app-fieldset > h3) {
  margin-block: 0;
}
</style>
