<template>
  <PageContainer>
    <div class="organization-members">
      <header
        class="organization-members__header"
        aria-labelledby="organization-members-title"
      >
        <div class="organization-members__heading">
          <h1
            id="organization-members-title"
            class="organization-members__title"
          >
            Equipe
          </h1>

          <p class="organization-members__description">
            Gerencie os membros, funções e acessos do grupo de cuidados.
          </p>
        </div>

        <AppButton
          variant="navigation"
          v-if="canInvite"
          type="button"
          icon="email"
          @click="openInvitationDialog"
        >
          Convidar membro
        </AppButton>
      </header>

      <div v-if="loadError" class="organization-members__error" role="alert">
        {{ loadError }}
      </div>

      <AppTable
        :columns="columns"
        :rows="membersStore.members"
        empty-text="Nenhum membro encontrado."
      >
        <template #cell-role="{ value }">
          {{ roleLabel(value) }}
        </template>

        <template #cell-status="{ value }">
          <span
            class="organization-members__status"
            :class="statusClass(value)"
          >
            {{ statusLabel(value) }}
          </span>
        </template>

        <template #cell-actions="{ row }">
          <div class="organization-members__actions">
            <AppButton
              variant="outline"
              v-if="canUpdateRole && row.can_update_role"
              type="button"
              size="sm"
              :aria-label="`Alterar função de ${row.name}`"
              @click="openRoleDialog(row)"
            >
              Alterar função
            </AppButton>

            <AppButton
              :variant="row.status === 'active' ? 'ghost' : 'outline'"
              v-if="canUpdateStatus && row.can_update_status"
              type="button"
              size="sm"
              :aria-label="
                row.status === 'active'
                  ? `Desativar ${row.name}`
                  : `Reativar ${row.name}`
              "
              @click="requestStatusChange(row)"
            >
              {{ row.status === "active" ? "Desativar" : "Reativar" }}
            </AppButton>
          </div>
        </template>
      </AppTable>

      <section
        v-if="canInvite"
        class="organization-members__invitations"
        aria-labelledby="organization-invitations-title"
      >
        <header class="organization-members__section-header">
          <div>
            <h2
              id="organization-invitations-title"
              class="organization-members__section-title"
            >
              Convites
            </h2>

            <p class="organization-members__section-description">
              Acompanhe os convites enviados e gerencie os acessos pendentes.
            </p>
          </div>
        </header>

        <div
          v-if="invitationLoadError"
          class="organization-members__error"
          role="alert"
        >
          {{ invitationLoadError }}
        </div>

        <div
          v-if="invitationActionError"
          class="organization-members__error"
          role="alert"
        >
          {{ invitationActionError }}
        </div>

        <div
          v-if="invitationsStore.fetching"
          class="organization-members__state"
          role="status"
        >
          Carregando convites...
        </div>

        <AppTable
          v-else
          :columns="invitationColumns"
          :rows="invitationsStore.invitations"
          empty-text="Nenhum convite encontrado."
        >
          <template #cell-role="{ value }">
            {{ roleLabel(value) }}
          </template>

          <template #cell-status="{ value }">
            <span
              class="organization-members__status"
              :class="invitationStatusClass(value)"
            >
              {{ invitationStatusLabel(value) }}
            </span>
          </template>

          <template #cell-expires_at="{ value }">
            {{ formatDate(value) }}
          </template>

          <template #cell-inviter="{ value }">
            {{ value?.name || "—" }}
          </template>

          <template #cell-actions="{ row }">
            <div
              v-if="row.status === 'pending'"
              class="organization-members__actions"
            >
              <AppButton
                variant="action"
                type="button"
                size="sm"
                :loading="invitationsStore.resendingId === row.id"
                :disabled="invitationActionInProgress"
                :aria-label="`Reenviar convite para ${row.email}`"
                @click="resendInvitation(row)"
              >
                Reenviar
              </AppButton>

              <AppButton
                variant="ghost"
                type="button"
                size="sm"
                :disabled="invitationActionInProgress"
                :aria-label="`Revogar convite de ${row.email}`"
                @click="requestInvitationRevocation(row)"
              >
                Revogar
              </AppButton>
            </div>

            <span
              v-else
              class="organization-members__empty-action"
              aria-hidden="true"
            >
              —
            </span>
          </template>
        </AppTable>
      </section>

      <AppDialog
        :open="invitationDialogOpen"
        title="Convidar membro"
        size="sm"
        :close-on-backdrop="!invitationsStore.creating"
        :close-on-escape="!invitationsStore.creating"
        @close="closeInvitationDialog"
      >
        <form
          class="organization-members__form"
          @submit.prevent="submitInvitation"
        >
          <AppEmail
            v-model="invitationForm.email"
            id="invitation-email"
            name="email"
            label="E-mail"
            placeholder="nome@exemplo.com"
            required
            :disabled="invitationsStore.creating"
            :error="invitationErrors.email"
          />

          <AppSelect
            v-model="invitationForm.role"
            id="invitation-role"
            name="role"
            label="Função"
            placeholder="Selecione uma função"
            required
            :options="rolesStore.options"
            :disabled="invitationsStore.creating"
            :error="invitationErrors.role"
          />

          <fieldset
            class="care-form"
            :disabled="invitationRecipientsLoading || invitationsStore.creating"
          >
            <legend>Assistido do grupo</legend>
            <p v-if="invitationRecipientsLoading">Carregando assistido…</p>
            <p v-for="recipient in invitationRecipients" :key="recipient.id">
              {{ recipient.name }}
            </p>
            <p
              v-if="
                !invitationRecipientsLoading && !invitationRecipients.length
              "
            >
              Cadastre um assistido antes de enviar o convite.
            </p>
            <p>O vínculo com o assistido deste grupo será criado no aceite.</p>
          </fieldset>
          <fieldset
            v-if="invitationForm.role && invitationForm.role !== 'responsavel'"
            class="care-form"
            :disabled="invitationsStore.creating"
          >
            <legend>Áreas autorizadas</legend>
            <label
              v-for="(label, key) in invitationAreas"
              :key="key"
              class="care-check"
              ><input
                type="checkbox"
                v-model="invitationForm.areas"
                :value="key"
              />{{ label }}</label
            >
            <label v-if="invitationForm.role === 'cuidador'" class="care-check"
              ><input
                type="checkbox"
                v-model="invitationForm.canEdit"
              />Permitir registrar cuidados nas áreas selecionadas</label
            >
            <p v-else>Observadores terão somente visualização.</p>
          </fieldset>
          <p v-else-if="invitationForm.role === 'responsavel'">
            O responsável terá acesso às áreas de cuidados somente do assistido
            deste grupo.
          </p>
          <div
            v-if="invitationError"
            class="organization-members__error"
            role="alert"
          >
            {{ invitationError }}
          </div>

          <div class="organization-members__form-actions">
            <AppButton
              type="button"
              variant="ghost"
              :disabled="invitationsStore.creating"
              @click="closeInvitationDialog"
            >
              Cancelar
            </AppButton>

            <AppButton
              variant="action"
              type="submit"
              :loading="invitationsStore.creating"
              :disabled="invitationsStore.creating"
            >
              Enviar convite
            </AppButton>
          </div>
        </form>
      </AppDialog>

      <AppDialog
        :open="Boolean(memberRoleTarget)"
        title="Alterar função"
        size="sm"
        :close-on-backdrop="!updatingRole"
        :close-on-escape="!updatingRole"
        @close="closeRoleDialog"
      >
        <form
          class="organization-members__form"
          @submit.prevent="submitRoleChange"
        >
          <p
            v-if="memberRoleTarget"
            class="organization-members__dialog-description"
          >
            Altere a função de
            <strong>{{ memberRoleTarget.name }}</strong
            >.
          </p>

          <p
            v-if="memberRoleTarget.id === authStore.user.id"
            class="organization-members__dialog-description"
          >
            Ao mudar sua função, suas permissões serão atualizadas
            imediatamente. Você poderá perder acesso à gestão do grupo.
          </p>
          <AppSelect
            v-model="selectedRole"
            id="member-role"
            name="role"
            label="Função"
            placeholder="Selecione uma função"
            required
            :options="rolesStore.options"
            :disabled="updatingRole"
            :error="roleError"
          />

          <div
            v-if="roleGeneralError"
            class="organization-members__error"
            role="alert"
          >
            {{ roleGeneralError }}
          </div>

          <div class="organization-members__form-actions">
            <AppButton
              type="button"
              variant="ghost"
              :disabled="updatingRole"
              @click="closeRoleDialog"
            >
              Cancelar
            </AppButton>

            <AppButton
              variant="action"
              type="submit"
              :loading="updatingRole"
              :disabled="updatingRole"
            >
              Salvar
            </AppButton>
          </div>
        </form>
      </AppDialog>

      <div v-if="statusError" class="organization-members__error" role="alert">
        {{ statusError }}
      </div>

      <AppConfirmDialog
        :open="Boolean(memberStatusTarget)"
        :title="statusDialogTitle"
        :message="statusDialogMessage"
        :confirm-label="statusDialogConfirmLabel"
        cancel-label="Cancelar"
        :loading="updatingStatus"
        @confirm="confirmStatusChange"
        @cancel="cancelStatusChange"
      />

      <AppConfirmDialog
        :open="Boolean(invitationRevokeTarget)"
        title="Revogar convite"
        :message="invitationRevokeDialogMessage"
        confirm-label="Revogar"
        cancel-label="Cancelar"
        :loading="invitationRevoking"
        @confirm="confirmInvitationRevocation"
        @cancel="cancelInvitationRevocation"
      />
    </div>
  </PageContainer>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";

