<template>
  <form class="care-form" @submit.prevent="submit">
    <label class="care-field"
      >Membro
      <select v-model="selectedUser" required :disabled="!ready || busy">
        <option value="">Selecione</option>
        <option v-for="member in members" :key="member.id" :value="member.id">
          {{ member.name }} · {{ member.email }}
        </option>
      </select>
    </label>
    <p v-if="readOnly" role="status">
      As permissões de outro responsável estão disponíveis somente para leitura.
    </p>
    <fieldset :disabled="!selectedUser || !ready || busy || readOnly">
      <legend>Áreas disponíveis</legend>
      <div class="access-areas">
        <AppCheckbox
          v-for="(label, key) in areas"
          :key="key"
          :id="`access-area-${key}`"
          :label="label"
          :model-value="form.areas.includes(key)"
          :disabled="!selectedUser || !ready || busy || readOnly"
          @update:model-value="toggleArea(key, $event)"
        />
      </div>
    </fieldset>
    <AppCheckbox
      id="access-can-edit"
      class="access-edit-permission"
      v-model="form.can_edit"
      label="Permitir edição (observadores continuam somente leitura)"
      :disabled="!selectedUser || !ready || busy || readOnly"
    />
    <label class="care-field"
      >Expira em (opcional)
      <input
        type="datetime-local"
        v-model="form.expires_at"
        :disabled="!selectedUser || !ready || busy || readOnly"
      />
    </label>
    <AppButton
      variant="action"
      type="submit"
      :disabled="!selectedUser || !ready || busy || readOnly"
      :loading="busy"
      >Salvar acesso</AppButton
    >
  </form>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import { AppCheckbox } from "@/components/forms";
import { AppButton } from "@/components/ui";
import { areas } from "@/utils/care";

const props = defineProps({
  members: Array,
  accesses: Array,
  ready: Boolean,
  busy: Boolean,
  recipientId: [String, Number],
  currentUserId: [String, Number],
});
const emit = defineEmits(["save"]);
const selectedUser = ref("");
const form = ref({ areas: [], can_edit: false, expires_at: "" });
const selectedAccess = computed(() =>
  props.accesses?.find(
    (access) => String(access.user_id) === String(selectedUser.value),
  ),
);

const readOnly = computed(() => {
  const member = props.members?.find(
    (item) => String(item.id) === String(selectedUser.value),
  );
  return (
    member?.role === "responsavel" &&
    String(member.id) !== String(props.currentUserId)
  );
});

function localDateTime(value) {
  if (!value) return "";
  const date = new Date(value);
  const pad = (number) => String(number).padStart(2, "0");
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

watch(
  () => [selectedUser.value, selectedAccess.value, props.ready],
  () => {
    const access = props.ready ? selectedAccess.value : null;
    form.value = {
      areas: [...(access?.areas || [])],
      can_edit: !!access?.can_edit,
      expires_at: localDateTime(access?.expires_at),
    };
  },
  { immediate: true },
);
watch(
  () => props.recipientId,
  () => {
    selectedUser.value = "";
  },
);
function toggleArea(key, checked) {
  form.value.areas = checked
    ? [...new Set([...form.value.areas, key])]
    : form.value.areas.filter((area) => area !== key);
}
function submit() {
  if (!selectedUser.value || !props.ready || props.busy || readOnly.value)
    return;
  emit("save", {
    user_id: selectedUser.value,
    areas: [...form.value.areas],
    can_edit: form.value.can_edit,
    expires_at:
      form.value.expires_at === localDateTime(selectedAccess.value?.expires_at)
        ? selectedAccess.value?.expires_at || null
        : form.value.expires_at
          ? new Date(form.value.expires_at).toISOString()
          : null,
  });
}
</script>

<style scoped>
.access-edit-permission {
  margin-top: 1rem;
}

.access-areas {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}
</style>
