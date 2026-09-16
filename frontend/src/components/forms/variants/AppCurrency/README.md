# AppCurrency

Campo monetário baseado em `AppInput` e na Number Engine do Design System.

O componente mantém o valor do `v-model` como:

```ts
number | null;
```

A formatação visual respeita o `locale`, a moeda, a precisão e as demais configurações do componente.

## Importação

```js
import { AppCurrency } from "@/components/forms";
```

## Uso básico

```vue
<script setup>
import { ref } from "vue";

import { AppCurrency } from "@/components/forms";

const amount = ref(1234.56);
</script>

<template>
  <AppCurrency v-model="amount" id="amount" name="amount" label="Valor" />
</template>
```

No locale padrão `pt-BR`, o campo será exibido como:

```text
R$ 1.234,56
```

O valor armazenado continuará sendo:

```js
1234.56;
```

## Contrato do v-model

O componente emite:

```ts
number | null;
```

Comportamento:

```text
Entrada válida                    → number
Campo vazio + allowEmpty=true     → null
Campo vazio + allowEmpty=false    → 0
Valor abaixo de min               → min
Valor acima de max                → max
```

A unidade monetária é armazenada como número decimal comum:

```js
1234.56;
```

A conversão para centavos inteiros deve ocorrer na camada responsável pela integração com a API ou com o banco de dados.

Exemplo:

```js
const amountInCents = Math.round(amount * 100);
```

## Props próprias

| Prop            | Tipo      |      Padrão | Descrição                                   |
| --------------- | --------- | ----------: | ------------------------------------------- |
| `modelValue`    | `Number`  |      `null` | Valor monetário controlado pelo `v-model`.  |
| `locale`        | `String`  |   `'pt-BR'` | Locale usado na entrada e na formatação.    |
| `currency`      | `String`  |     `'BRL'` | Código ISO 4217 da moeda.                   |
| `precision`     | `Number`  |         `2` | Quantidade de casas decimais, entre 0 e 20. |
| `min`           | `Number`  | `undefined` | Limite mínimo permitido.                    |
| `max`           | `Number`  | `undefined` | Limite máximo permitido.                    |
| `allowNegative` | `Boolean` |     `false` | Permite valores negativos.                  |
| `allowEmpty`    | `Boolean` |      `true` | Permite que o valor emitido seja `null`.    |
| `useGrouping`   | `Boolean` |      `true` | Exibe separadores de milhares fora do foco. |
| `showCurrency`  | `Boolean` |      `true` | Exibe o símbolo ou código da moeda.         |

O componente também herda as props públicas do `AppInput`, como:

```text
id
name
label
placeholder
hint
error
required
disabled
readonly
autofocus
```

## Eventos

| Evento              | Payload          | Descrição                              |
| ------------------- | ---------------- | -------------------------------------- |
| `update:modelValue` | `number \| null` | Emitido quando o valor monetário muda. |
| `focus`             | `FocusEvent`     | Emitido quando o controle recebe foco. |
| `blur`              | `FocusEvent`     | Emitido quando o controle perde foco.  |

## Slots

### prepend

Substitui a área anterior ao controle.

Quando esse slot é fornecido, ele substitui o símbolo monetário padrão.

```vue
<AppCurrency v-model="amount" id="amount" label="Valor">
  <template #prepend>
    <span>BRL</span>
  </template>
</AppCurrency>
```

### append

Adiciona conteúdo após o controle.

```vue
<AppCurrency v-model="amount" id="amount" label="Valor">
  <template #append>
    <span>mensal</span>
  </template>
</AppCurrency>
```

## Exemplos

### Real brasileiro

```vue
<AppCurrency v-model="amount" id="amount" label="Valor" />
```

### Dólar americano

```vue
<AppCurrency
  v-model="amount"
  id="amount-usd"
  label="Valor em dólar"
  locale="en-US"
  currency="USD"
/>
```

### Sem símbolo monetário

```vue
<AppCurrency
  v-model="amount"
  id="amount"
  label="Valor líquido"
  :show-currency="false"
/>
```

### Valores negativos

```vue
<AppCurrency
  v-model="adjustment"
  id="adjustment"
  label="Ajuste financeiro"
  :allow-negative="true"
/>
```

### Limites

```vue
<AppCurrency
  v-model="expenseLimit"
  id="expense-limit"
  label="Limite de despesa"
  :min="0"
  :max="10000"
/>
```

Valores fora do intervalo são normalizados para o limite correspondente.

### Precisão personalizada

```vue
<AppCurrency
  v-model="exchangeRate"
  id="exchange-rate"
  label="Cotação"
  :precision="3"
/>
```

### Valor obrigatório

```vue
<AppCurrency
  v-model="amount"
  id="required-amount"
  label="Valor obrigatório"
  :allow-empty="false"
  required
/>
```

Quando o conteúdo é apagado, o componente emite:

```js
0;
```

### Estado de erro

```vue
<AppCurrency
  v-model="amount"
  id="amount"
  label="Valor"
  error="Informe um valor válido."
  required
/>
```

## Comportamento de foco

Fora do foco, o valor é exibido com a formatação completa:

```text
1.234,56
```

Durante a edição, o agrupamento é removido:

```text
1234,56
```

Ao perder o foco, o agrupamento é reaplicado.

## Acessibilidade

O `AppCurrency` utiliza a infraestrutura de acessibilidade de `AppInput` e `AppField`.

Isso inclui:

- associação entre `label` e `input`;
- encaminhamento de `required`;
- descrição por `hint`;
- associação da mensagem de erro;
- estados `disabled` e `readonly`;
- `inputmode="decimal"` para teclados virtuais.

O símbolo monetário padrão é decorativo e não altera o nome acessível do campo.

## Number Engine

O componente reutiliza a Number Engine para:

```text
sanitização
parsing
arredondamento
limites
formatação
```

O `AppCurrency` não depende do `AppNumber`. Ambos consomem a mesma infraestrutura numérica compartilhada.

## Testes

Os testes do componente estão em:

```text
tests/components/forms/AppCurrency.spec.js
```

Cobertura atual:

```text
renderização
label
inputmode
locale
moeda
símbolo
precisão
foco
blur
v-model
campo vazio
valores negativos
min
max
```

Os testes da Number Engine estão em:

```text
tests/unit/number/
```
