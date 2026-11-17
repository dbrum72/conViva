<template>
  <form class="app-form">
    <slot />
  </form>
</template>

<style>
:where(.app-form) {
  display: grid;
  gap: var(--space-4);
  min-inline-size: 0;
}

:where(.app-form > p) {
  margin-block: 0;
}
</style>
