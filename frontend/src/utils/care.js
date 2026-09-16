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