import PageContainer from "@/components/layout/PageContainer/index.vue";

import { AppEmail, AppSelect } from "@/components/forms";

import {
  AppButton,
  AppConfirmDialog,
  AppDialog,
  AppTable,
} from "@/components/ui";

import { useCareStore } from "@/state/care.js";
import { areas as invitationAreas } from "@/utils/care.js";
import { useAuthStore } from "@/state/auth.js";
import { useOrganizationInvitationsStore } from "@/state/organization-invitations.js";
import { useOrganizationMembersStore } from "@/state/organization-members.js";
import { useOrganizationRolesStore } from "@/state/organization-roles.js";

const authStore = useAuthStore();
const router = useRouter();
const invitationsStore = useOrganizationInvitationsStore();
const membersStore = useOrganizationMembersStore();
const rolesStore = useOrganizationRolesStore();
const careStore = useCareStore();
const invitationRecipientsLoading = ref(false);
const invitationRecipients = computed(() =>
  careStore.recipients.filter((p) => p.status === "active"),
);

const loadError = ref("");

const invitationLoadError = ref("");
const invitationActionError = ref("");

const invitationDialogOpen = ref(false);
const invitationError = ref("");

const invitationErrors = reactive({
  email: "",
  role: "",
});

const invitationForm = reactive({
  email: "",
  role: null,
  recipientIds: [],
  areas: ["routine"],
  canEdit: false,
});

