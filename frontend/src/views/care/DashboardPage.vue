<template>
  <CareShell
    class="care-dashboard"
    title="Cuidar, juntos"
    subtitle="Um olhar sobre quem precisa de você e os próximos cuidados."
    ><template #actions
      ><RouterLink
        class="btn btn--primary"
        :to="{ name: 'organizations.select' }"
        >Ver assistidos</RouterLink
      ></template
    >
    <div class="care-grid">
      <AppCard
        class="dashboard-stat dashboard-stat--blue"
        title="Próximos cuidados"
        ><span class="dashboard-icon"
          ><AppIcon name="calendar" :size="24" decorative /></span
        ><strong class="care-metric">{{ pending.length }}</strong>
        <p class="care-muted">Compromissos e tarefas pendentes</p></AppCard
      ><AppCard class="dashboard-stat dashboard-stat--pink" title="Novidades"
        ><span class="dashboard-icon"
          ><AppIcon name="email" :size="24" decorative /></span
        ><strong class="care-metric">{{
          store.notifications.filter((n) => !n.read_at).length
        }}</strong>
        <p class="care-muted">Atualizações para você</p></AppCard
      >
    </div>
    <AppCard class="dashboard-agenda" title="Na sua agenda"
      ><div v-for="e in pending.slice(0, 6)" :key="e.id" class="care-list-row">
        <div>
          <RouterLink
            :to="{ name: 'recipient', params: { id: e.care_recipient_id } }"
            ><strong>{{ e.title }}</strong></RouterLink
          >
          <p class="care-muted">
            {{ e.recipient.name }} · {{ entryKinds[e.kind] }}
          </p>
        </div>
        <span>{{ dateTime(e.due_at) }}</span>
      </div>
      <div v-if="!pending.length" class="dashboard-empty">
        <span class="dashboard-empty__icon"
          ><AppIcon name="calendar" :size="30" decorative
        /></span>
        <h2>Espaço para os próximos cuidados</h2>
        <p>
          Nenhum cuidado pendente por aqui. Consulte o perfil do assistido para
          organizar os próximos passos.
        </p>
        <RouterLink class="dashboard-link" :to="{ name: 'recipients' }"
          >Abrir perfil do assistido
          <AppIcon name="arrow-right" :size="18" decorative
        /></RouterLink></div></AppCard
  ></CareShell>
</template>
<script setup>
import { computed, onMounted } from "vue";
import CareShell from "@/components/care/CareShell.vue";
import { AppCard, AppIcon } from "@/components/ui";
import { useCareStore } from "@/state/care";
import { entryKinds, dateTime } from "@/utils/care";
const store = useCareStore();
const pending = computed(() =>
  store.agenda.filter((e) => e.status !== "completed"),
);
onMounted(() =>
  Promise.all([
    store.loadAgenda(),
    store.loadNotifications(),
  ]).catch(() => {}),
);
</script>

<style scoped>
.care-dashboard :deep(.care-heading) {
  position: relative;
  padding: clamp(1.25rem, 3vw, 2rem);
  border-radius: 1.5rem;
  background: linear-gradient(
    110deg,
    var(--palette-baby-green),
    #eef5eb 48%,
    var(--palette-baby-blue)
  );
  border: 1px solid rgb(86 88 115 / 10%);
  border-bottom: 4px solid var(--palette-care-yellow);
}
.care-dashboard :deep(.care-muted) {
  color: var(--color-text-soft);
}
.dashboard-stat {
  position: relative;
  border: 1px solid rgb(86 88 115 / 12%);
  border-radius: 1.25rem;
}
.dashboard-stat--blue {
  background: var(--palette-baby-blue);
}
.dashboard-stat--pink {
  background: var(--palette-baby-pink);
}
.dashboard-stat :deep(.card__header) {
  border: 0;
  padding-bottom: 0;
}
.dashboard-stat :deep(.card__title) {
  color: var(--color-text);
  font-size: 1rem;
}
.dashboard-stat :deep(.card__body) {
  position: relative;
  padding-top: 1rem;
}
.dashboard-stat .care-metric {
  display: block;
  font-size: 3rem;
  line-height: 1.15;
  color: var(--color-brand-active);
}
.dashboard-stat .care-muted {
  margin: 0.7rem 0 0;
  padding-right: 2rem;
}
.dashboard-icon {
  position: absolute;
  right: 1.25rem;
  top: 0.75rem;
  display: grid;
  place-items: center;
  width: 3rem;
  height: 3rem;
  border-radius: 1rem;
  background: rgb(255 255 255 / 60%);
  color: var(--color-brand);
}
.dashboard-agenda {
  border-radius: 1.25rem;
}
.dashboard-agenda :deep(.card__header) {
  background: var(--color-surface-warning-soft);
  border-bottom: 1px solid var(--palette-care-yellow);
}
.dashboard-agenda .care-list-row {
  padding: 1rem;
  border-radius: 0.75rem;
  margin-bottom: 0.5rem;
  background: #f1f7fb;
  border: 0;
}
.dashboard-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 1.5rem 1rem 2rem;
}
.dashboard-empty__icon {
  display: grid;
  place-items: center;
  width: 4.5rem;
  height: 4.5rem;
  border-radius: 1.5rem;
  color: var(--color-brand);
  background: var(--palette-baby-blue);
  box-shadow:
    -12px 6px 0 -3px var(--palette-baby-pink),
    12px -6px 0 -3px var(--palette-baby-green);
  margin: 0.5rem 0 1.25rem;
}
.dashboard-empty h2 {
  font-size: 1.15rem;
  color: var(--color-brand);
  margin: 0;
}
.dashboard-empty p {
  max-width: 34rem;
  color: var(--color-text-soft);
  line-height: 1.7;
  margin: 0.65rem 0 1rem;
}
.dashboard-link {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  color: var(--color-brand-active);
  font-weight: 600;
  text-underline-offset: 4px;
}
.dashboard-link:focus-visible {
  outline: 2px solid var(--color-brand);
  outline-offset: 5px;
  border-radius: 4px;
}
@media (max-width: 600px) {
  .care-dashboard :deep(.care-heading) {
    align-items: flex-start;
    flex-direction: column;
  }
  .dashboard-agenda .care-list-row {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
