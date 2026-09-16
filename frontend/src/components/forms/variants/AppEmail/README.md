# AppEmail

Variante semântica de entrada para endereços de e-mail.

`AppEmail` reutiliza a infraestrutura compartilhada de campos de entrada do Design System e configura automaticamente o controle para entrada de e-mail.

## Responsabilidade

O componente é responsável por:

- especializar o campo para endereços de e-mail;
- utilizar `type="email"`;
- utilizar `autocomplete="email"`;
- utilizar `inputmode="email"`;
- exibir o ícone de e-mail por padrão;
- permitir ocultar o ícone através de `showIcon`;
- encaminhar as demais props para a infraestrutura compartilhada;
- retransmitir `v-model`, `focus` e `blur`;
- preservar os slots `prepend` e `append`.

O componente não implementa validação de domínio específica para endereços de e-mail.

## Arquitetura

Fluxo principal:

```text
AppEmail
    ↓
InputVariant
    ↓
infraestrutura compartilhada de AppInput
```

A configuração específica da variante é fornecida através de `variantConfig`.

## Importação

```js
import { AppEmail } from "@/components/forms";
```

## Uso básico

```vue
<script setup>
import { ref } from "vue";

import { AppEmail } from "@/components/forms";

const email = ref("");
</script>

<template>
  <AppEmail
    v-model="email"
    id="email"
    name="email"
    label="E-mail"
    placeholder="nome@exemplo.com"
  />
</template>
```

## Configuração semântica

Internamente, `AppEmail` define:

```js
const variantConfig = computed(() => ({
  type: "email",
  autocomplete: "email",
  inputmode: "email",
  icon: "email",
  iconSize: 18,
  showIcon: props.showIcon,
}));
```

Portanto, a variante possui como configuração efetiva:

```text
type         → email
autocomplete → email
inputmode    → email
icon         → email
iconSize     → 18
showIcon     → true por padrão
```

## type

A variante utiliza semanticamente:

```html
type="email"
```

Isso permite ao navegador aplicar o comportamento nativo associado a campos de e-mail.

## autocomplete

A configuração padrão é:

```html
autocomplete="email"
```

Isso permite que navegadores e gerenciadores de preenchimento reconheçam semanticamente o campo.

## inputmode

A variante utiliza:

```html
inputmode="email"
```

Em dispositivos compatíveis, isso pode sugerir um teclado otimizado para digitação de endereços de e-mail.

## Ícone

Por padrão:

```js
showIcon = true;
```

e a configuração utiliza:

```js
icon: "email";
iconSize: 18;
```

Para ocultar o ícone:

```vue
<AppEmail v-model="email" id="email" label="E-mail" :show-icon="false" />
```

## Props

`AppEmail` reutiliza o contrato de `AppInput` e adiciona/especializa:

| Prop           | Tipo      | Padrão    | Descrição                    |
| -------------- | --------- | --------- | ---------------------------- |
| `type`         | `String`  | `'email'` | Tipo declarado pela variante |
| `autocomplete` | `String`  | `'email'` | Semântica de autocomplete    |
| `inputmode`    | `String`  | `'email'` | Modo de entrada sugerido     |
| `showIcon`     | `Boolean` | `true`    | Controla a exibição do ícone |

As demais props são herdadas de `appInputProps`, incluindo o contrato de campo e de entrada.

Entre elas:

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
maxlength
minlength
```

e demais propriedades definidas por `AppInput`.

## Encaminhamento de props

Antes de encaminhar as props para `InputVariant`, o componente remove:

```js
modelValue;
showIcon;
type;
autocomplete;
inputmode;
```

através de:

```js
const {
  modelValue,
  showIcon,
  type,
  autocomplete,
  inputmode,
  ...forwardedProps
} = props;
```

O resultado é fornecido como:

```vue
:input-props="inputProps"
```

As configurações específicas da variante são fornecidas separadamente através de:

```vue
:config="variantConfig"
```

Essa separação evita que a configuração semântica da variante seja misturada com as props genéricas encaminhadas ao campo.

## v-model

O componente retransmite:

```text
update:modelValue
```

emitido por `InputVariant`.

Uso:

```vue
<AppEmail v-model="email" />
```

O valor editável permanece textual.

## Eventos

| Evento              | Payload                          | Descrição                             |
| ------------------- | -------------------------------- | ------------------------------------- |
| `update:modelValue` | valor emitido por `InputVariant` | Atualiza o `v-model`                  |
| `focus`             | `FocusEvent`                     | Emitido quando o controle recebe foco |
| `blur`              | `FocusEvent`                     | Emitido quando o controle perde foco  |

## Slots

### prepend

O slot `prepend` é encaminhado para `InputVariant`.

```vue
<AppEmail v-model="email" label="E-mail">
  <template #prepend>
    ...
  </template>
</AppEmail>
```

### append

O slot `append` também é preservado.

```vue
<AppEmail v-model="email" label="E-mail">
  <template #append>
    ...
  </template>
</AppEmail>
```

## Hint

```vue
<AppEmail
  v-model="email"
  id="email"
  label="E-mail"
  hint="Utilize seu endereço de e-mail profissional."
/>
```

A infraestrutura compartilhada do campo é responsável pela associação acessível do hint.

## Erro

```vue
<AppEmail
  v-model="email"
  id="email"
  label="E-mail"
  error="Informe um endereço de e-mail válido."
/>
```

A apresentação e os atributos ARIA relacionados ao erro são tratados pela infraestrutura compartilhada do formulário.

## Required

```vue
<AppEmail v-model="email" id="email" name="email" label="E-mail" required />
```

## Disabled

```vue
<AppEmail v-model="email" id="email" label="E-mail" disabled />
```

## Readonly

```vue
<AppEmail v-model="email" id="email" label="E-mail" readonly />
```

## Validação

`AppEmail` fornece a semântica nativa:

```html
type="email"
```

mas não deve ser confundido com uma camada completa de validação de dados.

Regras como:

```text
e-mail obrigatório
domínios permitidos
unicidade
confirmação de endereço
regras específicas da aplicação
```

devem permanecer na camada de validação apropriada.

## Acessibilidade

A variante reutiliza a infraestrutura compartilhada dos campos para suportar:

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

Além disso, a configuração:

```text
type="email"
autocomplete="email"
inputmode="email"
```

fornece semântica adequada ao propósito do controle.

O ícone é complementar à identificação textual do campo e não deve substituir a label.

## Estilos

`AppEmail` não possui apresentação visual própria.

Seu `style.css` registra explicitamente que a apresentação é herdada de:

```text
AppInput
InputIcon
```

Isso evita duplicação de CSS entre variantes.

## Boas práticas

Use `AppEmail` para entradas cujo valor representa semanticamente um endereço de e-mail.

Prefira:

```vue
<AppEmail v-model="email" id="email" name="email" label="E-mail" />
```

em vez de configurar manualmente um `AppInput`:

```vue
<AppInput type="email" autocomplete="email" inputmode="email" />
```

A variante centraliza o contrato e mantém consistência entre os formulários da aplicação.

## Playground

Consulte:

```text
Playground → Forms → AppEmail
```

Arquivo:

```text
src/views/playground/forms/EmailPlayground.vue
```
