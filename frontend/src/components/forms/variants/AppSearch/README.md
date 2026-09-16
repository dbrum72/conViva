# AppSearch

Variante de entrada para pesquisa, baseada em `AppInput`.

`AppSearch` adiciona semântica visual de busca, ícone de pesquisa, botão opcional de limpeza e suporte à tecla `Escape`, mantendo a infraestrutura compartilhada de campos do Design System.

## Responsabilidade

O componente é responsável por:

- reutilizar `AppInput`;
- exibir um ícone de pesquisa;
- permitir limpar rapidamente o valor;
- exibir o botão de limpeza somente quando existe conteúdo;
- permitir desabilitar a limpeza através de `clearable`;
- limpar a pesquisa com a tecla `Escape`;
- respeitar os estados `disabled` e `readonly`;
- fornecer labels acessíveis para os controles visuais;
- retransmitir `v-model`, `focus` e `blur`.

## Arquitetura

Fluxo principal:

```text
AppSearch
    ↓
AppInput
    ↓
InputIcon
InputIconButton
```

O componente reutiliza a infraestrutura visual e semântica de `AppInput` e adiciona apenas o comportamento específico de pesquisa.

## Importação

```js
import { AppSearch } from "@/components/forms";
```

## Uso básico

```vue
<script setup>
import { ref } from "vue";

import { AppSearch } from "@/components/forms";

const search = ref("");
</script>

<template>
  <AppSearch
    v-model="search"
    id="search"
    name="search"
    label="Pesquisar"
    placeholder="Digite para pesquisar..."
  />
</template>
```

## Contrato do v-model

O componente retransmite diretamente o valor recebido de `AppInput`.

O valor público é textual:

```ts
string;
```

Exemplo:

```text
entrada: processo civil
modelValue: "processo civil"
```

## Atualização do valor

Alterações provenientes do input são retransmitidas por:

```js
function updateValue(value) {
  emit("update:modelValue", value);
}
```

Portanto, `AppSearch` não realiza parsing ou transformação do texto pesquisado.

## Detecção de conteúdo

A existência de valor é calculada através de:

```js
const hasValue = computed(() => {
  if (props.modelValue === null || props.modelValue === undefined) {
    return false;
  }

  return String(props.modelValue).length > 0;
});
```

Assim:

```js
null;
undefined;
("");
```

são considerados estados sem conteúdo.

Valores com representação textual não vazia são considerados preenchidos.

## Botão limpar

Por padrão:

```js
clearable = true;
```

Quando existe conteúdo, o componente renderiza um botão de limpeza no slot `append`.

O botão utiliza:

```text
InputIconButton
InputIcon
AppIcon
```

com o ícone:

```text
close
```

e tamanho:

```text
18
```

## clearable

Para ocultar o botão de limpeza:

```vue
<AppSearch v-model="search" :clearable="false" />
```

Quando `clearable` é `false`, o botão não é renderizado mesmo quando existe conteúdo.

## Limpeza

Ao limpar, o componente emite:

```js
"";
```

através de:

```js
emit("update:modelValue", "");
```

Portanto:

```text
antes: "cuidador"
depois: ""
```

## Tecla Escape

O componente trata:

```vue
@keydown.esc="clear"
```

Assim, pressionar `Escape` limpa a pesquisa quando a alteração é permitida.

Esse comportamento oferece uma forma rápida de redefinir o campo sem utilizar o mouse.

## Disabled

Quando:

```js
disabled = true;
```

a função `clear()` retorna imediatamente:

```js
if (props.disabled || props.readonly) {
  return;
}
```

Portanto, o valor não pode ser limpo pelo botão nem pelo comportamento associado ao `Escape`.

## Readonly

O mesmo comportamento se aplica a:

```js
readonly = true;
```

O campo pode permanecer visível, mas a limpeza programada pelo componente é bloqueada.

## Labels acessíveis

O componente possui duas props específicas:

```text
clearLabel
searchLabel
```

### clearLabel

Padrão:

```text
Limpar pesquisa
```

É utilizado como label acessível do botão de limpeza.

Exemplo:

```vue
<AppSearch clear-label="Remover termo pesquisado" />
```

### searchLabel

Padrão:

```text
Pesquisar
```

É utilizado para fornecer semântica ao elemento visual associado à pesquisa.

Exemplo:

```vue
<AppSearch search-label="Buscar processos" />
```

## Props próprias

`AppSearch` reutiliza `appInputProps` e adiciona:

