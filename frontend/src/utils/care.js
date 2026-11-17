export const recipientKinds = {
  child: "Criança ou adolescente",
  adult: "Pessoa adulta sob cuidados",
  pet: "Pet",
};
export const entryKinds = {
  event: "Compromisso",
  task: "Tarefa",
  journal: "Registro de cuidado",
  medication: "Medicamento",
  vaccine: "Vacina",
  feeding: "Alimentação",
  expense: "Despesa",
};
export const areas = {
  routine: "Rotina",
  health: "Saúde",
  documents: "Documentos",
  finance: "Despesas",
};
export const areaFor = (kind) =>
  ["medication", "vaccine"].includes(kind)
    ? "health"
    : kind === "expense"
      ? "finance"
      : "routine";
export function formatSchedule(rule) {
  const [date, time] = rule.local_start.split("T");
  const frequency = {
    once: "Data isolada",
    daily: "Diária",
    weekly: "Semanal",
  };
  const days = ["Seg", "Ter", "Qua", "Qui", "Sex", "Sáb", "Dom"];
  return [
    frequency[rule.frequency],
    date.split("-").reverse().join("/"),
    (rule.times || [time]).join(", "),
    rule.timezone,
    rule.frequency !== "once" &&
      rule.until &&
      `até ${rule.until.split("-").reverse().join("/")}`,
    rule.frequency === "weekly" &&
      rule.weekdays.map((day) => days[day - 1]).join(", "),
    rule.duration_minutes > 0 && `${rule.duration_minutes} min`,
  ]
    .filter(Boolean)
    .join(" · ");
}
export const dateTime = (value) =>
  value
    ? new Intl.DateTimeFormat("pt-BR", {
        dateStyle: "short",
        timeStyle: "short",
      }).format(new Date(value))
    : "Sem data";
export const money = (cents) =>
  new Intl.NumberFormat("pt-BR", { style: "currency", currency: "BRL" }).format(
    (cents || 0) / 100,
  );
