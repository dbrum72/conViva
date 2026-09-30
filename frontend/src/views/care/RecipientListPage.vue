<template>
  <CareShell
    title="Assistido do grupo"
    subtitle="Cada grupo cuida de uma pessoa ou pet. Para outro assistido, crie outro grupo."
    @retry="store.loadRecipients().catch(() => {})"
    ><template #actions
      ><AppButton
        variant="modal"
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
    <p
      v-if="
        store.recipients.length &&
        auth.hasPermission('organization-members.invite')
      "
      class="care-muted"
    >
      Próximo passo:
      <RouterLink :to="{ name: 'organization-members' }"
        >convide sua rede de cuidados</RouterLink
      >
      e abra o assistido para registrar o primeiro cuidado.
    </p>
    <div class="care-grid">
      <AppCard v-for="p in store.recipients" :key="p.id"
        ><div class="care-person">
          <span class="care-avatar" aria-hidden="true">
            <img
              v-if="
                avatar.url &&
                auth.organization?.can_manage_avatar &&
                p.organization_id === auth.organization?.id
              "
              :src="avatar.url"
              alt=""
            />
            <template v-else>{{
              p.kind === "pet" ? "🐾" : p.name.slice(0, 1)
            }}</template>
          </span>
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
            variant="modal"
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
    <RecipientProfileCard
      v-for="p in store.recipients"
      :key="`profile-${p.id}`"
      :recipient="p"
      @edit="openForm(p)"
    />
    <p
      v-if="!store.recipients.length && !store.pending && !store.error"
      class="care-empty"
    >
      Sua rede começa aqui. Cadastre um assistido ou aguarde a concessão de
      acesso por um responsável.
    </p>
    <AppDialog
      :open="dialogOpen"
      :title="form.id ? 'Propor revisão cadastral' : 'Novo assistido'"
      :busy="!!store.pending"
      :error="store.error"
      @close="dialogOpen = false"
    >
      <form @submit.prevent="save">
        <p v-if="form.id">
          Qualquer responsável com acesso pode propor esta revisão. Se você for
          o único responsável, ela será aplicada imediatamente. Caso contrário,
          o cadastro vigente será preservado até o aceite de todos os demais.
          Acompanhe a proposta na central de decisões.
        </p>
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
        <RecipientProfileFields
          v-model="form"
          :kind="form.kind"
          :health-editable="!form.id || !!form.capabilities?.health?.edit"
        />
        <p v-if="store.error" role="alert" class="care-error">
          {{ store.error }}
        </p>
        <div class="care-actions">
          <AppButton
            variant="cancel"
            :disabled="!!store.pending"
            @click="dialogOpen = false"
            >Cancelar</AppButton
          ><AppButton variant="action" type="submit" :loading="!!store.pending"
            >Salvar</AppButton
          >
        </div>
      </form>
    </AppDialog>
    <AppConfirmDialog
      :open="!!archiving"
      title="Arquivar assistido"
      :message="
        'Propor o arquivamento de ' +
        (archiving?.name || '') +
        '? O cadastro continuará vigente até os aceites necessários. O histórico será preservado.'
      "
      :loading="!!store.pending"
      :error="store.error"
      @cancel="archiving = null"
      @confirm="confirmArchive"
    />
  </CareShell>
</template>
<script setup>
import { ref, onMounted, watch } from "vue";
import { useRouter } from "vue-router";
const router = useRouter();
import RecipientProfileCard from "@/components/care/RecipientProfileCard.vue";
import RecipientProfileFields from "@/components/care/RecipientProfileFields.vue";
import CareShell from "@/components/care/CareShell.vue";
import {
  AppCard,
  AppButton,
  AppDialog,
  AppConfirmDialog,
} from "@/components/ui";
import { useAuthStore } from "@/state/auth";
import { useCareStore } from "@/state/care";
import { useRecipientAvatarStore } from "@/state/recipient-avatar";
import { recipientKinds } from "@/utils/care";
const store = useCareStore(),
  avatar = useRecipientAvatarStore(),
  auth = useAuthStore(),
  dialogOpen = ref(false),
  archiving = ref(null),
  form = ref({});
function openForm(p) {
  store.error = "";
  form.value = p
    ? JSON.parse(JSON.stringify(p))
    : { name: "", kind: "child", birth_date: "", species: "", breed: "" };
  dialogOpen.value = true;
}
async function save() {
  try {
    if (form.value.id) {
      const proposal = await store.proposeProfile(form.value.id, {
        ...form.value,
        operation: "save",
        version: form.value.profile_version,
      });
      if (proposal)
        await router.push({
          name: "decisions",
          query: { type: "profile", proposal: proposal.id },
        });
    } else {
      await store.saveRecipient(form.value);
    }
    dialogOpen.value = false;
  } catch {}
}
async function archive(p) {
  store.error = "";
  archiving.value = p;
}
async function confirmArchive() {
  if (store.pending || !archiving.value) return;
  try {
    const proposal = await store.proposeProfile(archiving.value.id, {
      operation: "archive",
      version: archiving.value.profile_version,
    });
    if (proposal)
      await router.push({
        name: "decisions",
        query: { type: "profile", proposal: proposal.id },
      });
    archiving.value = null;
  } catch {
    /* Store exposes the error in the dialog. */
  }
}
watch(
  () => form.value.kind,
  (kind, previous) => {
    if (!previous || kind === previous || !form.value.routine_profile) return;
    const profile = { ...form.value.routine_profile };
    if (kind !== "child") {
      delete profile.school;
      delete profile.authorized_people;
    }
    if (kind !== "pet") delete profile.identification;
    form.value.routine_profile = profile;
  },
);
watch(
  () => [auth.organization?.id, auth.user?.id],
  () => {
    dialogOpen.value = false;
    archiving.value = null;
    form.value = {};
  },
);
onMounted(() => store.loadRecipients().catch(() => {}));
</script>
