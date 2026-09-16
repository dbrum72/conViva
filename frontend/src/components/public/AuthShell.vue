<template>
  <main class="auth-shell" data-testid="auth-shell">
    <section
      class="auth-shell__presentation"
      data-testid="auth-shell-presentation"
    >
      <div class="auth-shell__presentation-content">
        <AppLogo
          class="auth-shell__logo"
          :to="{ name: 'home' }"
          aria-label="conViva - Página inicial"
          data-testid="auth-shell-logo"
        />

        <div class="auth-shell__message">
          <p class="auth-shell__eyebrow">Gestão de cuidados</p>

          <h1 class="auth-shell__headline">
            Seu grupo de cuidados organizado. Seu trabalho mais simples.
          </h1>

          <p class="auth-shell__description">
            Centralize assistidos, cuidados, prazos e a rotina do seu grupo de
            cuidados em um ambiente seguro e organizado.
          </p>
        </div>
      </div>
    </section>

    <section class="auth-shell__content" data-testid="auth-shell-content">
      <div class="auth-shell__content-inner">
        <slot />
      </div>
    </section>
  </main>
</template>

<script setup>
import { AppLogo } from "@/components/ui";
</script>

<style scoped>
.auth-shell {
  min-height: 100vh;
  display: grid;
  grid-template-columns: minmax(20rem, 0.9fr) minmax(28rem, 1.1fr);
  background: var(--color-page);
}

.auth-shell__presentation {
  position: relative;
  display: flex;
  min-height: 100vh;
  padding: clamp(2rem, 5vw, 5rem);
  overflow: hidden;
  background: linear-gradient(
    145deg,
    var(--color-brand),
    color-mix(in srgb, var(--color-brand) 82%, black)
  );
}

.auth-shell__presentation::before {
  position: absolute;
  top: -10rem;
  right: -10rem;
  width: 28rem;
  height: 28rem;
  border-radius: 50%;
  background: color-mix(in srgb, var(--color-surface-accent) 14%, transparent);
  content: "";
}

.auth-shell__presentation::after {
  position: absolute;
  bottom: -13rem;
  left: -10rem;
  width: 32rem;
  height: 32rem;
  border: 1px solid color-mix(in srgb, white 12%, transparent);
  border-radius: 50%;
  content: "";
}

.auth-shell__presentation-content {
  position: relative;
  z-index: 1;
  display: flex;
  width: min(100%, 34rem);
  flex-direction: column;
}

.auth-shell__logo {
  align-self: flex-start;
  color: white;
  font-size: var(--font-size-3xl);
  font-weight: var(--font-weight-bold);
}

.auth-shell__message {
  margin: auto 0;
}

.auth-shell__eyebrow {
  margin: 0 0 var(--space-4);
  color: color-mix(in srgb, white 72%, transparent);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-semibold);
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.auth-shell__headline {
  max-width: 32rem;
  margin: 0;
  color: white;
  font-size: clamp(2.25rem, 4vw, 3.75rem);
  font-weight: var(--font-weight-bold);
  line-height: 1.08;
  letter-spacing: -0.035em;
}

.auth-shell__description {
  max-width: 30rem;
  margin: var(--space-6) 0 0;
  color: color-mix(in srgb, white 78%, transparent);
  font-size: var(--font-size-base);
  line-height: 1.7;
}

.auth-shell__content {
  display: flex;
  min-height: 100vh;
  align-items: center;
  justify-content: center;
  padding: clamp(2rem, 5vw, 5rem);
  background:
    radial-gradient(
      circle at top right,
      var(--color-surface-accent),
      transparent 28rem
    ),
    var(--color-page);
}

.auth-shell__content-inner {
  width: min(100%, 30rem);
}

@media (max-width: 56rem) {
  .auth-shell {
    grid-template-columns: 1fr;
  }

  .auth-shell__presentation {
    min-height: auto;
    padding: var(--space-6) var(--space-5);
  }

  .auth-shell__presentation-content {
    width: 100%;
  }

  .auth-shell__message {
    display: none;
  }

  .auth-shell__content {
    min-height: 0;
    flex: 1;
    padding: var(--space-8) var(--space-5);
  }
}

@media (max-width: 30rem) {
  .auth-shell__presentation {
    padding: var(--space-5) var(--space-4);
  }

  .auth-shell__content {
    align-items: flex-start;
    padding: var(--space-6) var(--space-4);
  }
}
</style>
