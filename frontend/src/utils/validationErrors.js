export function clearValidationErrors(errors) {
  Object.keys(errors).forEach((key) => {
    errors[key] = "";
  });

  return errors;
}

export function applyValidationErrors(errors, validationErrors = {}) {
  Object.keys(errors).forEach((key) => {
    errors[key] = validationErrors[key]?.[0] ?? "";
  });

  return errors;
}
