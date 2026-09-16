# AppTextarea

Campo de texto multilinha baseado em `BaseField` e `TextareaControl`.

O componente integra:

- label;
- hint;
- mensagem de erro;
- estados `required`, `readonly` e `disabled`;
- atributos nativos de `<textarea>`;
- eventos de foco, blur e atualização do `v-model`;
- associações ARIA gerenciadas pela infraestrutura de formulários.

## Importação

```js
import { AppTextarea } from "@/components/forms";
```

## Uso básico

```vue
<script setup>
import { ref } from "vue";

import { AppTextarea } from "@/components/forms";

const description = ref("");
</script>

<template>
  <AppTextarea
    v-model="description"
    id="description"
    name="description"
    label="Descrição"
    placeholder="Digite uma descrição..."
  />
</template>
```

## Contrato do v-model

O `AppTextarea` aceita e emite:

```ts
string | number;
```

Na prática, o uso recomendado é com `string`:

```js
const description = ref("");
```

Durante a edição, o componente emite:

```text
update:modelValue
```

com o conteúdo atual do `<textarea>`.

## Props próprias

| Prop           | Tipo                        |      Padrão | Descrição                                  |
| -------------- | --------------------------- | ----------: | ------------------------------------------ |
| `modelValue`   | `String \| Number`          |        `''` | Valor controlado pelo `v-model`.           |
| `placeholder`  | `String`                    |        `''` | Texto auxiliar exibido no controle vazio.  |
| `autocomplete` | `String`                    |     `'off'` | Configuração de preenchimento automático.  |
| `maxlength`    | `Number`                    | `undefined` | Quantidade máxima de caracteres.           |
| `minlength`    | `Number`                    | `undefined` | Quantidade mínima de caracteres.           |
| `rows`         | `Number`                    |         `4` | Quantidade inicial de linhas visíveis.     |
| `cols`         | `Number`                    | `undefined` | Largura sugerida em colunas de caracteres. |
| `wrap`         | `'soft' \| 'hard' \| 'off'` |    `'soft'` | Estratégia de quebra de linha do textarea. |

O componente também herda as props públicas de campo e controle, como:

```text
id
name
label
hint
error
required
disabled
readonly
autofocus
```

## Eventos

| Evento              | Payload      | Descrição                              |
| ------------------- | ------------ | -------------------------------------- |
| `update:modelValue` | `string`     | Emitido quando o conteúdo é alterado.  |
| `focus`             | `FocusEvent` | Emitido quando o controle recebe foco. |
| `blur`              | `FocusEvent` | Emitido quando o controle perde foco.  |

## Exemplos

### Padrão

```vue
<AppTextarea
  v-model="description"
  id="description"
  label="Descrição"
  placeholder="Digite uma descrição..."
/>
```

### Quantidade de linhas

```vue
<AppTextarea v-model="notes" id="notes" label="Observações" :rows="8" />
```

### Limite de caracteres

```vue
<AppTextarea v-model="summary" id="summary" label="Resumo" :maxlength="500" />
```

### Limites mínimo e máximo

```vue
<AppTextarea
  v-model="reason"
  id="reason"
  label="Justificativa"
  :minlength="20"
  :maxlength="1000"
/>
```

### Campo obrigatório

```vue
<AppTextarea v-model="reason" id="reason" label="Justificativa" required />
```

### Hint

```vue
<AppTextarea
  v-model="description"
  id="description"
  label="Descrição"
  hint="Informe uma descrição objetiva."
/>
```

### Estado de erro

```vue
<AppTextarea
  v-model="description"
  id="description"
  label="Descrição"
  error="Campo obrigatório."
  required
/>
```

Quando existe erro:

- o hint deixa de ser exibido;
- a mensagem de erro passa a descrever o controle;
- `aria-invalid="true"` é aplicado ao `<textarea>`.

### Somente leitura

```vue
<AppTextarea v-model="text" id="text" label="Texto" readonly />
```

### Desabilitado

```vue
<AppTextarea v-model="text" id="text" label="Texto" disabled />
```

### Quebra de linha

```vue
<AppTextarea
  v-model="content"
  id="content"
  label="Conteúdo"
  wrap="hard"
  :cols="60"
/>
```

Valores aceitos para `wrap`:

```text
soft
hard
off
```

## Contador de caracteres

O `AppTextarea` não renderiza contador internamente.

O contador pode ser implementado na camada de aplicação:

```vue
<script setup>
import { ref } from "vue";

const notes = ref("");
const maxLength = 120;
</script>

<template>
  <AppTextarea
    v-model="notes"
    id="notes"
    label="Observações"
    :maxlength="maxLength"
  />

  <p>{{ notes.length }}/{{ maxLength }}</p>
</template>
```

Essa decisão mantém o componente focado no controle de entrada e permite que cada interface escolha como apresentar o contador.

## Acessibilidade

O componente utiliza a infraestrutura de acessibilidade de `BaseField`, `AppField` e `TextareaControl`.

Isso inclui:

- associação entre `label` e `textarea`;
- `aria-describedby` apontando para hint ou erro;
- `aria-invalid="true"` quando há erro;
- encaminhamento de `required`;
- suporte nativo a `disabled`;
- suporte nativo a `readonly`.

Exemplo estrutural:

```html
<label for="description"> Descrição </label>

<textarea id="description" aria-describedby="description-hint"></textarea>

<p id="description-hint">Informe uma descrição objetiva.</p>
```

Quando há erro:

```html
<textarea
  id="description"
  aria-describedby="description-error"
  aria-invalid="true"
></textarea>

<p id="description-error">Campo obrigatório.</p>
```

## Arquitetura

O fluxo interno é:

```text
AppTextarea
    ↓
BaseField
    ↓
TextareaControl
```

O `AppTextarea` é responsável por:

- compor campo e controle;
- selecionar as props encaminhadas;
- retransmitir eventos.

O `TextareaControl` é responsável pelo elemento HTML nativo.

A lista de props encaminhadas é definida por:

```text
TEXTAREA_CONTROL_KEYS
```

em:

```text
src/components/forms/shared/constants/control-keys.js
```

## Playground

A demonstração visual está em:

```text
src/views/playground/forms/TextareaPlayground.vue
```

## Testes

Os testes do componente estão em:

```text
tests/components/forms/AppTextarea.spec.js
```

Cobertura atual:

```text
renderização
label
valor inicial
rows
cols
wrap
placeholder
maxlength
minlength
required
readonly
disabled
v-model
focus
blur
hint
erro
aria-describedby
aria-invalid
```
