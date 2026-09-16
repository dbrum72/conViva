# AppInput

Campo de entrada genérico baseado em `BaseField` e `InputControl`.

`AppInput` é o componente público base para entradas textuais simples e também serve como infraestrutura para várias variantes do Design System, como:

```text
AppEmail
AppPassword
AppSearch
AppPhone
AppURL
AppNumber
AppCurrency
```

## Responsabilidade

O componente é responsável por:

- compor `BaseField` e `InputControl`;
- encaminhar props de campo e controle;
- renderizar label, hint e erro;
- permitir slots `prepend` e `append`;
- retransmitir eventos do controle;
- integrar acessibilidade via `AppField` e `useFieldContext`.

## Arquitetura

Fluxo interno:

```text
AppInput
   ↓
BaseField
   ↓
AppField
   ↓
InputControl
```

As variantes reutilizam `AppInput` para evitar duplicação de estrutura.

## Importação

```js
import { AppInput } from "@/components/forms";
```

## Uso básico

```vue
<script setup>
import { ref } from "vue";

import { AppInput } from "@/components/forms";

const name = ref("");
</script>

<template>
  <AppInput
    v-model="name"
    id="name"
    name="name"
    label="Nome"
    placeholder="Digite seu nome"
  />
</template>
```

## Contrato do v-model

`AppInput` aceita:

```ts
string | number;
```

mas, durante a edição, o `InputControl` emite:

```ts
string;
```

porque o valor vem de:

```js
event.target.value;
```

Exemplo:

```vue
<AppInput v-model="value" type="number" />
```

Mesmo com:

```text
type="number"
```

o payload emitido durante a edição será textual:

```js
"25";
```

Quando for necessário normalizar tipos, utilize uma variante específica como `AppNumber`.

## Props de campo

O componente herda as props de `fieldProps`:

| Prop       | Tipo      | Padrão      | Descrição                 |
| ---------- | --------- | ----------- | ------------------------- |
| `id`       | `String`  | `undefined` | Identificador do controle |
| `label`    | `String`  | `''`        | Label do campo            |
| `hint`     | `String`  | `''`        | Texto auxiliar            |
| `error`    | `String`  | `''`        | Mensagem de erro          |
| `required` | `Boolean` | `false`     | Campo obrigatório         |
| `disabled` | `Boolean` | `false`     | Campo desabilitado        |
| `readonly` | `Boolean` | `false`     | Campo somente leitura     |

## Props de controle

Também encaminha as props suportadas por `InputControl`:

| Prop           | Tipo               | Padrão      |
| -------------- | ------------------ | ----------- |
| `modelValue`   | `String \| Number` | `''`        |
| `type`         | `String`           | `'text'`    |
| `name`         | `String`           | `undefined` |
| `placeholder`  | `String`           | `''`        |
| `autofocus`    | `Boolean`          | `false`     |
| `autocomplete` | `String`           | `'off'`     |
| `maxlength`    | `Number`           | `undefined` |
| `minlength`    | `Number`           | `undefined` |
| `inputmode`    | `String`           | `undefined` |
| `min`          | `Number \| String` | `undefined` |
| `max`          | `Number \| String` | `undefined` |
| `step`         | `Number \| String` | `undefined` |

## Eventos

| Evento              | Payload      | Descrição                |
| ------------------- | ------------ | ------------------------ |
| `update:modelValue` | `String`     | Emitido durante a edição |
| `focus`             | `FocusEvent` | Emitido ao receber foco  |
| `blur`              | `FocusEvent` | Emitido ao perder foco   |

## Slots

### prepend

Renderiza conteúdo antes do controle.

```vue
<AppInput v-model="amount" id="amount" label="Valor">
  <template #prepend>
    R$
  </template>
</AppInput>
```

### append

Renderiza conteúdo após o controle.

```vue
<AppInput v-model="weight" id="weight" label="Peso">
  <template #append>
    kg
  </template>
</AppInput>
```

## Com hint

```vue
<AppInput
  v-model="email"
  id="email"
  label="E-mail"
  hint="Utilize um endereço válido."
/>
```

Quando não existe erro, o hint é associado ao controle através de:

```text
aria-describedby
```

## Com erro

```vue
<AppInput
  v-model="email"
  id="email"
  label="E-mail"
  error="Informe um e-mail válido."
/>
```

Quando existe erro:

- o hint deixa de ser exibido;
- a mensagem de erro é renderizada;
- `aria-describedby` referencia o erro;
- `aria-invalid="true"` é aplicado ao input.

## Required

```vue
<AppInput v-model="name" id="name" label="Nome" required />
```

O indicador visual `*` é renderizado pela infraestrutura de `AppField`.

## Readonly

```vue
<AppInput v-model="code" id="code" label="Código" readonly />
```

## Disabled

```vue
<AppInput v-model="value" id="value" label="Valor" disabled />
```

## Limites textuais

```vue
<AppInput
  v-model="title"
  id="title"
  label="Título"
  :minlength="3"
  :maxlength="120"
/>
```

## Input mode

```vue
<AppInput v-model="phone" id="phone" label="Telefone" inputmode="tel" />
```

`inputmode` é útil principalmente em dispositivos móveis para sugerir o teclado mais adequado.

## Autocomplete

```vue
<AppInput
  v-model="name"
  id="name"
  name="name"
  label="Nome"
  autocomplete="name"
/>
```

## Acessibilidade

`AppInput` utiliza a infraestrutura compartilhada do Design System.

Fluxo:

```text
AppInput
    ↓
BaseField
    ↓
AppField fornece contexto
    ↓
InputControl consome useFieldContext()
```

Isso garante suporte a:

```text
label + for
aria-describedby
aria-invalid
required
disabled
readonly
```

## Encaminhamento de props

As props enviadas ao `InputControl` são selecionadas através de:

```js
pick(props, INPUT_CONTROL_KEYS);
```

Isso mantém a fronteira entre:

```text
props de campo
```

e:

```text
props do controle
```

e evita encaminhamento acidental de atributos indevidos.

## Variantes

`AppInput` é a base estrutural para diversas variantes.

### AppEmail

Especializa entrada de e-mail.

### AppPassword

Adiciona comportamento específico de senha.

### AppSearch

Especializa entrada para busca.

### AppPhone

Adiciona comportamento de telefone.

### AppURL

Especializa entrada de URL.

### AppNumber

Adiciona parsing e formatação numérica.

### AppCurrency

Adiciona comportamento monetário.

Essas variantes devem reutilizar a infraestrutura do `AppInput` sempre que possível, em vez de duplicar estrutura.

## Boas práticas

Use `AppInput` diretamente para entradas textuais genéricas.

Prefira variantes quando houver semântica específica:

```text
e-mail → AppEmail
senha → AppPassword
telefone → AppPhone
URL → AppURL
número → AppNumber
moeda → AppCurrency
pesquisa → AppSearch
```

Isso mantém o contrato de cada campo explícito e centraliza regras específicas.

## Playground

Consulte:

```text
Playground → Forms → AppInput
```

Arquivo:

```text
src/views/playground/forms/InputPlayground.vue
```

## Testes

O comportamento fundamental é coberto por:

```text
tests/components/forms/controls/InputControl.spec.js
tests/components/forms/fields/BaseField.spec.js
```

As variantes possuem cobertura adicional conforme seus contratos específicos.
