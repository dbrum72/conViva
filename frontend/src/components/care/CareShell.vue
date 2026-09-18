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
    <p v-if="store.error" role="alert" class="care-error">{{ store.error }}</p>
    <AppButton
      v-if="store.error && $attrs.onRetry"
      variant="outline"
      :disabled="!!store.pending"
      @click="$emit('retry')"
      >Tentar novamente</AppButton
    >
    <p v-if="store.pending" role="status" class="care-muted">Carregando…</p>
    <slot />
  </section>
</template>
<script setup>
import { useCareStore } from "@/state/care.js";
import { AppButton } from "@/components/ui";
defineProps({ title: String, subtitle: String });
const store = useCareStore();
</script>
