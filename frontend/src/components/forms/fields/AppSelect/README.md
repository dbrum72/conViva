# AppSelect

Campo de seleção baseado em `BaseField` e `SelectControl`.

O componente integra:

- label;
- hint;
- mensagem de erro;
- placeholder;
- estados `required`, `disabled` e `autofocus`;
- opções primitivas ou objetos;
- personalização de `optionLabel` e `optionValue`;
- preservação do tipo original do valor selecionado;
- eventos de foco, blur e atualização do `v-model`;
- associações ARIA fornecidas pela infraestrutura de formulários.

## Importação

```js
import { AppSelect } from "@/components/forms";
```

## Uso básico

```vue
<script setup>
import { ref } from "vue";

import { AppSelect } from "@/components/forms";

const status = ref(null);

const statusOptions = [
  {
    label: "Ativo",
    value: 1,
  },
  {
    label: "Inativo",
    value: 2,
  },
];
</script>

<template>
  <AppSelect
    v-model="status"
    id="status"
    name="status"
    label="Status"
    placeholder="Selecione..."
    :options="statusOptions"
  />
</template>
```

## Contrato do v-model

O `AppSelect` aceita e emite:

```ts
string | number | boolean | object | null;
```

O tipo original do valor definido em cada opção é preservado.

Exemplos:

```js
{ label: 'Ativo', value: 1 }
// emite 1

{ label: 'Sim', value: true }
// emite true

{ label: 'Administrador', value: 'admin' }
// emite 'admin'
```

Opções primitivas também são aceitas:

```js
["Baixa", "Média", "Alta"];
```

Nesse caso, o texto e o valor da opção serão iguais.

## Props próprias

| Prop          | Tipo                                            |    Padrão | Descrição                                            |
| ------------- | ----------------------------------------------- | --------: | ---------------------------------------------------- |
| `modelValue`  | `String \| Number \| Boolean \| Object \| null` |    `null` | Valor controlado pelo `v-model`.                     |
| `placeholder` | `String`                                        |      `''` | Opção inicial desabilitada exibida antes da seleção. |
| `options`     | `Array`                                         |      `[]` | Lista de opções disponíveis.                         |
| `optionLabel` | `String`                                        | `'label'` | Propriedade usada como texto da opção.               |
| `optionValue` | `String`                                        | `'value'` | Propriedade usada como valor da opção.               |

O componente também herda as props públicas de campo e controle, como:

```text
id
name
label
hint
error
required
disabled
autofocus
```

## Eventos

| Evento              | Payload                    | Descrição                              |
| ------------------- | -------------------------- | -------------------------------------- |
| `update:modelValue` | valor da opção selecionada | Emitido quando a seleção muda.         |
| `focus`             | `FocusEvent`               | Emitido quando o controle recebe foco. |
| `blur`              | `FocusEvent`               | Emitido quando o controle perde foco.  |

## Formatos de opções

### Objetos com `label` e `value`

```js
const options = [
  {
    label: "Ativo",
    value: 1,
  },
  {
    label: "Inativo",
    value: 2,
  },
];
```

```vue
<AppSelect v-model="status" :options="options" />
```

### Opções primitivas

```vue
<AppSelect v-model="priority" :options="['Baixa', 'Média', 'Alta']" />
```

### Propriedades personalizadas

```js
const users = [
  {
    id: 10,
    name: "Administrador",
  },
  {
    id: 20,
    name: "Operador",
  },
];
```

```vue
<AppSelect
  v-model="userId"
  id="user"
  label="Usuário"
  :options="users"
  option-label="name"
  option-value="id"
/>
```

O valor emitido será o conteúdo de `id`.

## Exemplos

### Com placeholder

```vue
<AppSelect
  v-model="status"
  id="status"
  label="Status"
  placeholder="Selecione..."
  :options="statusOptions"
/>
```

O placeholder é renderizado como uma opção desabilitada com valor vazio.

### Campo obrigatório

```vue
<AppSelect
  v-model="category"
  id="category"
  label="Categoria"
  required
  :options="categoryOptions"
/>
```

### Hint

```vue
<AppSelect
  v-model="status"
  id="status"
  label="Status"
  hint="Selecione a situação atual."
  :options="statusOptions"
/>
```

### Estado de erro

```vue
<AppSelect
  v-model="category"
  id="category"
  label="Categoria"
  error="Selecione uma opção."
  required
  :options="categoryOptions"
/>
```

Quando existe erro:

- o hint deixa de ser exibido;
- a mensagem de erro passa a descrever o controle;
- `aria-invalid="true"` é aplicado ao `<select>`.

### Desabilitado

```vue
<AppSelect
  v-model="status"
  id="status"
  label="Status"
  disabled
  :options="statusOptions"
/>
```

### Autofocus

```vue
<AppSelect
  v-model="status"
  id="status"
  label="Status"
  autofocus
  :options="statusOptions"
/>
```

## Readonly

O elemento HTML `<select>` não possui suporte nativo ao atributo `readonly`.

Por esse motivo, o `AppSelect` não encaminha `readonly` ao `SelectControl`.

Quando a seleção não puder ser alterada, use:

```vue
<AppSelect v-model="status" disabled />
```

Caso seja necessário exibir um valor sem aparência de controle desabilitado, utilize um componente de apresentação em vez de simular `readonly` no select.

## Acessibilidade

O componente utiliza a infraestrutura de acessibilidade de `BaseField` e `SelectControl`.

Isso inclui:

- associação entre `label` e `select`;
- `aria-describedby` apontando para hint ou erro;
- `aria-invalid="true"` quando há erro;
- encaminhamento de `required`;
- suporte nativo a `disabled`;
- suporte a `autofocus`.

Exemplo estrutural:

```html
<label for="status"> Status </label>

<select id="status" aria-describedby="status-hint">
  ...
</select>

<p id="status-hint">Selecione a situação atual.</p>
```

Quando há erro:

```html
<select id="status" aria-describedby="status-error" aria-invalid="true">
  ...
</select>

<p id="status-error">Selecione uma opção.</p>
```

## Preservação de tipos

O `SelectControl` não utiliza diretamente apenas:

```js
event.target.value;
```

porque o valor retornado pelo DOM seria sempre `string`.

Em vez disso, o componente localiza a opção selecionada dentro da lista original e emite o valor definido em `optionValue`.

Assim, estes tipos são preservados:

```text
string
number
boolean
object
null
```

## Arquitetura

O fluxo interno é:

```text
AppSelect
    ↓
BaseField
    ↓
SelectControl
```

O `AppSelect` é responsável por:

- compor campo e controle;
- selecionar as props encaminhadas;
- retransmitir eventos.

O `SelectControl` é responsável por:

- renderizar o elemento `<select>`;
- renderizar as opções;
- resolver labels e valores;
- preservar o tipo do valor selecionado.

A lista de props encaminhadas é definida por:

```text
SELECT_CONTROL_KEYS
```

em:

```text
src/components/forms/shared/constants/control-keys.js
```

## Playground

A demonstração visual está em:

```text
src/views/playground/forms/SelectPlayground.vue
```

## Testes

Os testes do componente estão em:

```text
tests/components/forms/AppSelect.spec.js
```

Cobertura atual:

```text
renderização
label
placeholder
opções
optionLabel
optionValue
opções primitivas
valores string
valores number
valores boolean
disabled
required
autofocus
focus
blur
hint
erro
aria-describedby
aria-invalid
```