const memberRoleTarget = ref(null);
const selectedRole = ref(null);
const updatingRole = ref(false);
const roleError = ref("");
const roleGeneralError = ref("");

const memberStatusTarget = ref(null);
const updatingStatus = ref(false);
const statusError = ref("");

const invitationRevokeTarget = ref(null);

const columns = [
  {
    key: "name",
    label: "Nome",
  },
  {
    key: "email",
    label: "E-mail",
  },
  {
    key: "role",
    label: "Função",
  },
  {
    key: "status",
    label: "Status",
  },
  {
    key: "actions",
    label: "Ações",
    align: "end",
  },
];

const invitationColumns = [
  {
    key: "email",
    label: "E-mail",
  },
  {
    key: "role",
    label: "Função",
  },
  {
    key: "status",
    label: "Status",
  },
  {
    key: "expires_at",
    label: "Expira em",
  },
  {
    key: "inviter",
    label: "Enviado por",
  },
  {
    key: "actions",
    label: "Ações",
    align: "end",
  },
];

const canInvite = computed(() =>
  authStore.hasPermission("organization-members.invite"),
);

const canUpdateRole = computed(() =>
  authStore.hasPermission("organization-members.update-role"),
);

const canUpdateStatus = computed(() =>
  authStore.hasPermission("organization-members.update-status"),
);

const invitationActionInProgress = computed(
  () =>
    invitationsStore.resendingId !== null ||
    invitationsStore.revokingId !== null,
);