| Prop          | Tipo      | Padrão              | Descrição                                |
| ------------- | --------- | ------------------- | ---------------------------------------- |
| `clearable`   | `Boolean` | `true`              | Permite exibir o botão de limpeza        |
| `clearLabel`  | `String`  | `'Limpar pesquisa'` | Label acessível do botão de limpar       |
| `searchLabel` | `String`  | `'Pesquisar'`       | Label associada à ação/ícone de pesquisa |

Também herda as props públicas de `AppInput`, incluindo:

```text
modelValue
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

e demais propriedades disponíveis no componente base.

## Encaminhamento de props

Antes de encaminhar as props ao `AppInput`, o componente remove:

```js
clearable;
clearLabel;
searchLabel;
```

A implementação é:

```js
const { clearable, clearLabel, searchLabel, ...forwardedProps } = props;
```

As demais propriedades são encaminhadas como:

```vue
v-bind="inputProps"
```

Isso mantém as props exclusivas da variante fora do contrato do input base.

## Eventos

| Evento              | Payload      | Descrição                          |
| ------------------- | ------------ | ---------------------------------- |
| `update:modelValue` | `String`     | Atualiza o texto pesquisado        |
| `focus`             | `FocusEvent` | Emitido quando o campo recebe foco |
| `blur`              | `FocusEvent` | Emitido quando o campo perde foco  |

## Ícone de pesquisa

O componente utiliza a infraestrutura interna:

```text
InputIcon
```

para apresentar a indicação visual de busca no início do campo.

O ícone não substitui uma label textual adequada quando o contexto exige identificação explícita do campo.

## Slot prepend

`AppSearch` utiliza o `prepend` para a indicação visual padrão de pesquisa.

A implementação do componente controla essa região como parte da própria variante.

## Slot append

O `append` é utilizado para o botão de limpeza quando:

```text
clearable = true
```

e:

```text
hasValue = true
```

## Hint

```vue
<AppSearch
  v-model="search"
  id="search"
  label="Pesquisar"
  hint="Pesquise por nome ou número do processo."
/>
```

A infraestrutura de `AppInput` é responsável pela associação do hint ao controle.

## Erro

```vue
<AppSearch
  v-model="search"
  id="search"
  label="Pesquisar"
  error="Informe um termo válido."
/>
```

A infraestrutura compartilhada gerencia:

```text
aria-describedby
aria-invalid
```

e a apresentação da mensagem.

## Acessibilidade

`AppSearch` herda de `AppInput`:

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

- o botão de limpeza possui `aria-label`;
- a ação de limpar também pode ser executada por teclado com `Escape`;
- controles desabilitados ou somente leitura não podem ser alterados pela função `clear()`.

## Semântica da pesquisa

`AppSearch` é um campo de entrada voltado à filtragem ou localização de conteúdo.

Ele não implementa por conta própria:

- debounce;
- requisições HTTP;
- busca assíncrona;
- sugestões;
- autocomplete;
- histórico;
- filtros avançados.

Essas responsabilidades pertencem à camada que consome o componente.

Para busca com sugestões selecionáveis, utilize:

```text
AppAutocomplete
```

## Debounce

O componente emite cada atualização do valor imediatamente.

Se uma busca remota precisar evitar requisições em cada tecla, o debounce deve ser aplicado externamente.

Exemplo conceitual:

```text
AppSearch
    ↓
v-model
    ↓
debounce
    ↓
API
```

## Clear versus alteração externa

O método `clear()` apenas emite:

```js
"";
```

O estado definitivo continua pertencendo ao componente pai através de `v-model`.

Isso mantém `AppSearch` como componente controlado.

## Estilos

`AppSearch` não possui estilos próprios.

Sua apresentação é herdada de:

```text
AppInput
InputIcon
InputIconButton
```

Isso mantém a variante focada em comportamento e evita duplicação de CSS.

## Boas práticas

Use `AppSearch` para filtros textuais e campos de pesquisa.

Exemplos:

```text
Pesquisar processos
Pesquisar clientes
Pesquisar documentos
Filtrar usuários
Localizar registros
```

Prefira `AppSearch` a um `AppInput` genérico quando a intenção do campo for explicitamente pesquisar.

## AppSearch ou AppAutocomplete?

Use:

```text
AppSearch
```

quando o usuário estiver digitando um termo para filtrar ou pesquisar conteúdo.

Use:

```text
AppAutocomplete
```

quando o usuário precisar pesquisar **e selecionar um item específico** de uma lista.

Exemplo:

```text
Pesquisar processos        → AppSearch
Selecionar cliente         → AppAutocomplete
```

## Playground

Consulte:

```text
Playground → Forms → AppSearch
```

Arquivo:

```text
src/views/playground/forms/SearchPlayground.vue
```
