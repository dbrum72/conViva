/**
 * Verifica se o valor é um número finito.
 */
export function isFiniteNumber(value) {
  return typeof value === "number" && Number.isFinite(value);
}

/**
 * Verifica se o valor respeita o limite mínimo.
 *
 * `undefined` ou `null` significam ausência de limite.
 */
export function isWithinMin(value, min) {
  if (min === undefined || min === null) {
    return true;
  }

  return isFiniteNumber(value) && value >= Number(min);
}

/**
 * Verifica se o valor respeita o limite máximo.
 *
 * `undefined` ou `null` significam ausência de limite.
 */
export function isWithinMax(value, max) {
  if (max === undefined || max === null) {
    return true;
  }

  return isFiniteNumber(value) && value <= Number(max);
}

/**
 * Verifica se o valor respeita os limites mínimo e máximo.
 */
export function isWithinRange(value, { min, max } = {}) {
  return isWithinMin(value, min) && isWithinMax(value, max);
}

/**
 * Restringe o valor aos limites informados.
 */
export function clampNumber(value, { min, max } = {}) {
  if (!isFiniteNumber(value)) {
    return null;
  }

  let result = value;

  if (min !== undefined && min !== null) {
    result = Math.max(result, Number(min));
  }

  if (max !== undefined && max !== null) {
    result = Math.min(result, Number(max));
  }

  return result;
}

/**
 * Verifica se o valor respeita o incremento configurado.
 *
 * A origem padrão é zero, mas pode ser alterada para `min`.
 */
export function isStepAligned(
  value,
  step,
  origin = 0,
  epsilon = Number.EPSILON * 100,
) {
  if (!isFiniteNumber(value)) {
    return false;
  }

  if (step === undefined || step === null || step === "any") {
    return true;
  }

  const numericStep = Number(step);
  const numericOrigin = Number(origin);

  if (
    !Number.isFinite(numericStep) ||
    numericStep <= 0 ||
    !Number.isFinite(numericOrigin)
  ) {
    return false;
  }

  const quotient = (value - numericOrigin) / numericStep;

  return Math.abs(quotient - Math.round(quotient)) <= epsilon;
}

/**
 * Limita a quantidade de casas decimais.
 */
export function roundToPrecision(value, precision = 0) {
  if (!isFiniteNumber(value)) {
    return null;
  }

  const normalizedPrecision = normalizePrecision(precision);
  const factor = 10 ** normalizedPrecision;

  return Math.round((value + Number.EPSILON) * factor) / factor;
}

function normalizePrecision(value) {
  const numericValue = Number(value);

  if (!Number.isInteger(numericValue) || numericValue < 0) {
    return 0;
  }

  return Math.min(numericValue, 20);
}
