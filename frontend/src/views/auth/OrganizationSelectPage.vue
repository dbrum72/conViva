<template>
  <main class="organization-select-page">
    <section
      class="organization-select-page__container"
      aria-labelledby="organization-select-title"
    >
      <header class="organization-select-page__brand">
        <AppLogo :to="{ name: 'organizations.select' }" aria-label="conViva" />

        <p class="organization-select-page__brand-description">
          Cuidados compartilhados, com organização e confiança.
        </p>
      </header>

      <AppCard class="organization-select-page__card" as="section">
        <header class="organization-select-page__header">
          <h1
            id="organization-select-title"
            class="organization-select-page__title"
          >
            Meus assistidos
          </h1>

          <p class="organization-select-page__description">
            Todos os seus assistidos, com o seu perfil e os acessos de cada
            grupo. Escolha de quem deseja cuidar.
          </p>
        </header>
        <p class="care-muted">
          Para começar: escolha ou crie um grupo, cadastre o assistido, convide
          sua rede e registre o primeiro cuidado.
        </p>
        <p v-if="loading" role="status">Carregando seus assistidos…</p>

        <div
          v-if="authStore.careGroups.length"
          class="organization-select-page__organizations"
        >
          <button
            v-for="item in authStore.careGroups"
            :key="item.id"
            type="button"
            class="organization-select-page__organization"
            :disabled="loading"
            @click="handleSelect(item)"
          >
            <span class="organization-select-page__organization-icon">
              <AppIcon name="building" :size="20" decorative />
            </span>

            <span class="organization-select-page__organization-content">
              <strong class="organization-select-page__organization-name">
                {{ item.recipient?.name || item.name }}
              </strong>

              <span class="organization-select-page__organization-slug">
                {{ item.name }} ·
                {{
                  item.roles.map((role) => roleLabels[role] || role).join(", ")
                }}
                <span v-if="!item.recipient">
                  · Nenhum assistido disponível para seu acesso</span
                >
                <span v-else-if="item.recipient.status === 'archived'">
                  · Arquivado</span
                >
              </span>
            </span>

            <AppIcon name="chevron-right" :size="16" decorative />
          </button>
        </div>

        <div
          v-else-if="!loading && !errorMessage"
          class="organization-select-page__empty"
          role="status"
        >
          <AppIcon name="building" :size="28" decorative />

          <strong> Nenhum grupo disponível </strong>

          <p>Sua conta não possui vínculo ativo com um grupo de cuidados.</p>
        </div>

        <div
          v-if="errorMessage"
          class="organization-select-page__error"
          role="alert"
        >
          {{ errorMessage }}
          <AppButton variant="outline" :disabled="loading" @click="loadGroups"
            >Tentar novamente</AppButton
          >
        </div>
        <form class="care-form" @submit.prevent="createGroup">
          <label class="care-field"
            >Nome do novo grupo<input
              v-model="newGroupName"
              required
              maxlength="150"
              placeholder="Ex.: Família Oliveira" /></label
          ><AppButton variant="action" type="submit" :loading="loading"
            >Criar grupo de cuidados</AppButton
          >
        </form>
      </AppCard>

      <footer class="organization-select-page__footer">
        <span>{{ authStore.userEmail }}</span>

        <AppButton
          type="button"
          variant="ghost"
          size="sm"
          :loading="loggingOut"
          :disabled="loading || loggingOut"
          @click="handleLogout"
        >
          Sair
        </AppButton>
      </footer>
    </section>
  </main>
</template>

<script setup>
import { ref, onMounted } from "vue";

import { useRouter } from "vue-router";

import { AppButton, AppCard, AppIcon, AppLogo } from "@/components/ui";

import { useAuthStore } from "@/state/auth.js";

const router = useRouter();

const authStore = useAuthStore();

const newGroupName = ref("");
async function createGroup() {
  loading.value = true;
  errorMessage.value = "";
  try {
    await authStore.createGroup(newGroupName.value);
    await router.replace({ name: "recipients" });
  } catch (e) {
    errorMessage.value =
      e.response?.data?.message || "Não foi possível criar o grupo.";
  } finally {
    loading.value = false;
  }
}
const loading = ref(false);
const loggingOut = ref(false);
const errorMessage = ref("");

const roleLabels = {
  responsavel: "Responsável",
  cuidador: "Cuidador",
  observador: "Observador",
};
async function loadGroups() {
  loading.value = true;
  errorMessage.value = "";
  try {
    await authStore.fetchCareGroups();
  } catch {
    errorMessage.value =
      "Não foi possível carregar seus assistidos. Atualize a página para tentar novamente.";
  } finally {
    loading.value = false;
  }
}
onMounted(loadGroups);