const invitationRevoking = computed(
  () =>
    invitationRevokeTarget.value !== null &&
    invitationsStore.revokingId === invitationRevokeTarget.value.id,
);

const statusDialogTitle = computed(() =>
  memberStatusTarget.value?.status === "active"
    ? "Desativar membro"
    : "Reativar membro",
);

const statusDialogConfirmLabel = computed(() =>
  memberStatusTarget.value?.status === "active" ? "Desativar" : "Reativar",
);

const statusDialogMessage = computed(() => {
  const member = memberStatusTarget.value;

  if (!member) {
    return "";
  }

  if (member.id === authStore.user.id && member.status === "active") {
    return "Desativar seu próprio vínculo? Você perderá o acesso a este grupo e será direcionado à seleção de grupos.";
  }
  if (member.status === "active") {
    return `Deseja realmente desativar o acesso de "${member.name}" ao grupo de cuidados?`;
  }

  return `Deseja reativar o acesso de "${member.name}" ao grupo de cuidados?`;
});

const invitationRevokeDialogMessage = computed(() => {
  const invitation = invitationRevokeTarget.value;

  if (!invitation) {
    return "";
  }

  return `Deseja realmente revogar o convite enviado para "${invitation.email}"?`;
});

function roleLabel(roleName) {
  if (roleName === "responsavel") return "Responsável";
  if (!roleName) {
    return "—";
  }

  return String(roleName)
    .split("-")
    .map((part) => {
      if (!part) {
        return "";
      }

      return `${part.charAt(0).toUpperCase()}${part.slice(1)}`;
    })
    .join(" ");
}

function statusLabel(status) {
  if (status === "active") {
    return "Ativo";
  }

  if (status === "inactive") {
    return "Inativo";
  }

  return status || "—";
}

function statusClass(status) {
  return {
    "organization-members__status--active": status === "active",

    "organization-members__status--inactive": status === "inactive",
  };
}

function invitationStatusLabel(status) {
  const labels = {
    pending: "Pendente",
    expired: "Expirado",
    accepted: "Aceito",
    revoked: "Revogado",
  };

  return labels[status] ?? status ?? "—";
}

function invitationStatusClass(status) {
  return {
    "organization-members__status--pending": status === "pending",

    "organization-members__status--expired": status === "expired",

    "organization-members__status--accepted": status === "accepted",

    "organization-members__status--revoked": status === "revoked",
  };
}

function formatDate(value) {
  if (!value) {
    return "—";
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return "—";
  }

  return new Intl.DateTimeFormat("pt-BR", {
    dateStyle: "short",
  }).format(date);
}

function clearInvitationErrors() {
  invitationErrors.email = "";
  invitationErrors.role = "";
  invitationError.value = "";
}

function resetInvitationForm() {
  invitationForm.email = "";
  invitationForm.role = null;
  invitationForm.recipientIds = [];
  invitationForm.areas = ["routine"];
  invitationForm.canEdit = false;

  clearInvitationErrors();
}

async function openInvitationDialog() {
  resetInvitationForm();
  invitationDialogOpen.value = true;
  invitationRecipientsLoading.value = true;
  try {
    await careStore.loadRecipients();
    if (invitationRecipients.value.length === 1)
      invitationForm.recipientIds = [invitationRecipients.value[0].id];
  } catch {
    invitationError.value =
      "Não foi possível carregar o assistido. Feche e tente novamente.";
  } finally {
    invitationRecipientsLoading.value = false;
  }
}

function closeInvitationDialog() {
  if (invitationsStore.creating) {
    return;
  }

  invitationDialogOpen.value = false;

  resetInvitationForm();
}

function applyInvitationValidationErrors(error) {
  const errors = error?.response?.data?.errors;

  if (!errors) {
    return false;
  }

  invitationErrors.email = errors.email?.[0] ?? "";

  invitationErrors.role = errors.role?.[0] ?? "";
  invitationError.value = Object.entries(errors)
    .filter(([key]) => key.startsWith("care_accesses"))
    .flatMap(([, messages]) => messages)
    .join(" ");

  return true;
}

