<template>
  <div
    class="app-layout"
    :class="{ 'app-layout--sidebar-closed': !sidebarOpen }"
  >
    <SideBar />

    <HeaderBar
      :sidebar-open="sidebarOpen"
      @toggle-sidebar="sidebarOpen = !sidebarOpen"
    />

    <main class="app-main">
      <RouterView />
    </main>
  </div>
</template>

<script setup>
import { onBeforeUnmount, ref, watch } from "vue";
import { RouterView, useRoute } from "vue-router";

import { HeaderBar, SideBar } from "@/components/layout";

const mobileViewport = window.matchMedia?.("(max-width: 760px)");
const sidebarOpen = ref(!mobileViewport?.matches);
const route = useRoute();
const adaptSidebar = (event) => {
  sidebarOpen.value = !event.matches;
};
mobileViewport?.addEventListener("change", adaptSidebar);
onBeforeUnmount(() =>
  mobileViewport?.removeEventListener("change", adaptSidebar),
);
watch(
  () => route.path,
  () => {
    if (mobileViewport?.matches) sidebarOpen.value = false;
  },
);
</script>
