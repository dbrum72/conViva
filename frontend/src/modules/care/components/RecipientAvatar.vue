<template>
  <span class="recipient-avatar" aria-hidden="true">
    <img v-if="src && !failed" :src="src" alt="" @error="failed = true" />
    <template v-else>{{ initials }}</template>
  </span>
</template>

<script setup>
import { ref, watch } from "vue";

const props = defineProps({ src: String, initials: String });
const failed = ref(false);
watch(() => props.src, () => { failed.value = false; });
</script>

<style scoped>
.recipient-avatar {
  display: grid;
  place-items: center;
  flex: 0 0 var(--recipient-avatar-size, 2.5rem);
  width: var(--recipient-avatar-size, 2.5rem);
  height: var(--recipient-avatar-size, 2.5rem);
  overflow: hidden;
  border-radius: var(--recipient-avatar-radius, 50%);
  background: var(--palette-care-green);
  color: var(--color-text);
  font-size: var(--recipient-avatar-font-size, var(--font-size-sm));
  font-weight: var(--font-weight-semibold);
}

.recipient-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
</style>
