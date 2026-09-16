<template>
  <CareShell
    title="Assistido do grupo"
    subtitle="Cada grupo cuida de uma pessoa ou pet. Para outro assistido, crie outro grupo."
    ><template #actions
      ><AppButton
        variant="primary"
        v-if="
          auth.hasPermission('recipients.create') &&
          !store.pending &&
          !store.recipients.length &&
          !auth.organization?.has_recipient
        "
        @click="openForm()"
        >Cadastrar assistido</AppButton
      ></template
    >
    <RouterLink :to="{ name: 'organizations.select' }"
      >Trocar ou criar grupo</RouterLink
    >
    <div class="care-grid">
      <AppCard v-for="p in store.recipients" :key="p.id"
        ><div class="care-person">
          <span class="care-avatar">{{
            p.kind === "pet" ? "🐾" : p.name.slice(0, 1)
          }}</span>
          <div>
            <h2>{{ p.name }}</h2>
            <p class="care-muted">{{ recipientKinds[p.kind] }}</p>
          </div>
        </div>
        <p>{{ p.species || "Uma rede de cuidado por perto" }}</p>
        <span v-if="p.status === 'archived'" class="care-pill">Arquivado</span>
        <div class="care-actions">
          <RouterLink
            class="btn btn--primary"
            :to="{ name: 'recipient', params: { id: p.id } }"
            >Abrir cuidados</RouterLink
          ><AppButton
            variant="outline"
            v-if="p.can_manage_profile"
            @click="openForm(p)"
            >Editar</AppButton
          ><AppButton
            variant="ghost"
            v-if="p.can_manage_profile && p.status !== 'archived'"
            @click="archive(p)"
            >Arquivar</AppButton
          >
        </div></AppCard
      >
    </div>
    <p v-if="!store.recipients.length && !store.pending" class="care-empty">
      Sua rede começa aqui. Cadastre um assistido ou aguarde a concessão de
      acesso por um responsável.
    </p>
    <dialog ref="dialog" class="care-dialog">
      <form @submit.prevent="save">
        <h2>{{ form.id ? "Editar assistido" : "Novo assistido" }}</h2>
        <label class="care-field"
          >Nome<input v-model="form.name" required maxlength="150" /></label
        ><label class="care-field"
          >Tipo<select v-model="form.kind">
            <option v-for="(label, key) in recipientKinds" :value="key">
              {{ label }}
            </option>
          </select></label
        ><label class="care-field"
          >Data de nascimento<input
            type="date"
            v-model="form.birth_date" /></label
        ><template v-if="form.kind === 'pet'"
          ><label class="care-field"
            >Espécie<input
              v-model="form.species"
              required
              placeholder="Cachorro, gato…" /></label
          ><label class="care-field">Raça<input v-model="form.breed" /></label
        ></template>
        <p v-if="store.error" role="alert" class="care-error">
          {{ store.error }}
        </p>
        <div class="care-actions">
          <AppButton variant="outline" @click="dialog.close()"
            >Cancelar</AppButton
          ><AppButton variant="action" type="submit" :loading="!!store.pending"
            >Salvar</AppButton
          >
        </div>
      </form>
    </dialog></CareShell
  >
</template>
<script setup>
import { ref, onMounted } from "vue";
import CareShell from "@/components/care/CareShell.vue";
import { AppCard, AppButton } from "@/components/ui";
import { useAuthStore } from "@/state/auth";
import { useCareStore } from "@/state/care";
import { recipientKinds } from "@/utils/care";
const store = useCareStore(),
  auth = useAuthStore(),
  dialog = ref(),
  form = ref({});
function openForm(p) {
  store.error = "";
  form.value = p
    ? { ...p }
    : { name: "", kind: "child", birth_date: "", species: "", breed: "" };
  dialog.value.showModal();
}
async function save() {
  try {
    await store.saveRecipient(form.value);
    dialog.value.close();
  } catch {}
}
async function archive(p) {
  if (window.confirm("Arquivar " + p.name + "? O histórico será preservado."))
    await store.archive(p.id).catch(() => {});
}
onMounted(() => store.loadRecipients().catch(() => {}));
</script>
