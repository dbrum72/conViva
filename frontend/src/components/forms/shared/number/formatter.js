import {
  DEFAULT_NUMBER_LOCALE,
  DEFAULT_NUMBER_PRECISION,
  DEFAULT_USE_GROUPING,
} from "./constants.js";

/**
 * Formata um valor numérico para exibição.
 *
 * Retorna string vazia quando:
 * - o valor é null ou undefined;
 * - o valor não representa um número finito.
 *
 * Não realiza:
 * - parsing de texto localizado;
 * - validação de min/max;
 * - sanitização da entrada.
 */
export function formatNumber(
  value,
  {
    locale = DEFAULT_NUMBER_LOCALE,
    minimumFractionDigits = 0,
    maximumFractionDigits = DEFAULT_NUMBER_PRECISION,
    useGrouping = DEFAULT_USE_GROUPING,
  } = {},
) {
  if (value === null || value === undefined || value === "") {
    return "";
  }

  const numericValue = Number(value);

  if (!Number.isFinite(numericValue)) {
    return "";
  }

  const normalizedMinimumFractionDigits = normalizeFractionDigits(
    minimumFractionDigits,
  );

  const normalizedMaximumFractionDigits = Math.max(
    normalizedMinimumFractionDigits,
    normalizeFractionDigits(maximumFractionDigits),
  );

  return new Intl.NumberFormat(locale, {
    style: "decimal",
    useGrouping,
    minimumFractionDigits: normalizedMinimumFractionDigits,
    maximumFractionDigits: normalizedMaximumFractionDigits,
  }).format(numericValue);
}

function normalizeFractionDigits(value) {
  const numericValue = Number(value);

  if (!Number.isInteger(numericValue) || numericValue < 0) {
    return 0;
  }

  return Math.min(numericValue, 20);
}
