import {
  DEFAULT_ALLOW_NEGATIVE,
  DEFAULT_DECIMAL_SEPARATOR,
  DEFAULT_GROUP_SEPARATOR,
  DEFAULT_NUMBER_LOCALE,
  DEFAULT_NUMBER_PRECISION,
  DEFAULT_USE_GROUPING,
} from "./constants.js";

import { formatNumber } from "./formatter.js";
import { parseNumber } from "./parser.js";
import { sanitizeNumberInput } from "./sanitizer.js";
import { clampNumber, roundToPrecision } from "./validators.js";

/**
 * Executa o pipeline completo de processamento numérico.
 *
 * Fluxo:
 * entrada
 * → sanitização
 * → parsing
 * → arredondamento
 * → aplicação de limites
 * → formatação
 *
 * Retorna os estados intermediários para permitir diagnóstico,
 * validação e reutilização pelos componentes.
 */
export function processNumber(
  value,
  {
    decimalSeparator = DEFAULT_DECIMAL_SEPARATOR,
    groupSeparator = DEFAULT_GROUP_SEPARATOR,
    allowNegative = DEFAULT_ALLOW_NEGATIVE,
    precision = DEFAULT_NUMBER_PRECISION,
    min,
    max,
    locale = DEFAULT_NUMBER_LOCALE,
    useGrouping = DEFAULT_USE_GROUPING,
    minimumFractionDigits = 0,
    maximumFractionDigits = precision,
  } = {},
) {
  const sanitized = sanitizeNumberInput(value, {
    decimalSeparator,
    allowNegative,
  });

  const parsed = parseNumber(sanitized, {
    decimalSeparator,
    groupSeparator,
    allowNegative,
  });

  if (parsed === null) {
    return {
      sanitized,
      parsed: null,
      rounded: null,
      clamped: null,
      formatted: "",
    };
  }

  const rounded = roundToPrecision(parsed, precision);

  const clamped = clampNumber(rounded, {
    min,
    max,
  });

  const formatted = formatNumber(clamped, {
    locale,
    useGrouping,
    minimumFractionDigits,
    maximumFractionDigits,
  });

  return {
    sanitized,
    parsed,
    rounded,
    clamped,
    formatted,
  };
}
