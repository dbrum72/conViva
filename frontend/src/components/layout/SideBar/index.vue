<template>
  <aside class="app-sidebar app-sidebar-nav" aria-label="Barra lateral">
    <header class="sidebar-header">
      <div v-if="authStore.organization" class="sidebar-organization">
        <PersonalRecipientAvatar
          :initials="groupInitials"
          :enabled="!!authStore.organization.can_manage_avatar"
          :context-key="`${authStore.user?.id}:${authStore.organization.id}`"
        />
        <div class="sidebar-organization__current">
          <span class="sidebar-organization__label">Grupo ativo</span>
          <strong
            class="sidebar-organization__name"
            :title="authStore.organization.name"
          >
            {{ authStore.organization.name }}
          </strong>
        </div>
      </div>
    </header>

    <SideBarNav />

    <footer class="sidebar-footer">
      <div class="sidebar-brand">
        <img
          :src="convivaSidebarLogo"
          class="sidebar-brand__symbol"
          width="42"
          height="44"
          alt=""
          aria-hidden="true"
          decoding="async"
          draggable="false"
        />

        <div class="sidebar-brand__text">
          <span class="sidebar-brand__name">conViva</span>
          <span class="sidebar-brand__tagline">Cuidados compartilhados</span>
        </div>
      </div>
    </footer>
  </aside>
</template>

<script setup>
import { computed } from "vue";
import PersonalRecipientAvatar from "@/components/care/PersonalRecipientAvatar.vue";
import convivaSidebarLogo from "@/assets/brand/conviva-sidebar-logo.svg";
import SideBarNav from "@/components/navigation/SideBarNav/index.vue";
import { useAuthStore } from "@/state/auth.js";

const authStore = useAuthStore();
const groupInitials = computed(() =>
  (authStore.organization?.name || "")
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => Array.from(part)[0] || "")
    .join("")
    .toLocaleUpperCase("pt-BR"),
);
</script>

<style scoped>
.app-sidebar-nav {
  display: flex;
  flex-direction: column;
  min-width: 0;
  min-height: 100dvh;
  height: 100%;
  overflow: hidden;
}

.sidebar-header {
  display: flex;
  align-items: center;
  flex: 0 0 auto;
  min-height: var(--header-height);
  padding: 0 1.5rem;
}

.sidebar-organization {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  width: 100%;
  min-width: 0;
}

.sidebar-organization__current {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: var(--space-1);
}

.sidebar-organization__label {
  color: rgb(255 255 255 / 0.66);
  font-size: var(--font-size-sm);
}

.sidebar-organization__name {
  overflow: hidden;
  color: var(--neutral-0);
  text-overflow: ellipsis;
  white-space: nowrap;
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  min-width: 0;
}

.sidebar-brand__symbol {
  display: block;
  flex: 0 0 auto;
  width: 42px;
  height: 44px;
  object-fit: contain;
  user-select: none;
}

.sidebar-brand__text {
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-width: 0;
}

.sidebar-brand__name,
.sidebar-brand__tagline {
  display: block;
  white-space: nowrap;
}

.sidebar-brand__name {
  color: var(--sidebar-brand-title-color, #f6f2e9);
  font-family: var(--font-family-heading, inherit);
  font-size: 1.125rem;
  font-weight: 700;
  line-height: 1;
  letter-spacing: 0.035em;
  text-transform: uppercase;
}

.sidebar-brand__tagline {
  margin-top: 0.25rem;
  color: var(--sidebar-brand-subtitle-color, #d8c79f);
  font-size: 0.5rem;
  font-weight: 500;
  line-height: 1;
  letter-spacing: 0.055em;
  text-transform: uppercase;
}

.app-sidebar-nav :deep(.sidebar-nav) {
  flex: 1 1 auto;
  min-height: 0;
  padding-bottom: var(--space-5);
  overflow-x: hidden;
  overflow-y: auto;
  overscroll-behavior: contain;
  scrollbar-width: thin;
}

.sidebar-footer {
  position: static;
  z-index: 2;
  left: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  flex: 0 0 auto;
  width: var(--sidebar-width);
  min-height: var(--header-height);
  padding: 0 1.5rem;
  background: var(--color-brand-active);
}
</style>
