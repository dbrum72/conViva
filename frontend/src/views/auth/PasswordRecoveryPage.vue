<template>
  <AuthShell>
    <section class="recovery-page" aria-labelledby="recovery-title">
      <h1 id="recovery-title">
        {{ resetting ? "Defina uma nova senha" : "Recupere seu acesso" }}
      </h1>
      <p>
        {{
          resetting
            ? "Use uma senha de 8 a 72 caracteres."
            : "Informe seu e-mail para receber um link de recuperação."
        }}
      </p>
      <AppCard>
        <div v-if="message" role="status" class="care-form">
          <p>{{ message }}</p>
          <RouterLink :to="{ name: 'login' }">Voltar para o login</RouterLink>
        </div>
        <form v-else class="care-form" @submit.prevent="submit">
          <AppEmail
            id="recovery-email"
            v-model="form.email"
            label="E-mail"
            autocomplete="email"
            required
            :disabled="store.pending"
            :error="errors.email?.[0]"
          />
          <template v-if="resetting">
            <AppPassword
              id="recovery-password"
              v-model="form.password"
              label="Nova senha"
              autocomplete="new-password"
              required
              :disabled="store.pending"
              :error="errors.password?.[0]"
            />
            <AppPassword
              id="recovery-confirmation"
              v-model="form.password_confirmation"
              label="Confirme a nova senha"
              autocomplete="new-password"
              required
              :disabled="store.pending"
            />
          </template>
          <p v-if="error || retryMessage" role="alert" class="care-error">
            {{ retryMessage || error }}
          </p>
          <RouterLink v-if="errors.token" :to="{ name: 'password.forgot' }"
            >Solicitar novo link</RouterLink
          >
          <AppButton
            variant="primary"
            type="submit"
            :loading="store.pending"
            :disabled="store.pending || remaining > 0"
            >{{
              resetting ? "Salvar nova senha" : "Enviar instruções"
            }}</AppButton
          >
        </form>
      </AppCard>
      <RouterLink v-if="!message" :to="{ name: 'login' }"
        >Voltar para o login</RouterLink
      >
    </section>
  </AuthShell>
</template>

<script setup>
import { computed, reactive, ref, watch } from "vue";
import { useRoute } from "vue-router";
import AuthShell from "@/components/public/AuthShell.vue";
import { AppEmail, AppPassword } from "@/components/forms";
import { AppButton, AppCard } from "@/components/ui";
import { usePasswordRecoveryStore } from "@/state/password-recovery.js";
import { useAuthStore } from "@/state/auth.js";
import { useRetryCountdown } from "@/composables/useRetryCountdown";

const route = useRoute(),
  store = usePasswordRecoveryStore(),
  auth = useAuthStore();
const resetting = computed(() => route.name === "password.reset");
const form = reactive({ email: "", password: "", password_confirmation: "" });
const message = ref(""),
  error = ref(""),
  errors = ref({});
const { remaining, message: retryMessage, start } = useRetryCountdown();
watch(
  () => route.fullPath,
  () => {
    form.email = typeof route.query.email === "string" ? route.query.email : "";
    form.password = form.password_confirmation = "";
    message.value = error.value = "";
    errors.value = {};
  },
  { immediate: true },
);
async function submit() {
  if (store.pending || remaining.value > 0) return;
  error.value = "";
  errors.value = {};
  try {
    const result = await store.submit(
      {
        ...form,
        email: form.email.trim(),
        token: typeof route.query.token === "string" ? route.query.token : "",
      },
      resetting.value,
    );
    if (resetting.value) auth.clearAuth();
    message.value = result.message;
    form.password = form.password_confirmation = "";
  } catch (e) {
    if (e.response?.status === 429) start(e);
    errors.value = e.response?.data?.errors || {};
    error.value =
      Object.values(errors.value).flat().join(" ") ||
      "Não foi possível concluir. Tente novamente.";
  }
}
</script>

<style scoped>
.recovery-page {
  display: grid;
  gap: var(--space-5);
  min-width: 0;
}
.recovery-page h1 {
  font-size: clamp(1.5rem, 5vw, 2rem);
}
.recovery-page p {
  overflow-wrap: anywhere;
}
.recovery-page .care-form {
  display: grid;
  gap: var(--space-5);
}
.recovery-page a {
  color: var(--color-primary);
  text-decoration: underline;
}
</style>
