const DEFAULT_LOCALE = "pt-BR";

function parseDate(value) {
  if (!value) {
    return null;
  }

  const date = value instanceof Date ? value : new Date(value);

  if (Number.isNaN(date.getTime())) {
    return null;
  }

  return date;
}

export function formatShortDate(
  value,
  { locale = DEFAULT_LOCALE, emptyValue = "—", invalidValue = value } = {},
) {
  const date = parseDate(value);

  if (!date) {
    return value ? invalidValue : emptyValue;
  }

  return new Intl.DateTimeFormat(locale, {
    dateStyle: "short",
  }).format(date);
}

export function formatShortDateTime(
  value,
  { locale = DEFAULT_LOCALE, emptyValue = "—", invalidValue = value } = {},
) {
  const date = parseDate(value);

  if (!date) {
    return value ? invalidValue : emptyValue;
  }

  return new Intl.DateTimeFormat(locale, {
    dateStyle: "short",
    timeStyle: "short",
  }).format(date);
}

export function formatShortTime(
  value,
  { locale = DEFAULT_LOCALE, emptyValue = "", invalidValue = "" } = {},
) {
  const date = parseDate(value);

  if (!date) {
    return value ? invalidValue : emptyValue;
  }

  return new Intl.DateTimeFormat(locale, {
    hour: "2-digit",
    minute: "2-digit",
  }).format(date);
}

export function addDays(date, days) {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate() + days);
}

export function formatDateKey(date) {
  const year = date.getFullYear();

  const month = String(date.getMonth() + 1).padStart(2, "0");

  const day = String(date.getDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
}

export function parseDateKey(value) {
  const [year, month, day] = String(value).split("-").map(Number);

  if (!year || !month || !day) {
    return null;
  }

  return new Date(year, month - 1, day);
}

export function isSameDate(first, second) {
  return (
    first.getFullYear() === second.getFullYear() &&
    first.getMonth() === second.getMonth() &&
    first.getDate() === second.getDate()
  );
}

export function formatDayMonth(
  value,
  { locale = DEFAULT_LOCALE, emptyValue = "", invalidValue = "" } = {},
) {
  const date = parseDate(value);

  if (!date) {
    return value ? invalidValue : emptyValue;
  }

  return new Intl.DateTimeFormat(locale, {
    day: "2-digit",
    month: "2-digit",
  }).format(date);
}

export function formatWeekday(
  value,
  { locale = DEFAULT_LOCALE, emptyValue = "", invalidValue = "" } = {},
) {
  const date = parseDate(value);

  if (!date) {
    return value ? invalidValue : emptyValue;
  }

  return new Intl.DateTimeFormat(locale, {
    weekday: "long",
  }).format(date);
}
