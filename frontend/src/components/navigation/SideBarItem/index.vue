<template>
  <RouterLink
    class="sidebar-item"
    :class="{
      'router-link-active':
        (item.name === 'settings' && route.path.startsWith('/settings')) ||
        (item.name === 'finance' && route.path.startsWith('/finance/')),
    }"
    :to="{ name: item.name }"
  >
    <AppIcon
      v-if="item.icon"
      class="sidebar-item__icon"
      :name="item.icon"
      :size="20"
    />

    <span class="sidebar-item__label">
      {{ item.label }}
    </span>
  </RouterLink>
</template>

<script setup>
import { RouterLink, useRoute } from "vue-router";
const route = useRoute();

import AppIcon from "@/components/ui/AppIcon/index.vue";
import { sideBarItemProps } from "./props.js";

defineProps(sideBarItemProps);
</script>
