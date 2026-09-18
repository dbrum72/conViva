<template>
  <CareShell
    title="Agenda de cuidados"
    subtitle="Compromissos e rotinas dos assistidos que você acompanha."
    @retry="store.loadAgenda().catch(() => {})"
    ><label class="care-check"
      ><input type="checkbox" v-model="showCompleted" />Mostrar
      concluídos</label
    ><AppCard v-for="e in visible" :key="e.id"
      ><div class="care-list-row">
        <div>
          <span class="care-pill">{{ entryKinds[e.kind] }}</span>
          <h2>{{ e.title }}</h2>
          <RouterLink
            :to="{ name: 'recipient', params: { id: e.care_recipient_id } }"
            >{{ e.recipient.name }}</RouterLink
          >
        </div>
        <strong>{{ dateTime(e.due_at) }}</strong>
      </div></AppCard
    >
    <p
      v-if="!visible.length && !store.pending && !store.error"
      class="care-empty"
    >
      Nenhum cuidado agendado. Abra um assistido e registre uma data.
    </p></CareShell
  >
</template>
<script setup>
import { ref, computed, onMounted } from "vue";
import CareShell from "@/components/care/CareShell.vue";
import { AppCard } from "@/components/ui";
import { useCareStore } from "@/state/care";
import { entryKinds, dateTime } from "@/utils/care";
const store = useCareStore(),
  showCompleted = ref(false);
const visible = computed(() =>
  store.agenda.filter((e) => showCompleted.value || e.status !== "completed"),
);
onMounted(() => store.loadAgenda().catch(() => {}));
</script>
