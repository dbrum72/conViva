<template>
  <AppCard title="Documentos privados">
    <AppForm class="care-form" @submit.prevent="applyFilters">
      <AppFieldset class="care-form" :disabled="busy">
        <template #legend>Filtrar por categoria e data de envio</template>
        <AppInput
          :id="fieldId + '-filter'"
          v-model="filters.category"
          label="Categoria"
          :maxlength="100"
        />
        <AppDate :id="fieldId + '-from'" v-model="filters.from" label="De" />
        <AppDate
          :id="fieldId + '-to'"
          v-model="filters.to"
          label="Até"
          :min="filters.from"
        />
        <div class="care-actions">
          <AppButton type="submit" variant="filter">Filtrar</AppButton>
          <AppButton variant="outline" @click="clearFilters"
            >Limpar filtros</AppButton
          >
        </div>
      </AppFieldset>
    </AppForm>
    <AppButton v-if="canEdit" variant="modal" :disabled="busy" @click="edit()">
      Adicionar documento
    </AppButton>
    <div v-for="doc in store.documents" :key="doc.id" class="care-list-row">
      <div>
        <strong>{{ doc.filename }}</strong>
        <p class="care-muted">
          {{ doc.category || "Sem categoria" }} · Enviado em
          {{ dateTime(doc.created_at, auth.organization?.timezone) }}
        </p>
        <p v-if="doc.valid_until">
          Válido até {{ doc.valid_until.split("-").reverse().join("/") }}
        </p>
        <p v-if="doc.entry">Vinculado a {{ doc.entry.title }}</p>
      </div>
      <div class="care-actions">
        <AppButton
          variant="outline"
          :disabled="busy"
          @click="store.download(recipientId, doc).catch(() => {})"
          >Baixar</AppButton
        >
        <template v-if="canEdit && doc.created_by === auth.user?.id">
          <AppButton variant="modal" :disabled="busy" @click="edit(doc)"
            >Editar</AppButton
          >
          <AppButton
            variant="danger"
            :disabled="busy"
            @click="$emit('remove', doc)"
            >Excluir</AppButton
          >
        </template>
      </div>
    </div>
    <p
      v-if="!busy && !store.error && !store.documents.length"
      class="care-empty"
    >
      Nenhum documento encontrado.
    </p>
  </AppCard>
  <AppDialog
    :open="open"
    :title="selected ? 'Editar documento' : 'Adicionar documento'"
    :busy="busy"
    :error="store.error"
    @close="open = false"
  >
    <AppForm class="care-form" @submit.prevent="save">
      <AppFieldset class="care-form" :disabled="busy">
        <AppFileUpload
          v-if="!selected"
          :id="fieldId + '-file'"
          v-model="files"
          label="Arquivo (até 20 MB)"
          accept=".pdf,.png,.jpg,.jpeg,.webp,.txt,.docx,.xlsx"
          required
        />
        <AppInput
          :id="fieldId + '-category'"
          v-model="draft.category"
          label="Categoria (opcional)"
          :maxlength="100"
        />
        <AppDate
          :id="fieldId + '-valid-until'"
          v-model="draft.valid_until"
          label="Válido até (opcional)"
        />
        <p v-if="selected?.entry">Vinculado a {{ selected.entry.title }}</p>
        <template v-else>
          <AppSelect
            :id="fieldId + '-entry'"
            v-model="draft.care_entry_id"
            label="Vincular a um registro (opcional)"
            :options="entryOptions"
          />
          <p class="care-muted">
            Após vincular, o documento fica disponível a quem pode consultar
            Documentos e a área do registro. O vínculo é permanente.
          </p>
        </template>
        <div class="care-actions">
          <AppButton variant="cancel" @click="open = false">Cancelar</AppButton>
          <AppButton
            variant="action"
            type="submit"
            :loading="busy"
            :disabled="!selected && !files.length"
            >Salvar documento</AppButton
          >
        </div>
      </AppFieldset>
    </AppForm>
  </AppDialog>
</template>

<script setup>
import { computed, ref, useId, watch } from "vue";
import {
  AppDate,
  AppFieldset,
  AppFileUpload,
  AppForm,
  AppInput,
  AppSelect,
} from "@/components/forms";
import { AppButton, AppCard, AppDialog } from "@/components/ui";
import { useAuthStore } from "@/modules/auth";
import { useCareStore } from "../state/care.js";
import { dateTime, entryKinds } from "../utils/care.js";

const props = defineProps({
  recipientId: { type: [String, Number], required: true },
  canEdit: Boolean,
});
defineEmits(["remove"]);
const auth = useAuthStore();
const store = useCareStore();
const fieldId = useId();
const busy = computed(() => !!store.pending);
const filters = ref({});
const appliedFilters = ref({});
const open = ref(false);
const selected = ref(null);
const files = ref([]);
const draft = ref({});
const entryOptions = computed(() => [
  { value: "", label: "Sem vínculo" },
  ...store.entries.map(({ id, title, kind }) => ({
    value: id,
    label: `${entryKinds[kind] || kind} · ${title}`,
  })),
]);

function reload() {
  return store
    .loadDocuments(props.recipientId, appliedFilters.value)
    .catch(() => {});
}
function applyFilters() {
  appliedFilters.value = { ...filters.value };
  return reload();
}
function clearFilters() {
  filters.value = {};
  return applyFilters();
}
function edit(doc = null) {
  store.error = "";
  selected.value = doc;
  files.value = [];
  draft.value = {
    category: doc?.category || "",
    valid_until: doc?.valid_until || "",
    care_entry_id: "",
  };
  open.value = true;
}
async function save() {
  if (busy.value || (!selected.value && !files.value.length)) return;
  const tenant = auth.currentTenant;
  const recipientId = props.recipientId;
  const metadata = {
    category: draft.value.category || null,
    valid_until: draft.value.valid_until || null,
    ...(draft.value.care_entry_id && {
      care_entry_id: draft.value.care_entry_id,
    }),
  };
  try {
    await store.saveDocument(
      recipientId,
      selected.value?.id,
      metadata,
      files.value[0],
    );
    if (auth.currentTenant !== tenant || props.recipientId !== recipientId)
      return;
    open.value = false;
    await reload();
  } catch {}
}

watch(
  () => [auth.currentTenant, auth.user?.id, props.recipientId],
  () => {
    open.value = false;
    files.value = [];
    filters.value = {};
    appliedFilters.value = {};
  },
);
</script>
