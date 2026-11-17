<template>
  <CareShell
    title="Notificações"
    subtitle="Atualizações dos cuidados que você pode acompanhar."
    @retry="store.loadNotifications().catch(() => {})"
  >
    <p v-if="store.error" role="alert">{{ store.error }}</p>
    <AppCard v-if="settings" title="Lembretes deste grupo">
      <AppForm class="care-form" @submit.prevent="save">
        <AppCheckbox
          label="Notificações internas"
          :id="fieldId + '-1'"
          v-model="settings.internal"
        />
        <AppCheckbox
          label="E-mail"
          :id="fieldId + '-2'"
          v-model="settings.email"
        />
        <AppCheckbox
          label="Incluir ocorrências que posso consultar e estão sob responsabilidade de outras pessoas"
          :id="fieldId + '-3'"
          v-model="settings.third_party"
        />
        <AppCheckbox
          label="Resumo diário a partir das 18h no fuso do grupo"
          :id="fieldId + '-4'"
          v-model="settings.summary"
        />
        <AppInput
          label="Antecedência em minutos"
          :id="fieldId + '-5'"
          v-model.number="settings.advance"
          type="number"
          min="5"
          max="1440"
          required
        />
        <AppInput
          label="Início do silêncio no fuso do grupo"
          :id="fieldId + '-6'"
          v-model="settings.quiet_start"
          type="time"
          :required="!!settings.quiet_end"
        />
        <AppInput
          label="Fim do silêncio no fuso do grupo"
          :id="fieldId + '-7'"
          v-model="settings.quiet_end"
          type="time"
          :required="!!settings.quiet_start"
        />
        <AppButton type="submit" :disabled="store.pending"
          >Salvar preferências</AppButton
        >
        <p v-if="saved" role="status">Preferências salvas.</p>
      </AppForm>
    </AppCard>
    <AppCard v-for="n in store.notifications" :key="n.id"
      ><div class="care-list-row">
        <div>
          <RouterLink
            :to="
              n.destination?.type === 'agenda'
                ? { name: 'agenda', query: { date: n.destination.date } }
                : n.destination
                  ? {
                      name: 'decisions',
                      query: {
                        type: n.destination.type,
                        proposal: n.destination.proposal,
                      },
                    }
                  : n.area === 'expenses'
                    ? { name: 'expenses' }
                    : { name: 'recipient', params: { id: n.care_recipient_id } }
            "
            ><strong>{{ n.message }}</strong></RouterLink
          >
          <p>{{ n.recipient?.name }} · {{ dateTime(n.created_at) }}</p>
        </div>
        <AppButton
          variant="action"
          v-if="!n.read_at"
          @click="store.read(n.id).catch(() => {})"
          >Marcar como lida</AppButton
        ><span v-else>Lida</span>
      </div></AppCard
    >
    <p
      v-if="!store.notifications.length && !store.pending && !store.error"
      class="care-empty"
    >
      Você está em dia. Nenhuma atualização disponível.
    </p>
    <nav
      v-if="store.pagination?.last > 1"
      aria-label="Páginas de notificações"
      class="care-actions"
    >
      <AppButton
        :disabled="store.pending || store.pagination.current <= 1"
        @click="load(store.pagination.current - 1)"
        >Anterior</AppButton
      >
      <span>{{ store.pagination.current }} / {{ store.pagination.last }}</span>
      <AppButton
        :disabled="
          store.pending || store.pagination.current >= store.pagination.last
        "
        @click="load(store.pagination.current + 1)"
        >Próxima</AppButton
      >
    </nav>
  </CareShell>
</template>
<script setup>
import { AppForm, AppCheckbox, AppInput } from "@/components/forms";
import { useId } from "vue";
const fieldId = useId();
import { ref, watch } from "vue";
import { CareShell } from "@/modules/care/ui.js";
import { AppCard, AppButton } from "@/components/ui";
import { useNotificationsStore } from "../store.js";
import { dateTime } from "@/modules/care";
import { useAuthStore } from "@/modules/auth";
const store = useNotificationsStore();
const auth = useAuthStore();
const settings = ref(null);
const saved = ref(false);
const load = (page) => store.loadNotifications(page).catch(() => {});
watch(
  () => store.preferences,
  (value) => {
    settings.value = value ? { ...value } : null;
  },
);
watch(
  () => auth.currentTenant,
  (tenant) => {
    saved.value = false;
    if (tenant) {
      load();
      store.loadPreferences().catch(() => {});
    }
  },
  { immediate: true },
);
async function save() {
  saved.value = false;
  const tenant = auth.currentTenant;
  try {
    await store.savePreferences({
      ...settings.value,
      quiet_start: settings.value.quiet_start || null,
      quiet_end: settings.value.quiet_end || null,
    });
    saved.value = tenant === auth.currentTenant;
  } catch {}
}
</script>
