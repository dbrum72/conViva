import {
  DEFAULT_ALLOW_NEGATIVE,
  DEFAULT_DECIMAL_SEPARATOR,
  DEFAULT_GROUP_SEPARATOR,
} from "./constants.js";

import { sanitizeNumberInput } from "./sanitizer.js";

/**
 * Converte uma entrada numérica textual em Number.
 *
 * Retorna:
 * - number, quando a entrada representa um valor válido;
 * - null, quando a entrada está vazia ou incompleta;
 *
 * Não aplica:
 * - arredondamento;
 * - limites de min/max;
 * - formatação de saída.
 */
export function parseNumber(
  value,
  {
    decimalSeparator = DEFAULT_DECIMAL_SEPARATOR,
    groupSeparator = DEFAULT_GROUP_SEPARATOR,
    allowNegative = DEFAULT_ALLOW_NEGATIVE,
  } = {},
) {
  if (value === null || value === undefined || value === "") {
    return null;
  }

  const source = String(value);

  const withoutGrouping = groupSeparator
    ? source.split(groupSeparator).join("")
    : source;

  const sanitized = sanitizeNumberInput(withoutGrouping, {
    decimalSeparator,
    allowNegative,
  });

  if (
    sanitized === "" ||
    sanitized === "-" ||
    sanitized === decimalSeparator ||
    sanitized === `-${decimalSeparator}`
  ) {
    return null;
  }

  const normalized =
    decimalSeparator === "."
      ? sanitized
      : sanitized.replace(decimalSeparator, ".");

  const parsed = Number(normalized);

  return Number.isFinite(parsed) ? parsed : null;
}
