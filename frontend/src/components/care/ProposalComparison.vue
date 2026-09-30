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
    <div class="comparison-scroll">
      <table v-if="proposal.changes.length">
        <thead>
          <tr>
            <th>Campo</th>
            <th>Antes</th>
            <th>Proposto</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="change in proposal.changes" :key="change.field">
            <th>{{ fields[change.field] || change.field }}</th>
            <td>{{ display(change.field, change.before) }}</td>
            <td>{{ display(change.field, change.after) }}</td>
          </tr>
        </tbody>
      </table>
      <p v-else>Nenhuma diferença de conteúdo registrada.</p>
    </div>
  </section>
</template>
<script setup>
import { dateTime, money, recipientKinds, entryKinds } from "@/utils/care";
const props = defineProps({ proposal: { type: Object, required: true } });
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
  if (field === "schedule")
    return `${{ once: "Data isolada", daily: "Diária", weekly: "Semanal" }[value.frequency]} · ${value.local_start.replace("T", " ")} · ${value.timezone} · até ${value.until} · ${value.duration_minutes} minutos${value.frequency === "weekly" ? " · dias: " + value.weekdays.map((d) => ["Seg", "Ter", "Qua", "Qui", "Sex", "Sáb", "Dom"][d - 1]).join(", ") : ""}`;
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
.comparison-scroll {
  overflow-x: auto;
}
table {
  border-collapse: collapse;
  width: 100%;
  margin-block: 1rem;
}
th,
td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid var(--color-border);
  vertical-align: top;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}
</style>
