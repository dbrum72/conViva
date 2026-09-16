<template>
  <header class="public-header" data-testid="public-header">
    <div class="public-header__container">
      <AppLogo
        class="public-header__brand"
        :to="{ name: 'home' }"
        aria-label="conViva - Página inicial"
      />

      <nav class="public-header__nav" aria-label="Navegação principal">
        <RouterLink
          class="public-header__nav-link"
          :to="{
            name: 'home',
            hash: '#recursos',
          }"
          data-testid="public-resources-link"
        >
          Recursos
        </RouterLink>

        <RouterLink
          class="public-header__nav-link"
          :to="{
            name: 'home',
            hash: '#beneficios',
          }"
          data-testid="public-benefits-link"
        >
          Benefícios
        </RouterLink>
      </nav>

      <div class="public-header__actions">
        <template v-if="!authStore.isAuthenticated">
          <RouterLink
            class="public-header__login"
            :to="{ name: 'login' }"
            data-testid="public-login-link"
          >
            Entrar
          </RouterLink>

          <RouterLink
            class="public-header__register"
            :to="{ name: 'register' }"
            data-testid="public-register-link"
          >
            Criar conta
          </RouterLink>
        </template>

        <RouterLink
          v-else-if="authStore.contextLoaded"
          class="public-header__register"
          :to="{ name: 'dashboard' }"
          data-testid="public-dashboard-link"
        >
          Acessar sistema
        </RouterLink>

        <RouterLink
          v-else
          class="public-header__register"
          :to="{ name: 'organizations.select' }"
          data-testid="public-organization-link"
        >
          Selecionar grupo
        </RouterLink>
      </div>
    </div>
  </header>
</template>

<script setup>
import { RouterLink } from "vue-router";

import { AppLogo } from "@/components/ui";

import { useAuthStore } from "@/state/auth.js";

const authStore = useAuthStore();
</script>

<style scoped>
.public-header {
  position: sticky;
  top: 0;
  z-index: 50;
  width: 100%;
  border-bottom: 1px solid var(--color-border, #e2dfd4);
  background: color-mix(in srgb, var(--color-page) 94%, transparent);
  backdrop-filter: blur(12px);
}

.public-header__container {
  width: min(100% - 2rem, 75rem);
  min-height: 4.5rem;
  margin-inline: auto;
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 2rem;
}

.public-header__brand {
  width: fit-content;
  color: inherit;
  text-decoration: none;
}

.public-header__nav {
  display: flex;
  align-items: center;
  gap: 1.75rem;
}

.public-header__nav-link,
.public-header__login {
  color: var(--color-text-muted, #62675f);
  font-size: 0.9rem;
  font-weight: 600;
  text-decoration: none;
  transition: color 0.15s ease;
}

.public-header__nav-link:hover,
.public-header__login:hover {
  color: var(--color-text, #252923);
}

.public-header__actions {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 1.25rem;
}

.public-header__register {
  display: inline-flex;
  min-height: 2.65rem;
  align-items: center;
  justify-content: center;
  padding: 0.55rem 1.05rem;
  border-radius: 0.6rem;
  background: var(--color-brand);
  color: #fff;
  font-size: 0.875rem;
  font-weight: 700;
  text-decoration: none;
  transition:
    transform 0.15s ease,
    opacity 0.15s ease;
}

.public-header__register:hover {
  opacity: 0.92;
}

.public-header__register:active {
  transform: translateY(1px);
}

@media (max-width: 767.98px) {
  .public-header__container {
    width: min(100% - 1.5rem, 75rem);
    min-height: 4rem;
    grid-template-columns: 1fr auto;
    gap: 1rem;
  }

  .public-header__nav {
    display: none;
  }

  .public-header__actions {
    gap: 0.85rem;
  }

  .public-header__login {
    font-size: 0.85rem;
  }

  .public-header__register {
    min-height: 2.5rem;
    padding-inline: 0.85rem;
    font-size: 0.825rem;
  }
}

@media (max-width: 399.98px) {
  .public-header__login {
    display: none;
  }
}
</style>
