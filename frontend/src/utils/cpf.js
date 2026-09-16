export function isValidCpf(value) {
  const cpf = String(value ?? "").replace(/\D/g, "");

  if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
    return false;
  }

  return [9, 10].every((position) => {
    let sum = 0;
    const initialWeight = position + 1;

    for (let index = 0; index < position; index++) {
      sum += Number(cpf[index]) * (initialWeight - index);
    }

    const remainder = sum % 11;
    const digit = remainder < 2 ? 0 : 11 - remainder;

    return Number(cpf[position]) === digit;
  });
}
