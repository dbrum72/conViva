<template>
  <header class="app-header app-header-bar">
    <button
      class="app-header-bar__menu"
      type="button"
      aria-label="Alternar menu lateral"
      :aria-expanded="sidebarOpen"
      @click="$emit('toggle-sidebar')"
    >
      <Menu :size="21" :stroke-width="1.8" aria-hidden="true" />
    </button>

    <div v-if="authStore.user" class="app-header-bar__welcome">
      <strong
        >{{ greeting }}, {{ displayName }}
        <span aria-hidden="true">👋</span></strong
      >
      <span>Último acesso: {{ lastAccess }}</span>
    </div>

    <nav class="app-header-bar__actions" aria-label="Ações do usuário">
      <button
        class="app-header-bar__icon-button app-header-bar__notifications"
        type="button"
        aria-label="Abrir notificações"
        @click="router.push({ name: 'notifications' })"
      >
        <Bell :size="21" :stroke-width="1.8" aria-hidden="true" />
      </button>
      <button
        v-if="canAccessSettings"
        class="app-header-bar__icon-button"
        type="button"
        aria-label="Configurações"
        @click="router.push({ name: 'settings' })"
      >
        <Settings :size="20" :stroke-width="1.8" aria-hidden="true" />
      </button>
      <div
        v-if="authStore.user"
        ref="profileRef"
        class="app-header-bar__profile"
      >
        <span class="app-header-bar__avatar" aria-hidden="true">{{
          initials
        }}</span>
        <span class="app-header-bar__identity">
          <strong>{{ authStore.userName }}</strong>
          <span>{{ roleLabel }}</span>
        </span>
        <button
          ref="profileMenuButtonRef"
          class="app-header-bar__profile-menu"
          type="button"
          aria-label="Abrir menu do usuário"
          aria-haspopup="menu"
          :aria-expanded="profileMenuOpen"
          aria-controls="profile-menu"
          @click="toggleProfileMenu"
        >
          <ChevronDown :size="16" :stroke-width="1.8" aria-hidden="true" />
        </button>
        <div
          v-if="profileMenuOpen"
          id="profile-menu"
          class="app-header-bar__profile-dropdown"
          role="menu"
        >
          <button
            class="app-header-bar__profile-dropdown-item"
            type="button"
            role="menuitem"
            :disabled="loggingOut"
            @click="handleLogout"
          >
            <LogOut :size="17" :stroke-width="1.8" aria-hidden="true" />
            <span>{{ loggingOut ? "Saindo..." : "Sair" }}</span>
          </button>
        </div>
      </div>
    </nav>
  </header>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { Bell, ChevronDown, LogOut, Menu, Settings } from "@lucide/vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/state/auth.js";
import { settingsPermissions } from "@/config/settings.js";

defineProps({ sidebarOpen: { type: Boolean, default: true } });
defineEmits(["toggle-sidebar"]);

const router = useRouter();
const authStore = useAuthStore();
const canAccessSettings = computed(() =>
  settingsPermissions.some((permission) => authStore.hasPermission(permission)),
);
const loggingOut = ref(false);
const profileMenuOpen = ref(false);
const profileRef = ref(null);
const profileMenuButtonRef = ref(null);
const currentHour = new Date().getHours();
const greeting =
  currentHour < 12 ? "Bom dia" : currentHour < 18 ? "Boa tarde" : "Boa noite";
const displayName = computed(() => authStore.userName || "Usuário");
const initials = computed(() =>
  displayName.value
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part[0])
    .join("")
    .toUpperCase(),
);
const roleLabel = computed(() => {
  if (authStore.roles?.includes("responsavel")) return "Responsável";
  const role = authStore.roles[0];
  return role
    ? role
        .split("-")
        .map((part) => `${part.charAt(0).toUpperCase()}${part.slice(1)}`)
        .join(" ")
    : "Responsável";
});
const lastAccess = computed(() => {
  if (!authStore.last_login_at) return "Não informado";

  const lastLogin = new Date(authStore.last_login_at);
  if (Number.isNaN(lastLogin.getTime())) return "Não informado";

  const now = new Date();
  const isToday =
    lastLogin.getFullYear() === now.getFullYear() &&
    lastLogin.getMonth() === now.getMonth() &&
    lastLogin.getDate() === now.getDate();
  const time = new Intl.DateTimeFormat("pt-BR", {
    hour: "2-digit",
    minute: "2-digit",
  }).format(lastLogin);

  if (isToday) return `Hoje, ${time}`;

  const date = new Intl.DateTimeFormat("pt-BR").format(lastLogin);
  return `${date}, ${time}`;
});

async function handleLogout() {
  if (loggingOut.value) return;
  loggingOut.value = true;
  profileMenuOpen.value = false;
  try {
    await authStore.logout();
  } finally {
    loggingOut.value = false;
    await router.replace({ name: "login" });
  }
}

function toggleProfileMenu() {
  profileMenuOpen.value = !profileMenuOpen.value;
}

function handleDocumentClick(event) {
  if (profileMenuOpen.value && !profileRef.value?.contains(event.target))
    profileMenuOpen.value = false;
}

function handleDocumentKeydown(event) {
  if (event.key !== "Escape" || !profileMenuOpen.value) return;
  profileMenuOpen.value = false;
  profileMenuButtonRef.value?.focus();
}

onMounted(() => {
  document.addEventListener("click", handleDocumentClick);
  document.addEventListener("keydown", handleDocumentKeydown);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleDocumentClick);
  document.removeEventListener("keydown", handleDocumentKeydown);
});
</script>
