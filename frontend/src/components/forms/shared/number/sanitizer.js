import {
  DEFAULT_ALLOW_NEGATIVE,
  DEFAULT_DECIMAL_SEPARATOR,
} from "./constants.js";

/**
 * Normaliza uma entrada textual numérica sem convertê-la para Number.
 *
 * Responsabilidades:
 * - remover caracteres não numéricos;
 * - preservar no máximo um separador decimal;
 * - preservar o sinal negativo apenas no início;
 * - respeitar a configuração de valores negativos.
 *
 * Não realiza:
 * - conversão para Number;
 * - aplicação de separador de milhares;
 * - arredondamento;
 * - validação de min/max.
 */
export function sanitizeNumberInput(
  value,
  {
    decimalSeparator = DEFAULT_DECIMAL_SEPARATOR,
    allowNegative = DEFAULT_ALLOW_NEGATIVE,
  } = {},
) {
  if (value === null || value === undefined) {
    return "";
  }

  const source = String(value);

  const hasNegativeSign = allowNegative && source.trimStart().startsWith("-");

  let sanitized = source.replace(
    new RegExp(`[^0-9${escapeRegExp(decimalSeparator)}]`, "g"),
    "",
  );

  const firstSeparatorIndex = sanitized.indexOf(decimalSeparator);

  if (firstSeparatorIndex !== -1) {
    const integerPart = sanitized.slice(0, firstSeparatorIndex);
    const decimalPart = sanitized
      .slice(firstSeparatorIndex + decimalSeparator.length)
      .replaceAll(decimalSeparator, "");

    sanitized = `${integerPart}${decimalSeparator}${decimalPart}`;
  }

  if (hasNegativeSign && sanitized) {
    sanitized = `-${sanitized}`;
  }

  return sanitized;
}

function escapeRegExp(value) {
  return value.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}
