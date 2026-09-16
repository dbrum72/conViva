# AppRadio

Componente de seleção exclusiva para formulários.

O `AppRadio` encapsula `RadioControl` e adiciona:

- label;
- hint;
- mensagem de erro;
- estado obrigatório;
- estado desabilitado;
- acessibilidade;
- preservação do tipo do valor;
- integração com o Design System.

## Importação

```js
import { AppRadio } from "@/components/forms";
```

## Uso básico

Radios pertencentes ao mesmo grupo devem compartilhar o mesmo `v-model` e o mesmo `name`.

```vue
<script setup>
import { ref } from "vue";

import { AppRadio } from "@/components/forms";

const status = ref("active");
</script>

<template>
  <AppRadio
    v-model="status"
    id="status-active"
    name="status"
    label="Ativo"
    value="active"
  />

  <AppRadio
    v-model="status"
    id="status-inactive"
    name="status"
    label="Inativo"
    value="inactive"
  />
</template>
```

O valor de `status` será:

```js
"active";
```

ou:

```js
"inactive";
```

## Valores numéricos

O componente preserva o tipo definido em `value`.

```vue
<AppRadio
  v-model="priority"
  id="priority-low"
  name="priority"
  label="Baixa"
  :value="1"
/>

<AppRadio
  v-model="priority"
  id="priority-high"
  name="priority"
  label="Alta"
  :value="2"
/>
```

Nesse caso, `priority` recebe um `Number`, e não uma representação textual do valor.

## Valores booleanos

Valores booleanos também são preservados:

```vue
<AppRadio
  v-model="enabled"
  id="enabled-yes"
  name="enabled"
  label="Sim"
  :value="true"
/>

<AppRadio
  v-model="enabled"
  id="enabled-no"
  name="enabled"
  label="Não"
  :value="false"
/>
```

## Com hint

```vue
<AppRadio
  v-model="deliveryType"
  id="delivery-express"
  name="delivery"
  label="Entrega expressa"
  value="express"
  hint="Prazo estimado de até dois dias úteis."
/>
```

Quando não existe erro, o `hint` é associado ao controle por `aria-describedby`.

## Com erro

```vue
<AppRadio
  v-model="selection"
  id="selection-required"
  name="selection"
  label="Opção obrigatória"
  value="selected"
  error="Escolha uma opção."
  required
/>
```

Quando `error` possui conteúdo:

- o hint deixa de ser exibido;
- a mensagem de erro é exibida;
- `aria-invalid="true"` é aplicado ao radio;
- `aria-describedby` referencia a mensagem de erro;
- a classe `app-radio--invalid` é aplicada ao componente.

## Obrigatório

```vue
<AppRadio
  v-model="accepted"
  id="accepted"
  name="acceptance"
  label="Concordo com esta opção"
  value="accepted"
  required
/>
```

O componente exibe `*` junto à label e encaminha `required` ao controle nativo.

## Desabilitado

```vue
<AppRadio
  v-model="selection"
  id="unavailable"
  name="selection"
  label="Opção indisponível"
  value="unavailable"
  disabled
/>
```

## Props

| Prop         | Tipo                                  | Padrão      | Descrição                         |
| ------------ | ------------------------------------- | ----------- | --------------------------------- |
| `modelValue` | `String \| Number \| Boolean \| null` | `null`      | Valor atualmente selecionado      |
| `value`      | `String \| Number \| Boolean`         | obrigatório | Valor representado por esta opção |
| `id`         | `String`                              | `undefined` | Identificador do controle         |
| `name`       | `String`                              | `undefined` | Nome do grupo de radios           |
| `label`      | `String`                              | `''`        | Texto apresentado ao usuário      |
| `hint`       | `String`                              | `''`        | Texto auxiliar                    |
| `error`      | `String`                              | `''`        | Mensagem de erro                  |
| `disabled`   | `Boolean`                             | `false`     | Desabilita a opção                |
| `required`   | `Boolean`                             | `false`     | Marca a seleção como obrigatória  |
| `autofocus`  | `Boolean`                             | `false`     | Solicita foco automático          |

## Eventos

| Evento              | Payload          | Descrição                             |
| ------------------- | ---------------- | ------------------------------------- |
| `update:modelValue` | valor de `value` | Emitido quando a opção é selecionada  |
| `focus`             | `FocusEvent`     | Emitido quando o controle recebe foco |
| `blur`              | `FocusEvent`     | Emitido quando o controle perde foco  |

## Comparação e preservação de tipos

A seleção é determinada por comparação com `Object.is()`:

```js
Object.is(modelValue, value);
```

Consequentemente:

```js
1 !== "1";
true !== 1;
false !== 0;
```

O componente também emite diretamente o valor recebido pela prop `value`, em vez de utilizar `event.target.value`.

Isso evita a conversão automática de valores numéricos e booleanos para `String` pelo DOM.

## Acessibilidade

O `AppRadio` implementa:

- `<input type="radio">` nativo;
- associação entre `label` e `id`;
- agrupamento nativo através de `name`;
- `required`;
- `disabled`;
- `aria-invalid`;
- `aria-describedby`;
- associação automática do hint ou erro ao controle.

Sempre forneça um `id` único para cada opção.

Radios pertencentes ao mesmo grupo devem utilizar o mesmo `name`.

## Boas práticas

Use `AppRadio` quando o usuário puder selecionar **exatamente uma opção entre alternativas mutuamente exclusivas**.

Exemplos adequados:

- status ativo ou inativo;
- modalidade de entrega;
- prioridade;
- tipo de pessoa;
- opção de pagamento.

Para uma escolha booleana independente, prefira `AppCheckbox` ou `AppSwitch`.

## Limitações

`AppRadio` representa uma opção individual.

A responsabilidade de:

- organizar várias opções;
- apresentar uma label para o grupo;
- compartilhar validação;
- controlar mensagens de erro do grupo;

deverá pertencer ao futuro `RadioGroup`.

Até a implementação desse componente, radios relacionados devem compartilhar manualmente:

```text
v-model
name
```

## Playground

Consulte:

```text
Playground → Forms → AppRadio
```
