<template>
  <main class="invitation-page">
    <section
      class="invitation-page__container"
      aria-labelledby="invitation-title"
    >
      <header class="invitation-page__brand">
        <AppLogo :to="{ name: 'login' }" aria-label="conViva" />

        <p class="invitation-page__brand-description">
          Cuidados compartilhados, com organização e confiança.
        </p>
      </header>

      <AppCard class="invitation-page__card" as="section">
        <div
          v-if="loadingInvitation"
          class="invitation-page__state"
          role="status"
        >
          <h1 id="invitation-title" class="invitation-page__title">
            Verificando convite
          </h1>

          <p class="invitation-page__description">
            Aguarde enquanto validamos seu acesso.
          </p>
        </div>

        <div v-else-if="pageError" class="invitation-page__state">
          <div
            class="invitation-page__status-icon invitation-page__status-icon--danger"
            aria-hidden="true"
          >
            <AppIcon name="circle-alert" :size="28" decorative />
          </div>

          <h1 id="invitation-title" class="invitation-page__title">
            {{ pageError.title }}
          </h1>

          <p class="invitation-page__description">
            {{ pageError.message }}
          </p>

          <AppButton type="button" variant="navigation" @click="goToLogin">
            Ir para o login
          </AppButton>
          <AppButton
            v-if="pageError.retryable"
            type="button"
            variant="outline"
            @click="loadInvitation"
            >Tentar novamente</AppButton
          >
        </div>

        <div v-else-if="accepted" class="invitation-page__state">
          <div
            class="invitation-page__status-icon invitation-page__status-icon--success"
            aria-hidden="true"
          >
            <AppIcon name="circle-check" :size="28" decorative />
          </div>

          <h1 id="invitation-title" class="invitation-page__title">
            Convite aceito
          </h1>

          <p class="invitation-page__description">
            Seu acesso a
            <strong>
              {{ acceptedOrganizationName }}
            </strong>
            foi configurado com sucesso.
          </p>

          <AppButton type="button" variant="navigation" @click="goToLogin">
            Entrar no conViva
          </AppButton>
        </div>

        <template v-else-if="invitation">
          <header class="invitation-page__header">
            <p class="invitation-page__eyebrow">Convite de acesso</p>

            <h1 id="invitation-title" class="invitation-page__title">
              Você foi convidado
            </h1>

            <p class="invitation-page__description">
              Você recebeu um convite para integrar
              <strong> {{ invitation.organization.name }} </strong>.
            </p>
          </header>

          <div class="invitation-page__summary">
            <div class="invitation-page__summary-row">
              <span class="invitation-page__summary-label"> Grupo </span>

              <strong class="invitation-page__summary-value">
                {{ invitation.organization.name }}
              </strong>
            </div>

            <div class="invitation-page__summary-row">
              <span class="invitation-page__summary-label"> E-mail </span>

              <strong class="invitation-page__summary-value">
                {{ invitation.email }}
              </strong>
            </div>

            <div class="invitation-page__summary-row">
              <span class="invitation-page__summary-label"> Função </span>

              <strong class="invitation-page__summary-value">
                {{ roleLabel }}
              </strong>
            </div>
          </div>

          <div
            class="invitation-page__summary"
            v-if="invitation.recipients?.length"
          >
            <strong>Assistido do grupo</strong>
            <ul>
              <li
                v-for="recipient in invitation.recipients"
                :key="recipient.id"
              >
                {{ recipient.name }} ·
                {{
                  (
                    invitation.care_accesses.find(
                      (a) => a.care_recipient_id === recipient.id,
                    )?.areas || []
                  )
                    .map((a) => invitationAreaLabels[a])
                    .join(", ")
                }}
                <p>
                  {{
                    invitation.role === "observador" ||
                    !invitation.care_accesses.find(
                      (a) => a.care_recipient_id === recipient.id,
                    )?.can_edit
                      ? "Somente leitura"
                      : "Leitura e edição nas áreas concedidas"
                  }}
                </p>
              </li>
            </ul>
          </div>
          <p class="invitation-page__description">
            Aceitar o convite concede acesso ao assistido. Cada nova obrigação
            de cuidado ou rateio ainda depende do seu aceite individual.
          </p>
          <form
            class="invitation-page__form"
            novalidate
            @submit.prevent="handleAccept"
          >
            <template v-if="invitation.registration_required">
              <div class="invitation-page__registration">
                <h2 class="invitation-page__section-title">Crie sua conta</h2>

                <p class="invitation-page__section-description">
                  Defina seus dados de acesso para concluir o ingresso.
                </p>
              </div>

              <AppInput
                v-model="form.name"
                id="invitation-name"
                name="name"
                label="Nome"
                autocomplete="name"
                :disabled="submitting"
                :error="fieldError('name')"
                required
              />

              <AppEmail
                :model-value="invitation.email"
                id="invitation-email"
                name="email"
                label="E-mail"
                autocomplete="email"
                readonly
                disabled
              />

              <AppPassword
                v-model="form.password"
                id="invitation-password"
                name="password"
                label="Senha"
                autocomplete="new-password"
                :disabled="submitting"
                :error="fieldError('password')"
                required
              />

              <AppPassword
                v-model="form.password_confirmation"
                id="invitation-password-confirmation"
                name="password_confirmation"
                label="Confirme a senha"
                autocomplete="new-password"
                :disabled="submitting"
                :error="fieldError('password_confirmation')"
                required
              />
            </template>

            <div v-else class="invitation-page__existing-user">
              <p>
                Sua conta já existe no conViva. Confirme o convite para
                ingressar neste grupo.
              </p>
            </div>

            <div v-if="submitError" class="invitation-page__error" role="alert">
              {{ submitError }}
            </div>

            <AppButton
              variant="action"
              type="submit"
              size="lg"
              :loading="submitting"
              :disabled="submitting"
              block
            >
              Aceitar convite
            </AppButton>
          </form>
        </template>
      </AppCard>

      <footer class="invitation-page__footer">
        <span>conViva</span>
        <span aria-hidden="true">·</span>
        <span>Cuidados compartilhados</span>
      </footer>
    </section>
  </main>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";

