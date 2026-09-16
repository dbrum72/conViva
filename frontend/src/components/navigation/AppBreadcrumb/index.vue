<template>
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <ol class="breadcrumb__list">
      <li
        v-for="(item, index) in breadcrumbItems"
        :key="item.name ?? index"
        class="breadcrumb__item"
      >
        <RouterLink
          v-if="item.name && index < breadcrumbItems.length - 1"
          class="breadcrumb__link"
          :to="{ name: item.name }"
        >
          {{ item.label }}
        </RouterLink>

        <span v-else class="breadcrumb__current" aria-current="page">
          {{ item.label }}
        </span>
      </li>
    </ol>
  </nav>
</template>

<script setup>
import { computed } from "vue";
import { RouterLink, useRoute } from "vue-router";

const route = useRoute();

const breadcrumbItems = computed(() =>
  route.matched
    .filter((record) => record.meta?.breadcrumb)
    .map((record) => ({
      label: record.meta.breadcrumb,
      name: record.name,
    })),
);
</script>
