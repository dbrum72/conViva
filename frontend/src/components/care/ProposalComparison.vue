<template>
  <section aria-label="Alterações propostas">
    <h3>Alterações propostas</h3>
    <p v-if="proposal.comparison_available">
      Comparação com a versão vigente no momento da proposta.
    </p>
    <p v-else>
      Esta proposta anterior à central não possui uma cópia da versão original
      para comparação. Os valores apresentados são os propostos.
    </p>
    <AppTable
      v-if="proposal.changes.length"
      class="proposal-comparison-table"
      :columns="columns"
      :rows="proposal.changes"
      row-key="field"
    >
      <template #cell-field="{ value }">{{ fields[value] || value }}</template>
      <template #cell-before="{ row, value }">{{
        display(row.field, value)
      }}</template>
      <template #cell-after="{ row, value }">{{
        display(row.field, value)
      }}</template>
    </AppTable>
    <p v-else>Nenhuma diferença de conteúdo registrada.</p>
  </section>
</template>
<script setup>
import { AppTable } from "@/components/ui";
import {
  dateTime,
  money,
  recipientKinds,
  entryKinds,
  formatSchedule,
} from "@/utils/care";
const props = defineProps({ proposal: { type: Object, required: true } });
const columns = [
  { key: "field", label: "Campo", rowHeader: true },
  { key: "before", label: "Antes" },
  { key: "after", label: "Proposto" },
];
const fields = {
  publish_to_agenda: "Publicar na agenda",
  schedule: "Programação",
  exception: "Cancelamento de ocorrências",
  routine_profile: "Ficha de rotina",
  health_profile: "Referências de saúde",
  title: "Título",
  description: "Descrição",
  due_at: "Quando",
  ends_at: "Término",
  assigned_user_id: "Designado",
  amount_cents: "Valor",
  details: "Detalhes",
  shares: "Parcelas",
  affected_user_ids: "Participantes afetados",
  name: "Nome",
  kind: "Tipo",
  birth_date: "Nascimento",
  species: "Espécie",
  breed: "Raça",
  status: "Situação",
};
function participant(id) {
  return (
    props.proposal.decisions.find((v) => v.user_id === id)?.user?.name ||
    (props.proposal.author?.id === id
      ? props.proposal.author.name
      : `Participante ${id}`)
  );
}
function display(field, value) {
  if (value === null || value === undefined || value === "") return "—";
  if (field === "publish_to_agenda") return value ? "Sim" : "Não";
  if (field === "exception")
    return `${value.scope === "future" ? "Esta e as futuras" : "Somente esta"}: ${dateTime(value.starts_at)}`;
  if (field === "schedule") return formatSchedule(value);
  if (field === "amount_cents") return money(value);
  if (field === "assigned_user_id") return participant(value);
  if (field === "affected_user_ids")
    return value.map(participant).join(", ") || "—";
  if (field === "shares")
    return (
      value
        .map((s) => `${participant(s.user_id)}: ${money(s.amount_cents)}`)
        .join("; ") || "—"
    );
  if (["due_at", "ends_at"].includes(field)) return dateTime(value);
  if (field === "kind")
    return recipientKinds[value] || entryKinds[value] || value;
  if (field === "status")
    return (
      {
        active: "Ativo",
        archived: "Arquivado",
        pending: "Confirmado",
        cancelled: "Cancelado",
        completed: "Cadastro encerrado",
      }[value] || value
    );
  if (["routine_profile", "health_profile"].includes(field)) {
    const labels = {
      preferences: "Preferências",
      instructions: "Instruções",
      school: "Escola",
      authorized_people: "Pessoas autorizadas",
      identification: "Identificação",
      contacts: "Contatos",
    };
    return (
      Object.entries(value)
        .map(
          ([key, item]) =>
            `${labels[key] || key}: ${Array.isArray(item) ? item.map((c) => [c.name, c.relationship, c.phone].filter(Boolean).join(" · ")).join("; ") : item || "—"}`,
        )
        .join("; ") || "—"
    );
  }
  if (typeof value === "object")
    return (
      Object.entries(value)
        .map(
          ([key, item]) =>
            `${{ dose: "Dose", frequency: "Frequência", route: "Via", food: "Alimento", quantity: "Quantidade", provider: "Profissional/local" }[key] || key}: ${item ?? "—"}`,
        )
        .join("; ") || "—"
    );
  return value;
}
</script>
<style scoped>
.proposal-comparison-table {
  margin-block: 1rem;
}
.proposal-comparison-table :deep(.app-table__cell) {
  vertical-align: top;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}
</style>
