# CheckboxGroup

Componente composto para seleção múltipla de opções.

`CheckboxGroup` organiza múltiplos `AppCheckbox` em um único grupo semântico e mantém o `v-model` como um array de valores selecionados.

## Importação

```js
import { CheckboxGroup } from "@/components/forms";
```

## Uso básico

```vue
<script setup>
import { ref } from "vue";

import { CheckboxGroup } from "@/components/forms";

const permissions = ref(["read"]);

const options = [
  {
    label: "Leitura",
    value: "read",
  },
  {
    label: "Escrita",
    value: "write",
  },
];
</script>

<template>
  <CheckboxGroup
    v-model="permissions"
    id="permissions"
    name="permissions"
    label="Permissões"
    :options="options"
  />
</template>
```

## Contrato do v-model

O `modelValue` é sempre um array:

```js
[];
```

ou:

```js
["read", "write"];
```

O componente não modifica o array recebido diretamente.

Ao selecionar uma nova opção, emite um novo array:

```js
[...modelValue, value];
```

Ao remover uma opção, utiliza:

```js
modelValue.filter(...)
```

Isso mantém o fluxo de dados imutável.

## Estrutura semântica

O componente utiliza:

```text
fieldset
├── legend
├── group
│   ├── AppCheckbox
│   ├── AppCheckbox
│   └── AppCheckbox
└── hint ou error
```

## Props

| Prop             | Tipo                         | Padrão       | Descrição                                          |
| ---------------- | ---------------------------- | ------------ | -------------------------------------------------- |
| `modelValue`     | `Array`                      | `[]`         | Valores atualmente selecionados                    |
| `id`             | `String`                     | `undefined`  | Identificador base do grupo                        |
| `name`           | `String`                     | obrigatório  | Nome HTML compartilhado pelas opções               |
| `label`          | `String`                     | `''`         | Legend do grupo                                    |
| `hint`           | `String`                     | `''`         | Texto auxiliar                                     |
| `error`          | `String`                     | `''`         | Mensagem de erro                                   |
| `required`       | `Boolean`                    | `false`      | Indica que o grupo exige seleção                   |
| `disabled`       | `Boolean`                    | `false`      | Desabilita todas as opções                         |
| `options`        | `Array`                      | `[]`         | Opções disponíveis                                 |
| `optionLabel`    | `String`                     | `'label'`    | Propriedade usada como label                       |
| `optionValue`    | `String`                     | `'value'`    | Propriedade usada como valor                       |
| `optionDisabled` | `String`                     | `'disabled'` | Propriedade que desabilita individualmente a opção |
| `orientation`    | `'vertical' \| 'horizontal'` | `'vertical'` | Direção visual das opções                          |

## Eventos

| Evento              | Payload      |
| ------------------- | ------------ |
| `update:modelValue` | `Array`      |
| `focus`             | `FocusEvent` |
| `blur`              | `FocusEvent` |

## Preservação de tipos

A seleção utiliza:

```js
Object.is(selectedValue, optionValue);
```

Logo, tipos distintos permanecem distintos:

```js
1 !== "1";
false !== 0;
```

Exemplo numérico:

```js
[
  { label: "Nível 1", value: 1 },
  { label: "Nível 2", value: 2 },
];
```

O valor poderá ser:

```js
[1, 2];
```

e não:

```js
["1", "2"];
```

Valores booleanos também são preservados.

## Opções primitivas

Também são suportadas:

```js
["Leitura", "Escrita"];
```

Nesse caso, cada valor é usado como label e value.

## Propriedades personalizadas

```js
const roles = [
  {
    id: 10,
    name: "Administrador",
    blocked: false,
  },
  {
    id: 20,
    name: "Operador",
    blocked: true,
  },
];
```

```vue
<CheckboxGroup
  v-model="rolesSelected"
  id="roles"
  name="roles"
  label="Perfis"
  :options="roles"
  option-label="name"
  option-value="id"
  option-disabled="blocked"
/>
```

