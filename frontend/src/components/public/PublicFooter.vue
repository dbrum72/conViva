<template>
  <footer class="public-footer" data-testid="public-footer">
    <div class="public-footer__container">
      <div class="public-footer__brand">
        <strong> conViva </strong>

        <span> Gestão de cuidados </span>
      </div>

      <p class="public-footer__copyright">
        © {{ currentYear }} conViva. Todos os direitos reservados.
      </p>
    </div>
  </footer>
</template>

<script setup>
const currentYear = new Date().getFullYear();
</script>

<style scoped>
.public-footer {
  border-top: 1px solid var(--color-border, #e2dfd4);
  background: var(--color-surface, #fff);
}

.public-footer__container {
  width: min(100% - 2rem, 75rem);
  min-height: 6rem;
  margin-inline: auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
}

.public-footer__brand {
  display: flex;
  align-items: baseline;
  gap: 0.75rem;
}

.public-footer__brand strong {
  color: var(--color-text, #252923);
}

.public-footer__brand span,
.public-footer__copyright {
  color: var(--color-text-muted, #6d7069);
  font-size: 0.825rem;
}

.public-footer__copyright {
  margin: 0;
}

@media (max-width: 575.98px) {
  .public-footer__container {
    width: min(100% - 1.5rem, 75rem);
    min-height: 7rem;
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
    gap: 0.65rem;
  }

  .public-footer__brand {
    flex-direction: column;
    gap: 0.2rem;
  }
}
</style>
