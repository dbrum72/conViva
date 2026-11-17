<template>
  <section class="care-page">
    <header class="care-heading">
      <div>
        <p class="care-eyebrow">CONVIVA · CUIDADOS COMPARTILHADOS</p>
        <h1>{{ title }}</h1>
        <p v-if="subtitle" class="care-muted">{{ subtitle }}</p>
      </div>
      <slot name="actions" />
    </header>
    <p v-if="status.error" role="alert" class="care-error">
      {{ status.error }}
    </p>
    <AppButton
      v-if="status.error && $attrs.onRetry"
      variant="outline"
      :disabled="!!status.pending"
      @click="$emit('retry')"
      >Tentar novamente</AppButton
    >
    <p v-if="status.pending" role="status" class="care-muted">Carregando…</p>
    <slot />
  </section>
</template>
<script setup>
import { computed } from "vue";
import { useCareStore } from "../state/care.js";
import { AppButton } from "@/components/ui";
const props = defineProps({
  title: String,
  subtitle: String,
  requestStatus: Object,
});
const store = useCareStore();
const status = computed(() => props.requestStatus ?? store);
</script>
