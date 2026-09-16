# RadioGroup

Componente composto para seleção exclusiva entre várias opções.

`RadioGroup` organiza múltiplos `AppRadio` em um único grupo semântico, centralizando:

- `modelValue`;
- `name`;
- label do grupo;
- hint;
- erro;
- required;
- disabled;
- opções;
- preservação de tipos;
- orientação vertical ou horizontal.

## Importação

```js
import { RadioGroup } from "@/components/forms";
```

## Uso básico

```vue
<script setup>
import { ref } from "vue";

import { RadioGroup } from "@/components/forms";

const gender = ref(null);

const options = [
  {
    label: "Masculino",
    value: "M",
  },
  {
    label: "Feminino",
    value: "F",
  },
];
</script>

<template>
  <RadioGroup
    v-model="gender"
    id="gender"
    name="gender"
    label="Sexo"
    :options="options"
  />
</template>
```

## Estrutura semântica

O componente utiliza:

```text
fieldset
├── legend
├── radiogroup
│   ├── AppRadio
│   ├── AppRadio
│   └── AppRadio
└── hint ou error
```

Essa estrutura oferece semântica nativa adequada para um conjunto de opções mutuamente exclusivas.

## Props

| Prop             | Tipo                                  | Padrão       | Descrição                                    |
| ---------------- | ------------------------------------- | ------------ | -------------------------------------------- |
| `modelValue`     | `String \| Number \| Boolean \| null` | `null`       | Valor atualmente selecionado                 |
| `id`             | `String`                              | `undefined`  | Identificador base do grupo                  |
| `name`           | `String`                              | obrigatório  | Nome HTML compartilhado por todos os radios  |
| `label`          | `String`                              | `''`         | Label/legend do grupo                        |
| `hint`           | `String`                              | `''`         | Texto auxiliar do grupo                      |
| `error`          | `String`                              | `''`         | Mensagem de erro do grupo                    |
| `required`       | `Boolean`                             | `false`      | Marca o grupo como obrigatório               |
| `disabled`       | `Boolean`                             | `false`      | Desabilita todas as opções                   |
| `options`        | `Array`                               | `[]`         | Opções disponíveis                           |
| `optionLabel`    | `String`                              | `'label'`    | Propriedade usada como label                 |
| `optionValue`    | `String`                              | `'value'`    | Propriedade usada como valor                 |
| `optionDisabled` | `String`                              | `'disabled'` | Propriedade usada para desabilitar uma opção |
| `orientation`    | `'vertical' \| 'horizontal'`          | `'vertical'` | Direção visual das opções                    |

## Eventos

| Evento              | Payload           |
| ------------------- | ----------------- |
| `update:modelValue` | valor selecionado |
| `focus`             | `FocusEvent`      |
| `blur`              | `FocusEvent`      |

## Preservação de tipos

O grupo preserva o tipo original da opção porque delega a seleção ao `AppRadio`.

São suportados:

```text
String
Number
Boolean
```

Exemplo:

```js
[
  { label: "Baixa", value: 1 },
  { label: "Alta", value: 2 },
];
```

resulta em:

```js
2;
```

e não:

```js
"2";
```

## Opções primitivas

Também são aceitas:

```js
["Aberto", "Fechado"];
```

Nesse caso, o próprio valor é utilizado como label e value.

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
<RadioGroup
  v-model="role"
  id="role"
  name="role"
  label="Perfil"
  :options="roles"
  option-label="name"
  option-value="id"
  option-disabled="blocked"
/>
```

## Disabled global

```vue
<RadioGroup v-model="value" name="status" :options="options" disabled />
```

Todas as opções ficam indisponíveis.

## Disabled por opção

```js
[
  {
    label: "Usuário",
    value: "user",
  },
  {
    label: "Administrador",
    value: "admin",
    disabled: true,
  },
];
```

Apenas a opção marcada fica desabilitada.

## Required

```vue
<RadioGroup
  v-model="type"
  id="type"
  name="type"
  label="Tipo"
  :options="options"
  required
/>
```

O indicador obrigatório aparece apenas uma vez no `legend`.

O grupo também expõe:

```text
aria-required="true"
```

## Hint

```vue
<RadioGroup
  v-model="category"
  id="category"
  name="category"
  label="Categoria"
  hint="Selecione a categoria aplicável."
  :options="options"
/>
```

## Erro

```vue
<RadioGroup
  v-model="category"
  id="category"
  name="category"
  label="Categoria"
  error="Selecione uma opção."
  :options="options"
/>
```

Quando existe erro:

- o hint é ocultado;
- o erro é exibido;
- `aria-describedby` referencia a mensagem;
- `aria-invalid="true"` é aplicado ao `fieldset`.

## IDs gerados

Se:

```text
id="gender"
```

os radios recebem:

```text
gender-option-0
gender-option-1
gender-option-2
```

Isso elimina a necessidade de gerar manualmente um ID para cada opção.

## Orientação

### Vertical

Padrão:

```vue
<RadioGroup orientation="vertical" />
```

### Horizontal

```vue
<RadioGroup orientation="horizontal" />
```

A orientação afeta apenas a apresentação visual, não a semântica nem o valor selecionado.

## Acessibilidade

O componente utiliza:

```text
fieldset
legend
role="radiogroup"
aria-required
aria-describedby
aria-invalid
```

e cada opção continua sendo um `<input type="radio">` nativo através de `AppRadio`.

## Boas práticas

Use `RadioGroup` quando várias opções fizerem parte da mesma decisão.

Prefira:

```vue
<RadioGroup v-model="gender" name="gender" :options="genderOptions" />
```

em vez de repetir manualmente:

```vue
<AppRadio v-model="gender" name="gender" ... />
<AppRadio v-model="gender" name="gender" ... />
<AppRadio v-model="gender" name="gender" ... />
```

Isso centraliza o contrato do grupo e reduz duplicação.

## AppRadio ou RadioGroup?

Use `AppRadio` para uma opção isolada ou quando a composição precisar ser totalmente manual.

Use `RadioGroup` paro grupos normais de opções mutuamente exclusivas.

## Playground

Consulte:

```text
Playground → Forms → RadioGroup
```

Arquivo:

```text
src/views/playground/forms/RadioGroupPlayground.vue
```

## Testes

Os testes estão em:

```text
tests/components/forms/groups/RadioGroup.spec.js
```

Cobertura atual:

```text
fieldset
legend
opções
name
ids
modelValue
string
number
boolean
opções primitivas
optionLabel
optionValue
optionDisabled
disabled global
disabled individual
required
hint
error
aria-required
aria-describedby
aria-invalid
orientação
focus
blur
```
