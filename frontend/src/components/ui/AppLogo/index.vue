<template>
  <RouterLink class="app-logo" :to="to" :aria-label="ariaLabel">
    <span class="app-logo__text">
      {{ text }}
    </span>
  </RouterLink>
</template>

<script setup>
import { RouterLink } from "vue-router";

import { appLogoProps } from "./props.js";

defineProps(appLogoProps);
</script>
