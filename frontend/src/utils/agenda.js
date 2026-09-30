// Calendar presentation uses civil dates in the group's time zone, never the device's offset.
export const weekdays = ["Seg", "Ter", "Qua", "Qui", "Sex", "Sáb", "Dom"];
export const agendaStates = {
  scheduled: "Programada",
  executed: "Execução registrada",
  cancelled: "Cancelada",
  superseded: "Substituída por revisão",
};
export function dateKey(value, zone) {
  const parts = new Intl.DateTimeFormat("en", {
    timeZone: zone,
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  }).formatToParts(new Date(value));
  const part = (type) => parts.find((p) => p.type === type).value;
  return `${part("year")}-${part("month")}-${part("day")}`;
}
export function civilDate(key) {
  return new Date(`${key}T12:00:00Z`);
}
export function addDays(key, count) {
  const date = civilDate(key);
  date.setUTCDate(date.getUTCDate() + count);
  return date.toISOString().slice(0, 10);
}
export function monthStart(key) {
  return key.slice(0, 7) + "-01";
}
export function shiftMonth(key, count) {
  const date = civilDate(monthStart(key));
  date.setUTCMonth(date.getUTCMonth() + count);
  return date.toISOString().slice(0, 10);
}
export function weekStart(key) {
  return addDays(key, -((civilDate(key).getUTCDay() + 6) % 7));
}
export function calendarDates(key) {
  const first = weekStart(monthStart(key));
  return Array.from({ length: 42 }, (_, i) => addDays(first, i));
}
export function dateLabel(key, options = { day: "numeric", month: "long" }) {
  return civilDate(key).toLocaleDateString("pt-BR", {
    timeZone: "UTC",
    ...options,
  });
}
export function timeLabel(value, zone) {
  return new Date(value).toLocaleTimeString("pt-BR", {
    timeZone: zone,
    hour: "2-digit",
    minute: "2-digit",
  });
}
export function occurrenceDays(items, dates, zone) {
  const groups = Object.fromEntries(dates.map((d) => [d, []]));
  for (const item of items) {
    const start = dateKey(item.due_at, zone);
    const end = dateKey(
      Math.max(
        new Date(item.due_at).getTime(),
        new Date(item.ends_at).getTime() - 1,
      ),
      zone,
    );
    for (const date of dates)
      if (date >= start && date <= end) groups[date].push(item);
  }
  return groups;
}
