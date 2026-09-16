# AppAutocomplete

Campo de autocomplete baseado em `BaseField` e `AutocompleteControl`.

O componente fornece:

- pesquisa incremental;
- seleção por mouse;
- navegação por teclado;
- suporte a objetos e valores primitivos;
- preservação do tipo original do valor selecionado;
- controle independente de `modelValue` e `searchValue`;
- integração com `BaseField`;
- acessibilidade baseada no padrão WAI-ARIA Combobox.

---

# Importação

```js
import { AppAutocomplete } from "@/components/forms";
```

---

# Uso básico

```vue
<script setup>
import { ref } from "vue";

import { AppAutocomplete } from "@/components/forms";

const user = ref(null);
const search = ref("");

const options = [
  {
    label: "Administrador",
    value: 10,
  },
  {
    label: "Operador",
    value: 20,
  },
];
</script>

<template>
  <AppAutocomplete
    v-model="user"
    v-model:searchValue="search"
    id="user"
    label="Usuário"
    placeholder="Digite para pesquisar..."
    :options="options"
  />
</template>
```

---

# Contrato do componente

O componente possui **dois modelos independentes**.

## modelValue

Representa o valor efetivamente selecionado.

Tipos suportados:

```ts
string | number | boolean | object | null;
```

Exemplo:

```js
20;
```

---

## searchValue

Representa o texto digitado pelo usuário.

Tipo:

```ts
string;
```

Exemplo:

```js
"Oper";
```

---

# Props

| Prop              | Tipo                                            |                           Padrão | Descrição                                                |
| ----------------- | ----------------------------------------------- | -------------------------------: | -------------------------------------------------------- |
| `modelValue`      | `String \| Number \| Boolean \| Object \| null` |                           `null` | Valor atualmente selecionado.                            |
| `searchValue`     | `String`                                        |                             `''` | Texto digitado pelo usuário.                             |
| `placeholder`     | `String`                                        |                             `''` | Placeholder do campo.                                    |
| `autocomplete`    | `String`                                        |                          `'off'` | Valor do atributo HTML autocomplete.                     |
| `options`         | `Array`                                         |                             `[]` | Lista de opções.                                         |
| `optionLabel`     | `String`                                        |                        `'label'` | Campo utilizado como texto.                              |
| `optionValue`     | `String`                                        |                        `'value'` | Campo utilizado como valor.                              |
| `noResultsText`   | `String`                                        | `'Nenhum resultado encontrado.'` | Texto quando não existem resultados.                     |
| `minSearchLength` | `Number`                                        |                              `0` | Quantidade mínima de caracteres para iniciar a pesquisa. |
| `openOnFocus`     | `Boolean`                                       |                           `true` | Abre automaticamente ao receber foco.                    |
| `clearable`       | `Boolean`                                       |                          `false` | Reservado para futura implementação do botão limpar.     |

Também herda todas as props públicas de campo:

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

---

# Eventos

| Evento               | Payload           |
| -------------------- | ----------------- |
| `update:modelValue`  | valor selecionado |
| `update:searchValue` | texto digitado    |
| `focus`              | FocusEvent        |
| `blur`               | FocusEvent        |
| `open`               | —                 |
| `close`              | —                 |

---

# Formatos aceitos

## Objetos

```js
[
  {
    label: "Administrador",
    value: 10,
  },
  {
    label: "Operador",
    value: 20,
  },
];
```

---

## Objetos personalizados

```js
[
  {
    id: 100,
    name: "Ana",
  },
  {
    id: 200,
    name: "Carlos",
  },
];
```

```vue
<AppAutocomplete option-label="name" option-value="id" />
```

---

## Valores primitivos

```js
["Baixa", "Média", "Alta"];
```

---

# Preservação de tipos

O componente preserva automaticamente o tipo do valor selecionado.

Exemplos:

```js
10;
```

permanece:

```js
number;
```

---

```js
true;
```

permanece:

```js
boolean;
```

---

```js
"admin";
```

permanece:

```js
string;
```

O componente nunca converte automaticamente o valor para `string`.

---

# Pesquisa

A pesquisa é realizada sobre o texto definido em:

```text
optionLabel
```

Não sobre o valor.

Exemplo:

```js
{
    id: 10,
    name: 'Administrador',
}
```

Pesquisa:

```text
adm
```

Resultado:

```text
Administrador
```

---

# minSearchLength

Permite definir o mínimo de caracteres antes de iniciar a pesquisa.

```vue
<AppAutocomplete :min-search-length="2" />
```

---

# openOnFocus

Por padrão:

```vue
openOnFocus=true
```

Ao receber foco, a lista é aberta.

Pode ser desabilitado:

```vue
:open-on-focus="false"
```

---

# Estado vazio

Quando nenhuma opção corresponde ao filtro:

```text
Nenhum resultado encontrado.
```

Pode ser personalizado:

```vue
<AppAutocomplete no-results-text="Nenhum usuário encontrado." />
```

---

# Navegação por teclado

Suportado atualmente:

```text
ArrowDown
ArrowUp
Enter
Escape
```

Comportamento:

| Tecla | Ação           |
| ----- | -------------- |
| ↓     | próxima opção  |
| ↑     | opção anterior |
| Enter | seleciona      |
| Esc   | fecha lista    |

---

# Acessibilidade

O componente segue o padrão WAI-ARIA Combobox.

São utilizados:

```text
role="combobox"

role="listbox"

role="option"
```

Além de:

```text
aria-expanded

aria-controls

aria-activedescendant

aria-selected

aria-describedby

aria-invalid
```

---

# Arquitetura

Fluxo interno:

```text
AppAutocomplete
        ↓
BaseField
        ↓
AutocompleteControl
```

O `AppAutocomplete`:

- integra BaseField;
- encaminha props;
- retransmite eventos.

O `AutocompleteControl`:

- pesquisa;
- renderiza lista;
- controla foco;
- controla teclado;
- controla seleção.

---

# Playground

Disponível em:

```text
src/views/playground/forms/AutocompletePlayground.vue
```

---

# Testes

Testes automatizados:

```text
tests/components/forms/AppAutocomplete.spec.js
```

Cobertura atual:

```text
renderização

combobox

label

placeholder

autocomplete

pesquisa

filtragem

seleção

mouse

teclado

ArrowDown

ArrowUp

Enter

Escape

open

close

focus

blur

v-model

searchValue

opções primitivas

objetos

boolean

number

ARIA

acessibilidade
```

---

# Situação atual

Implementado:

- ✅ pesquisa incremental
- ✅ mouse
- ✅ teclado
- ✅ preservação de tipos
- ✅ objetos
- ✅ primitivos
- ✅ acessibilidade
- ✅ testes

Planejado para versões futuras:

- ⏳ botão limpar (`clearable`)
- ⏳ carregamento assíncrono
- ⏳ virtualização
- ⏳ agrupamento de resultados
- ⏳ múltipla seleção
- ⏳ destaque do trecho pesquisado
