# AppPassword

Variante semântica de entrada para senhas, baseada em `AppInput`.

`AppPassword` adiciona comportamento de exibição/ocultação da senha, mantendo a infraestrutura visual, de acessibilidade e de eventos do campo base.

## Responsabilidade

O componente é responsável por:

- renderizar o campo como `password` por padrão;
- alternar entre `password` e `text`;
- exibir um botão de visibilidade por padrão;
- permitir ocultar o botão com `showToggle`;
- utilizar labels acessíveis configuráveis;
- respeitar o estado `disabled`;
- preservar slots `prepend` e `append`;
- retransmitir `v-model`, `focus` e `blur`.

## Arquitetura

Fluxo principal:

```text
AppPassword
    ↓
AppInput
    ↓
InputIconButton
    ↓
InputIcon
    ↓
AppIcon
```

O componente reutiliza `AppInput` para toda a estrutura de campo e adiciona apenas o comportamento específico de senha.

## Importação

```js
import { AppPassword } from "@/components/forms";
```

## Uso básico

```vue
<script setup>
import { ref } from "vue";

import { AppPassword } from "@/components/forms";

const password = ref("");
</script>

<template>
  <AppPassword v-model="password" id="password" name="password" label="Senha" />
</template>
```

## Visibilidade

O estado interno começa como:

```js
const isVisible = ref(false);
```

Por padrão, o tipo efetivo do input é:

```text
password
```

Quando o usuário alterna a visibilidade:

```text
password → text
```

e novamente:

```text
text → password
```

A lógica é:

```js
const inputType = computed(() => (isVisible.value ? "text" : "password"));
```

## Botão de alternância

Por padrão:

```js
showToggle = true;
```

Logo, o componente renderiza um botão no slot `append`.

Exemplo:

```vue
<AppPassword v-model="password" label="Senha" />
```

Para ocultar o botão:

```vue
<AppPassword v-model="password" label="Senha" :show-toggle="false" />
```

## Labels acessíveis do botão

O texto do botão muda conforme o estado atual.

Props:

```text
visibleLabel
hiddenLabel
```

Padrões:

```text
visibleLabel → "Ocultar senha"
hiddenLabel  → "Mostrar senha"
```

A regra é:

```js
const toggleLabel = computed(() =>
  isVisible.value ? props.visibleLabel : props.hiddenLabel,
);
```

Assim:

```text
senha oculta  → aria-label="Mostrar senha"
senha visível → aria-label="Ocultar senha"
```

## Personalização dos labels

```vue
<AppPassword
  v-model="password"
  hidden-label="Exibir senha"
  visible-label="Esconder senha"
/>
```

Esses textos são utilizados no `aria-label` do botão de alternância.

## Ícone

O botão utiliza:

```text
eye
```

quando a senha está oculta.

E:

```text
eye-off
```

quando a senha está visível.

A renderização é:

```vue
<AppIcon :name="isVisible ? 'eye-off' : 'eye'" :size="18" />
```

## Disabled

Quando `disabled` é verdadeiro, o botão de visibilidade também recebe:

```html
disabled
```

Além disso, a função:

```js
toggleVisibility();
```

protege explicitamente contra alterações:

```js
if (props.disabled) {
  return;
}
```

Portanto, mesmo que o handler seja chamado diretamente, o estado de visibilidade não muda.

## Props

`AppPassword` reutiliza `appInputProps` e adiciona:

| Prop           | Tipo      | Padrão            | Descrição                                   |
| -------------- | --------- | ----------------- | ------------------------------------------- |
| `showToggle`   | `Boolean` | `true`            | Exibe o botão de mostrar/ocultar senha      |
| `visibleLabel` | `String`  | `'Ocultar senha'` | Label acessível quando a senha está visível |
| `hiddenLabel`  | `String`  | `'Mostrar senha'` | Label acessível quando a senha está oculta  |

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

e demais props disponíveis no componente base.

## Encaminhamento de props

Antes de encaminhar as props para `AppInput`, o componente remove:

```js
showToggle;
visibleLabel;
hiddenLabel;
type;
```

A lógica é:

```js
const { showToggle, visibleLabel, hiddenLabel, type, ...appInputProps } = props;
```

O restante é fornecido através de:

```vue
v-bind="inputProps"
```

O tipo efetivo é controlado separadamente por:

```vue
:type="inputType"
```

Isso impede que uma prop externa de `type` sobrescreva a lógica de visibilidade.

## v-model

O componente retransmite:

```text
update:modelValue
```

emitido por `AppInput`.

Uso:

```vue
<AppPassword v-model="password" />
```

O valor permanece textual.

## Eventos

| Evento              | Payload       | Descrição                          |
| ------------------- | ------------- | ---------------------------------- |
| `update:modelValue` | valor textual | Atualiza o `v-model`               |
| `focus`             | `FocusEvent`  | Emitido quando o campo recebe foco |
| `blur`              | `FocusEvent`  | Emitido quando o campo perde foco  |

## Slots

### prepend

O slot `prepend` é preservado.

```vue
<AppPassword v-model="password">
  <template #prepend>
    ...
  </template>
</AppPassword>
```

### append

O slot `append` também é preservado.

Quando fornecido, ele substitui o conteúdo padrão do slot, incluindo o botão de visibilidade.

Exemplo:

```vue
<AppPassword v-model="password">
  <template #append>
    Conteúdo customizado
  </template>
</AppPassword>
```

Portanto, o slot customizado tem precedência sobre o toggle padrão.

## Acessibilidade

O botão de alternância utiliza `InputIconButton` com:

```text
aria-label
```

dinâmico.

Isso garante que o estado da ação seja compreensível por tecnologias assistivas.

Além disso, o componente herda de `AppInput` suporte a:

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

O ícone é apenas decorativo em termos de significado da ação; a informação acessível vem do `aria-label`.

## Autocomplete

`AppPassword` não redefine `autocomplete` em sua configuração própria.

Logo, o valor efetivo depende do que for fornecido através das props herdadas de `AppInput`.

Exemplo recomendado para senha atual:

```vue
<AppPassword autocomplete="current-password" />
```

Para criação de nova senha:

```vue
<AppPassword autocomplete="new-password" />
```

## Boas práticas

Use `AppPassword` sempre que o campo representar uma senha.

Prefira:

```vue
<AppPassword v-model="password" autocomplete="current-password" />
```

em vez de configurar manualmente:

```vue
<AppInput type="password" />
```

A variante centraliza:

- comportamento de visibilidade;
- acessibilidade do toggle;
- ícones;
- consistência visual.

## Segurança

O botão de visibilidade altera apenas a apresentação do input entre:

```text
password
text
```

Ele não modifica o valor armazenado no `v-model`.

Também não implementa:

- criptografia;
- hashing;
- política de senha;
- validação de força;
- proteção de armazenamento.

Essas responsabilidades pertencem às camadas apropriadas da aplicação e do backend.

## Estilos

O arquivo `style.css` contém estilos para uma classe histórica:

```text
app-password__toggle
```

A implementação atual utiliza `InputIconButton` para o toggle.

Portanto, a apresentação principal do botão é fornecida pela infraestrutura interna de ícones/botões.

Esse ponto pode ser revisado futuramente em uma etapa de limpeza de CSS, caso a classe não seja mais utilizada em nenhum lugar.

## Playground

Consulte:

```text
Playground → Forms → AppPassword
```

Arquivo:

```text
src/views/playground/forms/PasswordPlayground.vue
```
