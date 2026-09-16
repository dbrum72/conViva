# AppNumber

Campo numérico baseado em `AppInput` e na Number Engine compartilhada do Design System.

O `AppNumber` oferece entrada numérica localizada em `pt-BR`, mantendo o `v-model` como valor numérico e separando o valor de domínio da representação textual exibida ao usuário.

## Responsabilidade

O componente é responsável por:

- aceitar entrada numérica textual;
- converter a entrada para `Number`;
- formatar valores no padrão `pt-BR`;
- controlar precisão a partir de `step`;
- aplicar limites `min` e `max`;
- permitir ou impedir valores negativos conforme `min`;
- tratar estado vazio;
- remover agrupamento durante a edição;
- reaplicar agrupamento fora do foco;
- reutilizar a Number Engine compartilhada.

## Arquitetura

Fluxo principal:

```text
AppNumber
    ↓
AppInput
    ↓
Number Engine
```

A Number Engine utilizada pelo componente está disponível em:

```text
src/components/forms/shared/number/
```

O componente consome principalmente:

```js
formatNumber;
processNumber;
```

## Importação

```js
import { AppNumber } from "@/components/forms";
```

## Uso básico

```vue
<script setup>
import { ref } from "vue";

import { AppNumber } from "@/components/forms";

const quantity = ref(null);
</script>

<template>
  <AppNumber
    v-model="quantity"
    id="quantity"
    name="quantity"
    label="Quantidade"
  />
</template>
```

## Contrato do v-model

O `modelValue` é:

```ts
number | null;
```

Exemplos válidos:

```js
10;
```

```js
1234.5;
```

```js
null;
```

O componente não expõe ao domínio o texto formatado exibido no input.

Por exemplo:

```text
exibição: 1.234,5
modelValue: 1234.5
```

## Entrada HTML

Embora seja um componente numérico, internamente o controle é renderizado como:

```html
<input type="text" inputmode="decimal" />
```

Essa decisão permite controlar integralmente:

- separador decimal;
- agrupamento;
- edição parcial;
- parsing;
- precisão;
- formatação localizada.

O componente não depende do comportamento inconsistente de `<input type="number">` entre navegadores e locales.

## Locale

A configuração atual é fixa em:

```text
pt-BR
```

Com:

```text
separador decimal   ,
separador de grupo  .
```

Exemplo:

```text
1234.56
```

é exibido fora do foco como:

```text
1.234,56
```

## Comportamento de foco

### Fora do foco

O valor é formatado com agrupamento:

```text
1.234,56
```

### Durante a edição

O agrupamento é removido:

```text
1234,56
```

Isso reduz interferência visual durante a digitação.

Ao receber foco, o componente executa uma formatação sem agrupamento e armazena esse conteúdo em `draftValue`.

Ao perder foco, o valor é novamente processado e a apresentação externa é recalculada.

## Number Engine

As opções utilizadas pela Number Engine são calculadas em:

```js
numberOptions;
```

A configuração atual inclui:

```js
{
  decimalSeparator: ',',
  groupSeparator: '.',
  allowNegative,
  precision,
  min,
  max,
  locale: 'pt-BR',
  useGrouping: true,
}
```

## Precisão

A precisão é derivada automaticamente de:

```text
step
```

### step inteiro

```vue
<AppNumber :step="1" />
```

Precisão:

```text
0
```

### Uma casa decimal

```vue
<AppNumber :step="0.1" />
```

Precisão:

```text
1
```

### Duas casas decimais

```vue
<AppNumber :step="0.01" />
```

Precisão:

```text
2
```

### step="any"

```vue
<AppNumber step="any" />
```

Nesse caso, a precisão máxima utilizada pelo componente é:

```text
6
```

## resolvePrecision

A função interna segue esta regra:

```js
function resolvePrecision(step) {
  if (step === "any") {
    return 6;
  }

  const numericStep = Number(step);

  if (!Number.isFinite(numericStep)) {
    return 0;
  }

  const stepText = String(numericStep);
  const decimalIndex = stepText.indexOf(".");

  return decimalIndex === -1 ? 0 : stepText.length - decimalIndex - 1;
}
```

Portanto, `step` influencia diretamente a quantidade máxima de casas decimais processadas e exibidas.

## Valores negativos

A permissão de valores negativos é inferida a partir de `min`.

A regra é:

```js
allowNegative = props.min === undefined || props.min < 0;
```

### Sem min

```vue
<AppNumber />
```

Valores negativos são aceitos.

### min negativo

```vue
<AppNumber :min="-100" />
```

Valores negativos também são aceitos.

### min igual ou maior que zero

```vue
<AppNumber :min="0" />
```

O sinal negativo não é permitido.

## Limites

O componente encaminha:

```text
min
max
```

para a Number Engine.

Exemplo:

```vue
<AppNumber v-model="percentage" :min="0" :max="100" />
```

Após o parsing, o valor emitido é:

```js
result.clamped;
```

Portanto:

```text
entrada: 150
max: 100
resultado: 100
```

e:

```text
entrada: -10
min: 0
resultado: 0
```

## Campo vazio

A prop:

```text
allowEmpty
```

controla o tratamento de uma entrada sem valor numérico válido.

### allowEmpty=true

Padrão:

```js
true;
```

Entrada vazia emite:

```js
null;
```

### allowEmpty=false

