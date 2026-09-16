# AppPhone

Variante semântica de entrada para números de telefone.

`AppPhone` reutiliza a infraestrutura compartilhada de campos de entrada do Design System e configura automaticamente o controle para entrada telefônica.

## Responsabilidade

O componente é responsável por:

- utilizar `type="tel"`;
- utilizar `autocomplete="tel"`;
- utilizar `inputmode="tel"`;
- exibir o ícone de telefone por padrão;
- permitir ocultar o ícone através de `showIcon`;
- encaminhar as demais props para a infraestrutura compartilhada;
- retransmitir `v-model`, `focus` e `blur`;
- preservar os slots `prepend` e `append`.

O componente **não implementa máscara, parsing ou normalização telefônica**.

## Arquitetura

Fluxo principal:

```text
AppPhone
    ↓
InputVariant
    ↓
infraestrutura compartilhada de AppInput
```

A configuração específica da variante é fornecida por `variantConfig`.

## Importação

```js
import { AppPhone } from "@/components/forms";
```

## Uso básico

```vue
<script setup>
import { ref } from "vue";

import { AppPhone } from "@/components/forms";

const phone = ref("");
</script>

<template>
  <AppPhone
    v-model="phone"
    id="phone"
    name="phone"
    label="Telefone"
    placeholder="(00) 00000-0000"
  />
</template>
```

## Configuração semântica

Internamente, a variante utiliza:

```js
const variantConfig = computed(() => ({
  type: "tel",
  autocomplete: "tel",
  inputmode: "tel",
  icon: "phone",
  iconSize: 18,
  showIcon: props.showIcon,
}));
```

Portanto, a configuração efetiva é:

```text
type         → tel
autocomplete → tel
inputmode    → tel
icon         → phone
iconSize     → 18
showIcon     → true por padrão
```

## type

A variante utiliza:

```html
type="tel"
```

Isso fornece semântica apropriada para entradas telefônicas.

## autocomplete

O padrão é:

```html
autocomplete="tel"
```

Isso permite que navegadores reconheçam o propósito do campo e ofereçam preenchimento adequado quando disponível.

## inputmode

A variante utiliza:

```html
inputmode="tel"
```

Em dispositivos móveis, isso pode sugerir um teclado otimizado para números telefônicos.

## Ícone

Por padrão:

```js
showIcon = true;
```

A configuração utiliza:

```js
icon: "phone";
iconSize: 18;
```

Para ocultar o ícone:

```vue
<AppPhone v-model="phone" :show-icon="false" />
```

## Props

`AppPhone` reutiliza `appInputProps` e especializa:

| Prop           | Tipo      | Padrão  | Descrição                    |
| -------------- | --------- | ------- | ---------------------------- |
| `type`         | `String`  | `'tel'` | Tipo declarado pela variante |
| `autocomplete` | `String`  | `'tel'` | Semântica de autocomplete    |
| `inputmode`    | `String`  | `'tel'` | Modo de entrada sugerido     |
| `showIcon`     | `Boolean` | `true`  | Controla a exibição do ícone |

Também herda as demais props públicas de `AppInput`, incluindo:

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

e demais propriedades do componente base.

## Encaminhamento de props

Antes de encaminhar as props para `InputVariant`, o componente remove:

```js
modelValue;
showIcon;
type;
autocomplete;
inputmode;
```

A implementação é:

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

As props restantes são enviadas como:

```vue
:input-props="inputProps"
```

As configurações específicas da variante são fornecidas separadamente:

```vue
:config="variantConfig"
```

Essa separação mantém o contrato da variante isolado das props genéricas do campo.

## v-model

O componente retransmite:

```text
update:modelValue
```

emitido por `InputVariant`.

Uso:

```vue
<AppPhone v-model="phone" />
```

O valor permanece textual.

## Eventos

| Evento              | Payload       | Descrição                             |
| ------------------- | ------------- | ------------------------------------- |
| `update:modelValue` | valor textual | Atualiza o `v-model`                  |
| `focus`             | `FocusEvent`  | Emitido quando o controle recebe foco |
| `blur`              | `FocusEvent`  | Emitido quando o controle perde foco  |

## Slots

### prepend

```vue
<AppPhone v-model="phone">
  <template #prepend>
    +55
  </template>
</AppPhone>
```

### append

```vue
<AppPhone v-model="phone">
  <template #append>
    ...
  </template>
</AppPhone>
```

Os dois slots são encaminhados para `InputVariant`.

## Hint

```vue
<AppPhone
  v-model="phone"
  id="phone"
  label="Telefone"
  hint="Informe um número com DDD."
/>
```

## Erro

```vue
<AppPhone
  v-model="phone"
  id="phone"
  label="Telefone"
  error="Informe um telefone válido."
/>
```

A apresentação do erro e os atributos ARIA são tratados pela infraestrutura compartilhada.

## Required

```vue
<AppPhone v-model="phone" id="phone" name="phone" label="Telefone" required />
```

## Disabled

```vue
<AppPhone v-model="phone" id="phone" label="Telefone" disabled />
```

## Readonly

```vue
<AppPhone v-model="phone" id="phone" label="Telefone" readonly />
```

## Máscara e normalização

A implementação atual **não aplica máscara telefônica**.

Isso significa que `AppPhone` não:

- insere parênteses;
- insere hífen;
- remove caracteres automaticamente;
- converte o valor para somente dígitos;
- valida DDD;
- valida quantidade de dígitos;
- diferencia telefone fixo e celular.

Exemplo:

```text
entrada: (53) 99999-9999
modelValue: "(53) 99999-9999"
```

ou:

```text
entrada: 53999999999
modelValue: "53999999999"
```

O valor permanece conforme digitado pelo usuário.

Se a aplicação precisar de máscara ou normalização, essa responsabilidade deve ser implementada em uma camada específica, sem ser presumida pelo componente atual.

## Validação

`type="tel"` não fornece validação estrutural completa de telefone.

Regras como:

```text
DDD obrigatório
quantidade mínima de dígitos
formato nacional
formato internacional
E.164
```

devem pertencer à camada de validação apropriada.

## Acessibilidade

A variante reutiliza a infraestrutura de `AppInput`, incluindo:

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

```text
type="tel"
autocomplete="tel"
inputmode="tel"
```

fornecem semântica adequada ao propósito do campo.

O ícone é complementar e não substitui a label textual.

## Estilos

`AppPhone` não possui estilos próprios.

Seu `style.css` está vazio, portanto a apresentação é herdada integralmente da infraestrutura compartilhada.

Isso evita duplicação visual entre variantes.

## Boas práticas

Use `AppPhone` sempre que o valor representar semanticamente um telefone.

Prefira:

```vue
<AppPhone v-model="phone" id="phone" name="phone" label="Telefone" />
```

em vez de configurar manualmente:

```vue
<AppInput type="tel" autocomplete="tel" inputmode="tel" />
```

A variante centraliza a semântica e mantém consistência entre formulários.

## Playground

Consulte:

```text
Playground → Forms → AppPhone
```

Arquivo:

```text
src/views/playground/forms/PhonePlayground.vue
```
