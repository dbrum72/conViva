<template>
  <AppFieldset v-for="area in availableAreas" :key="area">
    <template #legend>
      {{
        area === "health" ? "Referências de saúde" : "Rotina e rede de apoio"
      }}
    </template>
    <p class="care-muted">
      {{
        area === "health"
          ? "Visível somente para quem tem acesso à saúde. Registre instruções informadas, sem substituir orientação profissional."
          : "Use esta seção para informações de rotina. Informações clínicas devem ficar na seção de saúde."
      }}
    </p>
    <template v-if="area === 'routine'">
      <AppTextarea
        :label="
          kind === 'adult'
            ? 'Preferências e autonomia no cuidado'
            : 'Preferências'
        "
        :id="fieldId + '-1' + '-' + area"
        :model-value="profile(area).preferences"
        :maxlength="2000"
        placeholder="Como prefere ser chamado; atividades e hábitos favoritos"
        @update:model-value="set(area, 'preferences', $event)"
      />
      <template v-if="kind === 'child'">
        <AppInput
          label="Escola e referência escolar"
          :id="fieldId + '-2' + '-' + area"
          :model-value="profile(area).school"
          :maxlength="500"
          placeholder="Escola, turma e turno"
          @update:model-value="set(area, 'school', $event)"
        />
        <AppTextarea
          label="Pessoas autorizadas (referência informada)"
          :id="fieldId + '-3' + '-' + area"
          :model-value="profile(area).authorized_people"
          :maxlength="2000"
          placeholder="Nome, vínculo e contexto da autorização"
          @update:model-value="set(area, 'authorized_people', $event)"
        />
        <p class="care-muted">
          Este registro não concede acesso ao aplicativo nem substitui a
          autorização exigida pela instituição.
        </p>
      </template>
      <AppInput
        v-if="kind === 'pet'"
        label="Identificação opcional"
        :id="fieldId + '-4' + '-' + area"
        :model-value="profile(area).identification"
        :maxlength="150"
        placeholder="Microchip ou identificação da coleira"
        @update:model-value="set(area, 'identification', $event)"
      />
    </template>
    <AppTextarea
      :label="
        area === 'health'
          ? 'Instruções de saúde informadas'
          : 'Instruções de rotina'
      "
      :id="fieldId + '-5' + '-' + area"
      :model-value="profile(area).instructions"
      :maxlength="4000"
      :placeholder="
        area === 'health'
          ? 'Cuidados informados e orientações de referência'
          : kind === 'pet'
            ? 'Alimentação, passeios e hábitos'
            : kind === 'child'
              ? 'Horários, deslocamentos e atividades'
              : 'Apoio desejado, comunicação e hábitos cotidianos'
      "
      @update:model-value="set(area, 'instructions', $event)"
    />
    <h3>
      {{
        area === "health"
          ? kind === "pet"
            ? "Contato veterinário"
            : "Contatos de saúde"
          : "Contatos de apoio"
      }}
    </h3>
    <div
      v-for="(contact, index) in profile(area).contacts || []"
      :key="index"
      class="recipient-profile-contact"
    >
      <AppInput
        label="Nome"
        :id="fieldId + '-6' + '-' + area + '-' + index"
        v-model="contact.name"
        required
        :maxlength="150"
      />
      <AppInput
        label="Vínculo ou serviço"
        :id="fieldId + '-7' + '-' + area + '-' + index"
        v-model="contact.relationship"
        :maxlength="100"
      />
      <AppInput
        label="Telefone"
        :id="fieldId + '-8' + '-' + area + '-' + index"
        v-model="contact.phone"
        type="tel"
        required
        :maxlength="50"
      />
      <AppButton variant="ghost" @click="remove(area, index)"
        >Remover contato {{ index + 1 }}</AppButton
      >
    </div>
    <AppButton
      v-if="(profile(area).contacts || []).length < 10"
      variant="outline"
      @click="add(area)"
      >Adicionar contato</AppButton
    >
  </AppFieldset>
</template>
<script setup>
import { AppFieldset, AppTextarea, AppInput } from "@/components/forms";
import { useId } from "vue";
const fieldId = useId();
import { computed } from "vue";
import { AppButton } from "@/components/ui";
const props = defineProps({
  modelValue: { type: Object, required: true },
  kind: String,
  healthEditable: Boolean,
});
const emit = defineEmits(["update:modelValue"]);
const availableAreas = computed(() =>
  props.healthEditable ? ["routine", "health"] : ["routine"],
);
const profile = (area) => props.modelValue[`${area}_profile`] || {};
function set(area, key, value) {
  emit("update:modelValue", {
    ...props.modelValue,
    [`${area}_profile`]: { ...profile(area), [key]: value },
  });
}
function add(area) {
  set(area, "contacts", [
    ...(profile(area).contacts || []),
    { name: "", relationship: "", phone: "" },
  ]);
}
function remove(area, index) {
  set(
    area,
    "contacts",
    profile(area).contacts.filter((_, i) => i !== index),
  );
}
</script>

<style scoped>
.recipient-profile-contact {
  display: grid;
  gap: var(--space-3);
  padding: var(--space-4);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
}

.recipient-profile-contact > .btn {
  justify-self: start;
}
</style>