async function submitInvitation() {
  if (invitationsStore.creating || invitationRecipientsLoading.value) {
    return;
  }

  clearInvitationErrors();
  if (!invitationForm.recipientIds.length) {
    invitationError.value = "Selecione pelo menos um assistido.";
    return;
  }

  try {
    await invitationsStore.create({
      email: invitationForm.email,
      role: invitationForm.role,
      care_accesses: invitationForm.recipientIds.map((id) => ({
        care_recipient_id: id,
        areas:
          invitationForm.role === "responsavel"
            ? Object.keys(invitationAreas)
            : invitationForm.areas,
        can_edit:
          invitationForm.role === "responsavel" ||
          (invitationForm.role === "cuidador" && invitationForm.canEdit),
      })),
    });

    invitationDialogOpen.value = false;

    resetInvitationForm();
  } catch (error) {
    if (applyInvitationValidationErrors(error)) {
      return;
    }

    invitationError.value =
      "Não foi possível enviar o convite. Tente novamente.";
  }
}

async function resendInvitation(invitation) {
  if (invitation.status !== "pending" || invitationActionInProgress.value) {
    return;
  }

  invitationActionError.value = "";

  try {
    await invitationsStore.resend(invitation.id);
  } catch {
    invitationActionError.value =
      "Não foi possível reenviar o convite. Tente novamente.";
  }
}

function requestInvitationRevocation(invitation) {
  if (invitation.status !== "pending" || invitationActionInProgress.value) {
    return;
  }

  invitationActionError.value = "";

  invitationRevokeTarget.value = invitation;
}

function cancelInvitationRevocation() {
  if (invitationRevoking.value) {
    return;
  }

  invitationRevokeTarget.value = null;
  invitationActionError.value = "";
}

async function confirmInvitationRevocation() {
  const invitation = invitationRevokeTarget.value;

  if (!invitation || invitationRevoking.value) {
    return;
  }

  invitationActionError.value = "";

  try {
    await invitationsStore.revoke(invitation.id);

    invitationRevokeTarget.value = null;
  } catch {
    invitationActionError.value =
      "Não foi possível revogar o convite. Tente novamente.";
  }
}

function openRoleDialog(member) {
  if (!member.can_update_role) return;
  memberRoleTarget.value = member;
  selectedRole.value = member.role;

  roleError.value = "";
  roleGeneralError.value = "";
}

function resetRoleDialog() {
  memberRoleTarget.value = null;
  selectedRole.value = null;

  roleError.value = "";
  roleGeneralError.value = "";
}

function closeRoleDialog() {
  if (updatingRole.value) {
    return;
  }

  resetRoleDialog();
}

async function submitRoleChange() {
  if (!memberRoleTarget.value || !selectedRole.value || updatingRole.value) {
    return;
  }

  updatingRole.value = true;

  roleError.value = "";
  roleGeneralError.value = "";

  let updated = false;

  try {
    await membersStore.updateRole(
      memberRoleTarget.value.id,
      selectedRole.value,
    );

    updated = true;
    if (memberRoleTarget.value.id === authStore.user.id) {
      authStore.clearTenantStores();
      const tenant = authStore.currentTenant;
      authStore.clearContext();
      await authStore.fetchContext(tenant);
      await router.replace({ name: "dashboard" });
    }
  } catch (error) {
    const validationErrors = error?.response?.data?.errors;

    if (validationErrors?.role?.[0]) {
      roleError.value = validationErrors.role[0];
    }

    if (validationErrors?.member?.[0]) {
      roleGeneralError.value = validationErrors.member[0];
    }

    if (!roleError.value && !roleGeneralError.value) {
      roleGeneralError.value = "Não foi possível alterar a função do membro.";
    }
  } finally {
    updatingRole.value = false;
  }

  if (updated) {
    resetRoleDialog();
  }
}

function requestStatusChange(member) {
  if (!member.can_update_status) return;
  statusError.value = "";

  memberStatusTarget.value = member;
}

function cancelStatusChange() {
  if (updatingStatus.value) {
    return;
  }

  memberStatusTarget.value = null;
  statusError.value = "";
}

