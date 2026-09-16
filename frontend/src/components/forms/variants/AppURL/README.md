# AppURL

Variante semântica de entrada para URLs.

`AppURL` reutiliza a infraestrutura compartilhada de campos do Design System através de `InputVariant` e configura automaticamente o controle para entrada de endereços web.

## Responsabilidade

O componente é responsável por:

- utilizar `type="url"`;
- utilizar `autocomplete="url"`;
- utilizar `inputmode="url"`;
- exibir o ícone de link por padrão;
- permitir ocultar o ícone através de `showIcon`;
- encaminhar as demais props para a infraestrutura compartilhada;
- retransmitir `v-model`, `focus` e `blur`;
- preservar os slots `prepend` e `append`.

O componente **não implementa parsing, sanitização, normalização ou validação própria de URLs**.

## Arquitetura

Fluxo principal:

```text
AppURL
    ↓
InputVariant
    ↓
infraestrutura compartilhada de AppInput
```

A configuração específica da variante é fornecida por `variantConfig`.

## Importação

```js
import { AppURL } from "@/components/forms";
```

## Uso básico

```vue
<script setup>
import { ref } from "vue";

import { AppURL } from "@/components/forms";

const website = ref("");
</script>

<template>
  <AppURL
    v-model="website"
    id="website"
    name="website"
    label="Site"
    placeholder="https://exemplo.com.br"
  />
</template>
```

## Configuração semântica

Internamente, a variante utiliza:

```js
const variantConfig = computed(() => ({
  type: "url",
  autocomplete: "url",
  inputmode: "url",
  icon: "link",
  iconSize: 18,
  showIcon: props.showIcon,
}));
```

Portanto, a configuração efetiva é:

```text
type         → url
autocomplete → url
inputmode    → url
icon         → link
iconSize     → 18
showIcon     → true por padrão
```

## type

A variante utiliza:

```html
type="url"
```

Isso fornece ao navegador a semântica nativa correspondente a um endereço web.

## autocomplete

A configuração efetiva é:

```html
autocomplete="url"
```

Isso permite que navegadores reconheçam a finalidade do campo e ofereçam valores previamente conhecidos quando aplicável.

## inputmode

A variante utiliza:

```html
inputmode="url"
```

Em dispositivos compatíveis, isso pode apresentar um teclado virtual mais adequado à digitação de URLs.

## Ícone

Por padrão:

```js
showIcon = true;
```

A configuração utiliza:

```js
icon: "link";
iconSize: 18;
```

Para ocultar o ícone:

```vue
<AppURL v-model="website" :show-icon="false" />
```

## Props

`AppURL` reutiliza `appInputProps` e especializa:

| Prop           | Tipo      | Padrão  | Descrição                    |
| -------------- | --------- | ------- | ---------------------------- |
| `type`         | `String`  | `'url'` | Tipo declarado pela variante |
| `autocomplete` | `String`  | `'url'` | Semântica de autocomplete    |
| `inputmode`    | `String`  | `'url'` | Modo de entrada sugerido     |
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

e demais propriedades disponibilizadas pelo componente base.

## Configuração fixa da variante

Embora `type`, `autocomplete` e `inputmode` façam parte do contrato de props, a implementação atual não os encaminha diretamente.

Eles são removidos de `inputProps`:

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

e a configuração efetivamente utilizada é definida por:

```js
variantConfig;
```

com:

```js
type: "url";
autocomplete: "url";
inputmode: "url";
```

Portanto, na implementação atual, `AppURL` mantém deliberadamente sua semântica de URL independentemente desses valores presentes no contrato herdado.

## Encaminhamento de props

Antes de encaminhar as props para `InputVariant`, o componente remove:

```js
modelValue;
showIcon;
type;
autocomplete;
inputmode;
```

As propriedades restantes são fornecidas através de:

```vue
:input-props="inputProps"
```

Enquanto a configuração específica da variante é fornecida separadamente:

```vue
:config="variantConfig"
```

Essa separação impede que propriedades específicas da variante sejam encaminhadas indevidamente ao input base.

## v-model

O componente retransmite:

```text
update:modelValue
```

emitido por `InputVariant`.

Uso:

```vue
<AppURL v-model="website" />
```

