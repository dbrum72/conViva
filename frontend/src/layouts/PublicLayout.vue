<template>
  <div class="public-layout" data-testid="public-layout">
    <PublicHeader />

    <main class="public-layout__main" data-testid="public-main">
      <RouterView />
    </main>

    <PublicFooter />
  </div>
</template>

<script setup>
import PublicFooter from "@/components/public/PublicFooter.vue";

import PublicHeader from "@/components/public/PublicHeader.vue";
</script>

<style scoped>
.public-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--color-page);
}

.public-layout__main {
  flex: 1 0 auto;
}
</style>