async function handleSelect(organization) {
  if (loading.value) {
    return;
  }

  loading.value = true;
  errorMessage.value = "";

  try {
    await authStore.selectOrganization(organization);

    await router.replace(
      organization.recipient
        ? { name: "recipient", params: { id: organization.recipient.id } }
        : { name: "recipients" },
    );
  } catch {
    errorMessage.value =
      "Não foi possível acessar o grupo de cuidados selecionado. Tente novamente.";
  } finally {
    loading.value = false;
  }
}

async function handleLogout() {
  if (loggingOut.value) {
    return;
  }

  loggingOut.value = true;

  try {
    await authStore.logout();
  } finally {
    loggingOut.value = false;

    await router.replace({
      name: "login",
    });
  }
}
</script>

<style scoped>
.organization-select-page {
  min-height: 100vh;

  display: grid;
  place-items: center;

  padding: var(--space-6);

  background: var(--color-background);
}

.organization-select-page__container {
  width: min(100%, 560px);

  display: flex;
  flex-direction: column;

  gap: var(--space-6);
}

.organization-select-page__brand {
  display: flex;
  flex-direction: column;
  align-items: center;

  gap: var(--space-3);

  text-align: center;
}

.organization-select-page__brand-description,
.organization-select-page__description,
.organization-select-page__organization-slug,
.organization-select-page__empty p {
  margin: 0;

  color: var(--color-text-muted);
}

.organization-select-page__card {
  width: 100%;
}

.organization-select-page__header {
  margin-bottom: var(--space-5);
}

.organization-select-page__title {
  margin: 0;

  color: var(--color-text);
}

.organization-select-page__description {
  margin-top: var(--space-2);
}

.organization-select-page__organizations {
  display: flex;
  flex-direction: column;

  gap: var(--space-3);
}

.organization-select-page__organization {
  width: 100%;

  display: flex;
  align-items: center;

  gap: var(--space-3);

  padding: var(--space-4) var(--space-4);

  border: 1px solid var(--color-divider);

  border-radius: var(--radius-md);

  background: var(--color-surface);

  color: var(--color-text);

  text-align: left;

  cursor: pointer;

  transition:
    border-color 0.15s ease,
    background-color 0.15s ease,
    transform 0.15s ease;
}

.organization-select-page__organization:hover:not(:disabled) {
  border-color: var(--color-primary);

  background: var(--color-surface-hover);

  transform: translateY(-1px);
}

.organization-select-page__organization:focus-visible {
  outline: 2px solid var(--color-primary);

  outline-offset: 2px;
}

.organization-select-page__organization:disabled {
  cursor: wait;

  opacity: 0.65;
}

.organization-select-page__organization-icon {
  width: 40px;
  height: 40px;

  flex: 0 0 40px;

  display: grid;
  place-items: center;

  border-radius: var(--radius-md);

  background: var(--color-primary-soft);

  color: var(--color-primary);
}

.organization-select-page__organization-content {
  min-width: 0;

  flex: 1;

  display: flex;
  flex-direction: column;

  gap: var(--space-1);
}

.organization-select-page__organization-name {
  overflow: hidden;

  text-overflow: ellipsis;
  white-space: nowrap;
}

.organization-select-page__organization-slug {
  overflow: hidden;

  font-size: var(--font-size-sm);

  text-overflow: ellipsis;
  white-space: nowrap;
}

.organization-select-page__empty {
  min-height: 180px;

  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  gap: var(--space-3);

  padding: var(--space-6);

  border: 1px dashed var(--color-divider);

  border-radius: var(--radius-md);

  color: var(--color-text);

  text-align: center;
}

.organization-select-page__empty p {
  max-width: 360px;
}

.organization-select-page__error {
  margin-top: var(--space-4);

  padding: var(--space-3) var(--space-4);

  border: 1px solid var(--color-danger);

  border-radius: var(--radius-md);

  background: var(--color-danger-soft);

  color: var(--color-danger);

  font-size: var(--font-size-sm);
}

.organization-select-page__footer {
  display: flex;
  align-items: center;
  justify-content: center;

  gap: var(--space-3);

  color: var(--color-text-muted);

  font-size: var(--font-size-sm);
}

@media (max-width: 640px) {
  .organization-select-page {
    padding: var(--space-4);
  }

  .organization-select-page__container {
    gap: var(--space-4);
  }

  .organization-select-page__organization {
    padding: var(--space-3);
  }
}
</style>
