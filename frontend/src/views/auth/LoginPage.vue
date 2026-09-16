<template>
  <AuthShell>
    <section class="login-page" aria-labelledby="login-title">
      <header class="login-page__header">
        <span class="login-page__eyebrow"> Bem-vindo de volta </span>

        <h1 id="login-title" class="login-page__title">Acesse sua conta</h1>

        <p class="login-page__description">
          Informe suas credenciais para continuar.
        </p>
      </header>

      <AppCard class="login-page__card" as="section">
        <form
          class="login-page__form"
          novalidate
          @submit.prevent="handleSubmit"
        >
          <AppEmail
            id="login-email"
            v-model="form.email"
            name="email"
            label="E-mail"
            placeholder="seu@email.com"
            :disabled="loading"
            :error="errors.email"
            required
            autofocus
          />

          <AppPassword
            id="login-password"
            v-model="form.password"
            name="password"
            label="Senha"
            autocomplete="current-password"
            :disabled="loading"
            :error="errors.password"
            required
          />

          <div
            v-if="retryMessage || authError"
            class="login-page__error"
            role="alert"
          >
            {{ retryMessage || authError }}
          </div>

          <AppButton
            type="submit"
            variant="primary"
            size="lg"
            :loading="loading"
            :disabled="loading || retryRemaining > 0"
            block
          >
            Entrar
          </AppButton>
        </form>
      </AppCard>

      <footer class="login-page__footer">
        <span> Ainda não possui uma conta? </span>

        <RouterLink :to="{ name: 'register' }"> Criar conta </RouterLink>
      </footer>
    </section>
  </AuthShell>
</template>

<script setup>
import { useRetryCountdown } from "@/composables/useRetryCountdown";
import { reactive, ref } from "vue";

import { useRouter } from "vue-router";

import { AppEmail, AppPassword } from "@/components/forms";

import AuthShell from "@/components/public/AuthShell.vue";

import { AppButton, AppCard } from "@/components/ui";

import { useAuthStore } from "@/state/auth.js";

const router = useRouter();

const authStore = useAuthStore();

const loading = ref(false);

const authError = ref("");

const {
  remaining: retryRemaining,
  message: retryMessage,
  start: startRetryCountdown,
} = useRetryCountdown();

const form = reactive({
  email: "",
  password: "",
});

const errors = reactive({
  email: "",
  password: "",
});

function clearErrors() {
  errors.email = "";
  errors.password = "";
  authError.value = "";
}

function validate() {
  clearErrors();

  if (!form.email.trim()) {
    errors.email = "Informe seu e-mail.";
  }

  if (!form.password) {
    errors.password = "Informe sua senha.";
  }

  return !errors.email && !errors.password;
}

async function handleSubmit() {
  if (loading.value || retryRemaining.value > 0 || !validate()) {
    return;
  }

  loading.value = true;

  try {
    await authStore.login({
      email: form.email.trim(),

      password: form.password,
    });

    await router.replace({ name: "organizations.select" });
  } catch (error) {
    const status = error.response?.status;

    if (status === 429) {
      startRetryCountdown(error);
      return;
    }

    if (status === 422) {
      const validationErrors = error.response?.data?.errors ?? {};

      errors.email = validationErrors.email?.[0] ?? "";

      errors.password = validationErrors.password?.[0] ?? "";

      if (!errors.email && !errors.password) {
        authError.value = "Verifique os dados informados.";
      }

      return;
    }

    if (status === 401 || status === 403) {
      authError.value =
        error.response?.data?.msg ?? "E-mail ou senha inválidos.";

      return;
    }

    authError.value = "Não foi possível acessar sua conta. Tente novamente.";
  } finally {
    loading.value = false;
  }
}
</script>

<style>
.login-page {
  width: 100%;
}

.login-page__header {
  margin-bottom: var(--space-6);
}

.login-page__eyebrow {
  display: inline-block;
  margin-bottom: var(--space-3);
  color: var(--color-primary);
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-semibold);
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.login-page__title {
  margin: 0;
  color: var(--color-text);
  font-size: clamp(var(--font-size-2xl), 5vw, var(--font-size-3xl));
  font-weight: var(--font-weight-semibold);
  line-height: 1.15;
}

.login-page__description {
  margin: var(--space-2) 0 0;
  color: var(--color-text-soft);
  font-size: var(--font-size-sm);
  line-height: 1.6;
}

.login-page__card {
  width: 100%;
  box-shadow: var(--shadow-md);
}

.login-page__form {
  display: grid;
  gap: var(--space-5);
}

.login-page__error {
  padding: var(--space-3) var(--space-4);
  border: 1px solid var(--color-danger);
  border-radius: var(--radius-md);
  background: var(--color-danger-soft);
  color: var(--color-danger);
  font-size: var(--font-size-sm);
}

.login-page__footer {
  display: flex;
  justify-content: center;
  gap: var(--space-2);
  margin-top: var(--space-6);
  color: var(--color-text-muted);
  font-size: var(--font-size-sm);
}

.login-page__footer a {
  color: var(--color-primary);
  font-weight: var(--font-weight-semibold);
  text-decoration: none;
}

.login-page__footer a:hover {
  text-decoration: underline;
}

@media (max-width: 30rem) {
  .login-page__header {
    margin-bottom: var(--space-5);
  }

  .login-page__footer {
    flex-wrap: wrap;
    text-align: center;
  }
}
</style>
