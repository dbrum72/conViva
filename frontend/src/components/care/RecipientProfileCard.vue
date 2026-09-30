<template>
  <AppCard title="Dados cadastrais">
    <p>
      {{ recipientKinds[recipient.kind]
      }}<span v-if="recipient.birth_date">
        · Nascimento:
        {{
          recipient.birth_date.slice(0, 10).split("-").reverse().join("/")
        }}</span
      >
    </p>
    <p v-if="recipient.kind === 'pet'">
      {{ recipient.species
      }}<span v-if="recipient.breed"> · {{ recipient.breed }}</span>
    </p>
    <AppButton
      v-if="recipient.can_manage_profile"
      variant="modal"
      @click="$emit('edit')"
      >Editar ficha</AppButton
    >
  </AppCard>
  <AppCard v-for="area in sections" :key="area.key" :title="area.label">
    <dl class="care-details">
      <template v-for="(label, field) in fields" :key="field">
        <template v-if="recipient[area.key]?.[field]"
          ><dt>{{ label }}</dt>
          <dd class="care-pre">{{ recipient[area.key][field] }}</dd></template
        >
      </template>
    </dl>
    <div
      v-for="(contact, index) in recipient[area.key]?.contacts || []"
      :key="index"
    >
      <strong>{{ contact.name }}</strong>
      <p>{{ contact.relationship }} · {{ contact.phone }}</p>
    </div>
    <p
      v-if="
        !Object.values(recipient[area.key] || {}).some((v) =>
          Array.isArray(v) ? v.length : v,
        )
      "
      class="care-muted"
    >
      Nenhuma informação de referência cadastrada.
    </p>
  </AppCard>
</template>
<script setup>
import { computed } from "vue";
import { AppCard, AppButton } from "@/components/ui";
import { recipientKinds } from "@/utils/care";
defineEmits(["edit"]);
const props = defineProps({ recipient: { type: Object, required: true } });
const sections = computed(() =>
  [
    {
      key: "health_profile",
      label:
        props.recipient.kind === "pet"
          ? "Saúde e contato veterinário"
          : "Referências de saúde",
      area: "health",
    },
    { key: "routine_profile", label: "Rotina e apoio", area: "routine" },
  ].filter((s) => props.recipient.capabilities?.[s.area]?.view),
);
const fields = {
  preferences: "Preferências",
  instructions: "Instruções informadas",
  school: "Escola",
  authorized_people: "Pessoas autorizadas (referência)",
  identification: "Identificação",
};
</script>
