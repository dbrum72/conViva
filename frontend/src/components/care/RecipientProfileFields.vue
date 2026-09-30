<template>
  <fieldset v-for="area in availableAreas" :key="area">
    <legend>
      {{
        area === "health" ? "Referências de saúde" : "Rotina e rede de apoio"
      }}
    </legend>
    <p class="care-muted">
      {{
        area === "health"
          ? "Visível somente para quem tem acesso à saúde. Registre instruções informadas, sem substituir orientação profissional."
          : "Use esta seção para informações de rotina. Informações clínicas devem ficar na seção de saúde."
      }}
    </p>
    <template v-if="area === 'routine'">
      <label class="care-field"
        >{{
          kind === "adult"
            ? "Preferências e autonomia no cuidado"
            : "Preferências"
        }}<textarea
          :value="profile(area).preferences"
          maxlength="2000"
          placeholder="Como prefere ser chamado; atividades e hábitos favoritos"
          @input="set(area, 'preferences', $event.target.value)"
        />
      </label>
      <template v-if="kind === 'child'">
        <label class="care-field"
          >Escola e referência escolar<input
            :value="profile(area).school"
            maxlength="500"
            placeholder="Escola, turma e turno"
            @input="set(area, 'school', $event.target.value)"
        /></label>
        <label class="care-field"
          >Pessoas autorizadas (referência informada)<textarea
            :value="profile(area).authorized_people"
            maxlength="2000"
            placeholder="Nome, vínculo e contexto da autorização"
            @input="set(area, 'authorized_people', $event.target.value)"
          />
        </label>
        <p class="care-muted">
          Este registro não concede acesso ao aplicativo nem substitui a
          autorização exigida pela instituição.
        </p>
      </template>
      <label v-if="kind === 'pet'" class="care-field"
        >Identificação opcional<input
          :value="profile(area).identification"
          maxlength="150"
          placeholder="Microchip ou identificação da coleira"
          @input="set(area, 'identification', $event.target.value)"
      /></label>
    </template>
    <label class="care-field"
      >{{
        area === "health"
          ? "Instruções de saúde informadas"
          : "Instruções de rotina"
      }}<textarea
        :value="profile(area).instructions"
        maxlength="4000"
        :placeholder="
          area === 'health'
            ? 'Cuidados informados e orientações de referência'
            : kind === 'pet'
              ? 'Alimentação, passeios e hábitos'
              : kind === 'child'
                ? 'Horários, deslocamentos e atividades'
                : 'Apoio desejado, comunicação e hábitos cotidianos'
        "
        @input="set(area, 'instructions', $event.target.value)"
      />
    </label>
    <h3>
      {{
        area === "health"
          ? kind === "pet"
            ? "Contato veterinário"
            : "Contatos de saúde"
          : "Contatos de apoio"
      }}
    </h3>
    <div v-for="(contact, index) in profile(area).contacts || []" :key="index">
      <label class="care-field"
        >Nome<input v-model="contact.name" required maxlength="150"
      /></label>
      <label class="care-field"
        >Vínculo ou serviço<input
          v-model="contact.relationship"
          maxlength="100"
      /></label>
      <label class="care-field"
        >Telefone<input
          v-model="contact.phone"
          type="tel"
          required
          maxlength="50"
      /></label>
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
  </fieldset>
</template>
<script setup>
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