import { useRoute, useRouter } from "vue-router";

import { AppEmail, AppInput, AppPassword } from "@/components/forms";

import { AppButton, AppCard, AppIcon, AppLogo } from "@/components/ui";

import { useInvitationAcceptanceStore } from "@/state/invitation-acceptance.js";
import { storeToRefs } from "pinia";

import { areas as invitationAreaLabels } from "@/utils/care.js";
import { useAuthStore } from "@/state/auth.js";

const route = useRoute();
const router = useRouter();

const authStore = useAuthStore();

const acceptanceStore = useInvitationAcceptanceStore();
const { invitation } = storeToRefs(acceptanceStore);

const loadingInvitation = ref(true);
const submitting = ref(false);
const accepted = ref(false);

const pageError = ref(null);
const submitError = ref("");
const validationErrors = ref({});

const acceptedOrganizationName = ref("");

const form = reactive({
  name: "",
  password: "",
  password_confirmation: "",
});

const roleLabel = computed(() => {
  if (invitation.value?.role === "responsavel") return "Responsável";
  if (!invitation.value?.role) {
    return "";
  }

  return invitation.value.role
    .split("-")
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(" ");
});

function fieldError(field) {
  const errors = validationErrors.value[field];

  if (!Array.isArray(errors)) {
    return "";
  }

  return errors[0] ?? "";
}

function resolvePageError(error) {
  const status = error?.response?.status;

  if (status === 404) {
    return {
      title: "Convite não encontrado",

      message:
        "O convite informado não existe ou o endereço utilizado está incorreto.",
    };
  }

  if (status === 410) {
    return {
      title: "Convite indisponível",

      message:
        "Este convite expirou, foi revogado ou já foi utilizado. Peça à pessoa que convidou você um novo convite.",
    };
  }

  return {
    title: "Não foi possível carregar o convite",
    retryable: true,

    message:
      "Ocorreu um erro ao verificar o convite. Tente novamente mais tarde.",
  };
}

async function loadInvitation() {
  loadingInvitation.value = true;

  pageError.value = null;
  invitation.value = null;

  try {
    await acceptanceStore.load(String(route.params.token));
  } catch (error) {
    pageError.value = resolvePageError(error);
  } finally {
    loadingInvitation.value = false;
  }
}

async function authenticateNewUser(result) {
  try {
    authStore.clearAuth();
    authStore.applyAuthPayload(result);

    await authStore.initializeContext();

    await router.replace({
      name: "recipients",
    });

    return true;
  } catch {
    authStore.clearAuth();

    return false;
  }
}

async function handleAccept() {
  if (submitting.value || !invitation.value) {
    return;
  }

  submitting.value = true;

  submitError.value = "";
  validationErrors.value = {};

  const registrationRequired = invitation.value.registration_required;

  const payload = registrationRequired
    ? {
        name: form.name.trim(),

        password: form.password,

        password_confirmation: form.password_confirmation,
      }
    : {};

  try {
    const result = await acceptanceStore.accept(
      String(route.params.token),
      payload,
    );

    acceptedOrganizationName.value =
      result?.organization?.name ?? invitation.value.organization.name;

    accepted.value = true;

    const shouldAuthenticateAutomatically =
      registrationRequired && Boolean(result?.access_token);

    if (shouldAuthenticateAutomatically) {
      await authenticateNewUser(result);
    } else if (authStore.user?.email === invitation.value.email) {
      authStore.clearTenantStores();
      await authStore.fetchMe();
      await authStore.selectOrganization(result.organization);
      await router.replace({ name: "recipients" });
    }
  } catch (error) {
    const status = error?.response?.status;

    if (status === 422) {
      validationErrors.value = error.response?.data?.errors ?? {};

      if (validationErrors.value.care_accesses) {
        submitError.value = validationErrors.value.care_accesses.join(" ");
        return;
      }
      submitError.value = Object.keys(validationErrors.value).length
        ? "Verifique os campos informados."
        : (error.response?.data?.message ??
          "Não foi possível aceitar o convite.");

      return;
    }

    if (status === 404 || status === 410) {
      invitation.value = null;

      pageError.value = resolvePageError(error);

      return;
    }

    submitError.value = "Não foi possível aceitar o convite. Tente novamente.";
  } finally {
    submitting.value = false;
  }
}