```vue
<AppNumber :allow-empty="false" />
```

Entrada vazia emite:

```js
0;
```

Essa regra é aplicada tanto durante a edição quanto no `blur`.

## Parsing durante a edição

Cada alteração textual passa por:

```js
processNumber(value, numberOptions.value);
```

Quando:

```js
result.parsed === null;
```

o componente emite:

```js
props.allowEmpty ? null : 0;
```

Caso contrário:

```js
emit("update:modelValue", result.clamped);
```

## Blur

No `blur`, o conteúdo atual de `draftValue` é novamente processado.

Se inválido ou vazio:

```js
null;
```

ou:

```js
0;
```

é emitido conforme `allowEmpty`.

Se válido, o componente:

1. aplica parsing;
2. aplica limites;
3. emite `result.clamped`;
4. atualiza o draft formatado sem agrupamento;
5. emite `blur`.

## Reatividade externa

O componente observa:

```js
props.modelValue;
```

Quando o valor externo muda e o campo não está focado, `draftValue` é atualizado automaticamente.

Isso permite que alterações programáticas no `v-model` sejam refletidas na interface.

## Props

O componente reutiliza `appInputProps` e especializa:

| Prop         | Tipo               | Padrão      | Descrição                              |
| ------------ | ------------------ | ----------- | -------------------------------------- |
| `modelValue` | `Number`           | `null`      | Valor numérico controlado              |
| `type`       | `String`           | `'number'`  | Identificação semântica da variante    |
| `inputmode`  | `String`           | `'decimal'` | Teclado virtual sugerido               |
| `min`        | `Number`           | `undefined` | Limite mínimo                          |
| `max`        | `Number`           | `undefined` | Limite máximo                          |
| `step`       | `Number \| String` | `1`         | Define incremento e precisão           |
| `allowEmpty` | `Boolean`          | `true`      | Permite `null`                         |
| `showIcon`   | `Boolean`          | `false`     | Prop atualmente disponível no contrato |

Também herda as demais props do `AppInput`, como:

```text
id
name
label
hint
error
placeholder
disabled
readonly
required
autofocus
autocomplete
maxlength
minlength
```

## Observação sobre type

Embora a prop tenha padrão:

```js
type: "number";
```

o controle interno é deliberadamente renderizado com:

```vue
type="text"
```

Essa é uma decisão de implementação necessária para a Number Engine controlar a representação localizada.

## Observação sobre showIcon

A prop:

```js
showIcon;
```

existe atualmente no contrato de `AppNumber`, com padrão:

```js
false;
```

mas não participa da renderização ou da configuração interna apresentada pela implementação atual.

Ela deve ser considerada parte do contrato existente, mas não implica exibição de ícone nesta versão.

## Encaminhamento de props

Antes de encaminhar props ao `AppInput`, o componente remove:

```js
modelValue;
type;
inputmode;
min;
max;
step;
allowEmpty;
showIcon;
```

O restante é armazenado em:

```js
inputProps;
```

e enviado através de:

```vue
v-bind="inputProps"
```

Isso evita que props específicas da variante sejam encaminhadas indevidamente ao controle textual interno.

## Eventos

| Evento              | Payload          | Descrição                              |
| ------------------- | ---------------- | -------------------------------------- |
| `update:modelValue` | `number \| null` | Emitido quando o valor processado muda |
| `focus`             | `FocusEvent`     | Emitido ao receber foco                |
| `blur`              | `FocusEvent`     | Emitido ao perder foco                 |

## Slots

O componente preserva os slots:

```text
prepend
append
```

Exemplo:

```vue
<AppNumber v-model="percentage" label="Percentual">
  <template #append>
    %
  </template>
</AppNumber>
```

## Acessibilidade

Por reutilizar `AppInput`, o componente herda a infraestrutura de:

```text
label
hint
error
aria-describedby
aria-invalid
required
disabled
readonly
```

Além disso:

```html
inputmode="decimal"
```

fornece uma sugestão adequada de teclado em dispositivos móveis.

## Estilos

`AppNumber` não possui CSS próprio.

A apresentação é herdada de:

```text
AppInput
```

Isso mantém a variante concentrada em comportamento e evita duplicação visual.

## AppNumber ou AppInput?

Use `AppNumber` quando o valor de domínio precisar ser efetivamente numérico.

Prefira:

```vue
<AppNumber v-model="quantity" />
```

em vez de:

```vue
<AppInput v-model="quantity" type="number" />
```

A diferença é importante:

```text
AppInput  → edição textual
AppNumber → parsing + number | null + limites + formatação
```

## AppNumber ou AppCurrency?

Use `AppNumber` para quantidades e valores numéricos genéricos:

```text
quantidade
percentual
peso
altura
índice
taxa
```

Use `AppCurrency` para valores monetários:

```text
preço
honorários
saldo
despesa
receita
```

`AppCurrency` adiciona semântica e apresentação próprias de moeda.

## Playground

Consulte:

```text
Playground → Forms → AppNumber
```

Arquivo:

```text
src/views/playground/forms/NumberPlayground.vue
```

## Testes

A Number Engine é coberta por:

```text
tests/unit/number/
```

incluindo:

```text
sanitizer
parser
validators
formatter
integration
```

O contrato de `AppNumber` depende diretamente dessa infraestrutura compartilhada.