## Disabled global

```vue
<CheckboxGroup
  v-model="values"
  name="permissions"
  :options="options"
  disabled
/>
```

Todas as opções ficam desabilitadas.

## Disabled por opção

```js
[
  {
    label: "Leitura",
    value: "read",
  },
  {
    label: "Exclusão",
    value: "delete",
    disabled: true,
  },
];
```

Apenas a opção marcada fica indisponível.

## Required

```vue
<CheckboxGroup
  v-model="values"
  id="permissions"
  name="permissions"
  label="Permissões"
  :options="options"
  required
/>
```

O indicador obrigatório aparece uma única vez no `legend`.

O grupo também expõe:

```text
aria-required="true"
```

Importante: `required` no grupo significa semanticamente que uma seleção é exigida. Ele não é repassado individualmente aos `AppCheckbox`.

A regra de validação concreta permanece responsabilidade da camada de formulário.

## Hint

```vue
<CheckboxGroup
  v-model="values"
  id="categories"
  name="categories"
  label="Categorias"
  hint="Você pode selecionar uma ou mais opções."
  :options="options"
/>
```

## Erro

```vue
<CheckboxGroup
  v-model="values"
  id="categories"
  name="categories"
  label="Categorias"
  error="Selecione ao menos uma opção."
  :options="options"
/>
```

Quando existe erro:

- o hint é ocultado;
- a mensagem de erro é exibida;
- `aria-describedby` referencia o erro;
- `aria-invalid="true"` é aplicado ao `fieldset`.

## IDs gerados

Com:

```text
id="permissions"
```

as opções recebem:

```text
permissions-option-0
permissions-option-1
permissions-option-2
```

Isso evita geração manual de IDs pelo consumidor.

## Orientação

### Vertical

Padrão:

```vue
<CheckboxGroup orientation="vertical" />
```

### Horizontal

```vue
<CheckboxGroup orientation="horizontal" />
```

A orientação altera apenas o layout.

## Acessibilidade

O componente utiliza:

```text
fieldset
legend
role="group"
aria-required
aria-describedby
aria-invalid
```

Cada opção continua utilizando um `<input type="checkbox">` nativo através de `AppCheckbox`.

## Imutabilidade

O componente nunca executa operações mutáveis como:

```js
modelValue.push(...)
modelValue.splice(...)
```

Toda mudança produz uma nova referência de array.

Isso torna o componente adequado ao fluxo reativo do Vue e reduz efeitos colaterais.

## Indeterminate

`CheckboxGroup` não implementa estado `indeterminate` no nível do grupo nesta versão.

Esse comportamento permanece disponível em `AppCheckbox` e poderá ser utilizado futuramente em recursos como:

```text
Selecionar todos
Seleção parcial
Hierarquias
```

## Limites de seleção

A versão atual não implementa:

```text
minSelections
maxSelections
```

Essas regras devem permanecer externas até existir uma necessidade clara de incorporá-las ao contrato público.

## AppCheckbox ou CheckboxGroup?

Use `AppCheckbox` para uma escolha booleana independente:

```text
Aceito os termos
Receber notificações
```

Use `CheckboxGroup` quando várias opções independentes fizerem parte da mesma seleção:

```text
Permissões
Categorias
Recursos
Canais
```

## Playground

Consulte:

```text
Playground → Forms → CheckboxGroup
```

Arquivo:

```text
src/views/playground/forms/CheckboxGroupPlayground.vue
```

## Testes

Os testes estão em:

```text
tests/components/forms/groups/CheckboxGroup.spec.js
```

Cobertura atual:

```text
fieldset
legend
opções
name
ids
seleção inicial
adição
remoção
não duplicação
imutabilidade
number
boolean
distinção entre number e string
opções primitivas
optionLabel
optionValue
optionDisabled
disabled global
disabled individual
required
hint
erro
aria-required
aria-describedby
aria-invalid
orientação
focus
blur
```
