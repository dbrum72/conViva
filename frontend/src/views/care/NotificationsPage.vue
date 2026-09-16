<template>
  <CareShell
    title="Notificações"
    subtitle="Atualizações dos cuidados que você pode acompanhar."
    ><AppCard v-for="n in store.notifications" :key="n.id"
      ><div class="care-list-row">
        <div>
          <RouterLink
            :to="{ name: 'recipient', params: { id: n.care_recipient_id } }"
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
    <p v-if="!store.notifications.length" class="care-empty">
      Você está em dia. Nenhuma atualização disponível.
    </p></CareShell
  >
</template>
<script setup>
import { onMounted } from "vue";
import CareShell from "@/components/care/CareShell.vue";
import { AppCard, AppButton } from "@/components/ui";
import { useCareStore } from "@/state/care";
import { dateTime } from "@/utils/care";
const store = useCareStore();
onMounted(() => store.loadNotifications().catch(() => {}));
</script>