async function goToLogin() {
  authStore.clearAuth();
  await router.push({
    name: "login",
  });
}

onMounted(loadInvitation);
</script>

<style scoped>
.invitation-page {
  min-height: 100vh;

  display: grid;
  place-items: center;

  padding: var(--space-6);

  background:
    radial-gradient(
      circle at top left,
      var(--color-surface-accent),
      transparent 34rem
    ),
    var(--color-page);
}

.invitation-page__container {
  width: min(100%, 34rem);
}

.invitation-page__brand {
  margin-bottom: var(--space-8);

  text-align: center;
}

.invitation-page__brand .app-logo {
  display: inline-flex;

  font-size: var(--font-size-3xl);

  font-weight: var(--font-weight-bold);
}

.invitation-page__brand-description {
  margin: var(--space-3) 0 0;

  color: var(--color-text-soft);

  font-size: var(--font-size-sm);
}

.invitation-page__card {
  box-shadow: var(--shadow-md);
}

.invitation-page__header {
  margin-bottom: var(--space-6);
}

.invitation-page__eyebrow {
  margin: 0 0 var(--space-2);

  color: var(--color-brand);

  font-size: var(--font-size-xs);

  font-weight: var(--font-weight-semibold);

  text-transform: uppercase;
}

.invitation-page__title {
  margin: 0;

  color: var(--color-text);

  font-size: var(--font-size-2xl);

  font-weight: var(--font-weight-semibold);
}

.invitation-page__description {
  margin: var(--space-2) 0 0;

  color: var(--color-text-soft);

  font-size: var(--font-size-sm);

  line-height: var(--line-height-relaxed);
}

.invitation-page__summary {
  display: grid;

  gap: var(--space-3);

  margin-bottom: var(--space-6);

  padding: var(--space-4);

  border: var(--border-width) solid var(--color-border);

  border-radius: var(--radius-md);

  background: var(--color-surface-soft);
}

.invitation-page__summary-row {
  display: flex;

  justify-content: space-between;

  gap: var(--space-4);
}

.invitation-page__summary-label {
  color: var(--color-text-muted);

  font-size: var(--font-size-sm);
}

.invitation-page__summary-value {
  min-width: 0;

  overflow: hidden;

  color: var(--color-text);

  font-size: var(--font-size-sm);

  text-align: right;

  text-overflow: ellipsis;

  white-space: nowrap;
}

.invitation-page__registration {
  margin-bottom: var(--space-1);
}

.invitation-page__section-title {
  margin: 0;

  color: var(--color-text);

  font-size: var(--font-size-lg);

  font-weight: var(--font-weight-semibold);
}

.invitation-page__section-description {
  margin: var(--space-1) 0 0;

  color: var(--color-text-soft);

  font-size: var(--font-size-sm);
}

.invitation-page__form {
  display: grid;

  gap: var(--space-5);
}

.invitation-page__existing-user {
  padding: var(--space-4);

  border: var(--border-width) solid var(--color-border);

  border-radius: var(--radius-md);

  background: var(--color-surface-muted);

  color: var(--color-text-soft);

  font-size: var(--font-size-sm);

  line-height: var(--line-height-relaxed);
}

.invitation-page__existing-user p {
  margin: 0;
}

.invitation-page__error {
  padding: var(--space-3) var(--space-4);

  border: var(--border-width) solid var(--color-danger);

  border-radius: var(--radius-md);

  background: var(--color-danger-soft);

  color: var(--color-danger);

  font-size: var(--font-size-sm);
}

.invitation-page__state {
  display: flex;

  flex-direction: column;
  align-items: center;

  text-align: center;
}

.invitation-page__state .invitation-page__description {
  margin-bottom: var(--space-6);
}

.invitation-page__status-icon {
  width: 3.5rem;
  height: 3.5rem;

  display: grid;
  place-items: center;

  margin-bottom: var(--space-4);

  border-radius: var(--radius-pill);
}

.invitation-page__status-icon--success {
  background: var(--color-success-soft);

  color: var(--color-success);
}

.invitation-page__status-icon--danger {
  background: var(--color-danger-soft);

  color: var(--color-danger);
}

.invitation-page__footer {
  display: flex;

  justify-content: center;

  gap: var(--space-2);

  margin-top: var(--space-6);

  color: var(--color-text-muted);

  font-size: var(--font-size-xs);
}

@media (max-width: 30rem) {
  .invitation-page {
    align-items: start;

    padding: var(--space-8) var(--space-4);
  }

  .invitation-page__brand {
    margin-bottom: var(--space-6);
  }

  .invitation-page__summary-row {
    flex-direction: column;

    gap: var(--space-1);
  }

  .invitation-page__summary-value {
    text-align: left;
  }
}
</style>