O valor permanece textual.

Exemplo:

```text
entrada:
https://exemplo.com.br/processos

modelValue:
"https://exemplo.com.br/processos"
```

## Eventos

| Evento              | Payload       | Descrição                             |
| ------------------- | ------------- | ------------------------------------- |
| `update:modelValue` | valor textual | Atualiza o `v-model`                  |
| `focus`             | `FocusEvent`  | Emitido quando o controle recebe foco |
| `blur`              | `FocusEvent`  | Emitido quando o controle perde foco  |

## Slots

### prepend

O slot `prepend` é preservado:

```vue
<AppURL v-model="website">
  <template #prepend>
    ...
  </template>
</AppURL>
```

### append

O slot `append` também é preservado:

```vue
<AppURL v-model="website">
  <template #append>
    ...
  </template>
</AppURL>
```

Ambos são encaminhados para `InputVariant`.

## Hint

```vue
<AppURL
  v-model="website"
  id="website"
  label="Site"
  hint="Informe o endereço completo."
/>
```

## Erro

```vue
<AppURL
  v-model="website"
  id="website"
  label="Site"
  error="Informe uma URL válida."
/>
```

A apresentação da mensagem e sua associação com o controle pertencem à infraestrutura compartilhada.

## Required

```vue
<AppURL v-model="website" id="website" name="website" label="Site" required />
```

## Disabled

```vue
<AppURL v-model="website" id="website" label="Site" disabled />
```

## Readonly

```vue
<AppURL v-model="website" id="website" label="Site" readonly />
```

## Normalização

A implementação atual não modifica o texto informado pelo usuário.

Ela não adiciona automaticamente:

```text
https://
```

nem remove:

```text
http://
https://
www.
/
```

Também não converte domínio, protocolo, path ou query string.

Exemplo:

```text
entrada:
exemplo.com.br

modelValue:
"exemplo.com.br"
```

O componente preserva o valor recebido do controle.

## Validação

`AppURL` não possui algoritmo próprio de validação.

A semântica:

```html
type="url"
```

pode fornecer comportamento nativo do navegador quando utilizado em mecanismos de validação HTML, mas o componente não implementa regras adicionais.

Validações específicas, como:

```text
protocolo obrigatório
HTTPS obrigatório
domínios permitidos
domínios bloqueados
URL absoluta
restrição de host
```

devem ser tratadas pela camada de validação apropriada da aplicação.

## Segurança

O fato de um valor ser recebido por `AppURL` não significa que ele seja seguro para utilização direta em:

```text
href
redirecionamentos
iframes
requisições
conteúdo HTML
```

Validação e sanitização de URLs para contextos sensíveis devem ocorrer na camada responsável pelo uso efetivo do valor.

`AppURL` é um componente de entrada, não uma fronteira de segurança.

## Acessibilidade

A variante reutiliza a infraestrutura compartilhada de `AppInput`, incluindo:

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
type="url"
autocomplete="url"
inputmode="url"
```

fornecem semântica apropriada ao propósito do campo.

O ícone `link` é complementar e não substitui a identificação textual do campo.

## Estilos

`AppURL` não possui estilos próprios.

Seu `style.css` declara que a apresentação é herdada de:

```text
InputVariant
AppInput
```

Isso mantém a variante concentrada em semântica e evita duplicação de estilos.

## Boas práticas

Use `AppURL` quando o valor representar um endereço web.

Exemplos:

```text
site institucional
perfil profissional
link de documento
página externa
endereço de referência
```

Prefira:

```vue
<AppURL v-model="website" id="website" label="Site" />
```

em vez de repetir manualmente:

```vue
<AppInput type="url" autocomplete="url" inputmode="url" />
```

A variante centraliza a configuração e mantém consistência entre formulários.

## AppURL ou AppInput?

Use:

```text
AppURL
```

quando o domínio do campo for explicitamente uma URL.

Use:

```text
AppInput
```

quando o conteúdo for texto genérico.

Exemplo:

```text
Site do cliente        → AppURL
Descrição do cliente   → AppInput
```

## Playground

Consulte:

```text
Playground → Forms → AppURL
```

Arquivo:

```text
src/views/playground/forms/UrlPlayground.vue
```