async function confirmStatusChange() {
  const member = memberStatusTarget.value;

  if (!member || updatingStatus.value) {
    return;
  }

  const newStatus = member.status === "active" ? "inactive" : "active";

  updatingStatus.value = true;
  statusError.value = "";

  try {
    await membersStore.updateStatus(member.id, newStatus);

    memberStatusTarget.value = null;
    if (member.id === authStore.user.id && newStatus === "inactive") {
      authStore.clearContext({ removeTenant: true, clearStores: true });
      await authStore.fetchMe();
      await router.replace({ name: "organizations.select" });
    }
  } catch {
    statusError.value =
      "Não foi possível alterar o status do membro. Tente novamente.";
  } finally {
    updatingStatus.value = false;
  }
}

async function loadMembersAndRoles() {
  loadError.value = "";

  try {
    await Promise.all([membersStore.fetchMembers(), rolesStore.fetchRoles()]);
  } catch {
    loadError.value = "Não foi possível carregar os dados da equipe.";
  }
}

async function loadInvitations() {
  if (!canInvite.value) {
    return;
  }

  invitationLoadError.value = "";

  try {
    await invitationsStore.fetchInvitations();
  } catch {
    invitationLoadError.value = "Não foi possível carregar os convites.";
  }
}

onMounted(async () => {
  await Promise.all([loadMembersAndRoles(), loadInvitations()]);
});
</script>

<style scoped>
.organization-members {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.organization-members__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-4);
}

.organization-members__heading {
  min-width: 0;
}

.organization-members__title {
  margin: 0;
  color: var(--color-text);
}

.organization-members__description,
.organization-members__dialog-description {
  margin: var(--space-2) 0 0;

  color: var(--color-text-muted);
}

.organization-members__invitations {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);

  padding-top: var(--space-6);

  border-top: 1px solid var(--color-border);
}

.organization-members__section-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-4);
}

.organization-members__section-title {
  margin: 0;

  color: var(--color-text);

  font-size: var(--font-size-xl);
  font-weight: 600;
}

.organization-members__section-description {
  margin: var(--space-2) 0 0;

  color: var(--color-text-muted);

  font-size: var(--font-size-sm);
}

.organization-members__dialog-description strong {
  color: var(--color-text);
  font-weight: 600;
}

.organization-members__actions,
.organization-members__form-actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--space-2);
}

.organization-members__actions {
  flex-wrap: wrap;
  white-space: nowrap;
}

.organization-members__form {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.organization-members__form-actions {
  margin-top: var(--space-2);
}

.organization-members__status {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);

  padding: var(--space-1) var(--space-2);

  border-radius: var(--radius-full);

  font-size: var(--font-size-sm);
  font-weight: 600;
  line-height: 1.25;
}

.organization-members__status::before {
  width: 0.45rem;
  height: 0.45rem;

  border-radius: 50%;

  background: currentColor;

  content: "";
}

.organization-members__status--active {
  background: var(--color-success-soft);
  color: var(--color-success);
}

.organization-members__status--inactive {
  background: var(--color-surface-muted);
  color: var(--color-text-muted);
}

.organization-members__status--pending {
  background: var(--color-surface-accent);
  color: var(--color-brand);
}

.organization-members__status--expired {
  background: var(--color-surface-muted);
  color: var(--color-text-muted);
}

.organization-members__status--accepted {
  background: var(--color-success-soft);
  color: var(--color-success);
}

.organization-members__status--revoked {
  background: var(--color-danger-soft);
  color: var(--color-danger);
}

.organization-members__state {
  padding: var(--space-5) var(--space-4);

  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);

  background: var(--color-surface-muted);
  color: var(--color-text-muted);

  font-size: var(--font-size-sm);
  text-align: center;
}

.organization-members__empty-action {
  color: var(--color-text-muted);
}

.organization-members__error {
  padding: var(--space-3) var(--space-4);

  border: 1px solid var(--color-danger);
  border-radius: var(--radius-md);

  background: var(--color-danger-soft);
  color: var(--color-danger);

  font-size: var(--font-size-sm);
}

@media (max-width: 760px) {
  .organization-members__header {
    flex-direction: column;
    align-items: stretch;
  }

  .organization-members__actions {
    flex-direction: column;
    align-items: stretch;

    white-space: normal;
  }

  .organization-members__form-actions {
    flex-wrap: wrap;
  }
}
</style>
