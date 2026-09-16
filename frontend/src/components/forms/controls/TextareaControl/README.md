# TextareaControl

Controle HTML de texto multilinha baseado em `<textarea>`.

`TextareaControl` é um componente interno do Design System. Ele não renderiza label, hint ou mensagem de erro. Essas responsabilidades pertencem aos componentes de campo, principalmente `AppTextarea`.

## Responsabilidade

O componente é responsável por:

- renderizar o elemento `<textarea>`;
- encaminhar atributos nativos;
- emitir alterações de valor;
- emitir eventos de foco e blur;
- consumir o contexto do campo para atributos ARIA;
- manter a aparência visual desacoplada do componente externo.

## Arquitetura

Fluxo típico:

```text
AppTextarea
    ↓
BaseField
    ↓
TextareaControl
```

`TextareaControl` não deve implementar:

- label;
- hint;
- mensagem de erro;
- validação de domínio;
- contador de caracteres;
- regras específicas da aplicação.

Essas responsabilidades pertencem às camadas superiores.

## Uso interno

```vue
<TextareaControl
  v-model="description"
  id="description"
  name="description"
  :rows="4"
/>
```

Na aplicação, prefira utilizar `AppTextarea`.

## Props

| Prop           | Tipo                        | Padrão      | Descrição                         |
| -------------- | --------------------------- | ----------- | --------------------------------- |
| `modelValue`   | `String \| Number`          | `''`        | Conteúdo atual                    |
| `id`           | `String`                    | `undefined` | Identificador do controle         |
| `name`         | `String`                    | `undefined` | Nome do controle                  |
| `placeholder`  | `String`                    | `''`        | Placeholder                       |
| `disabled`     | `Boolean`                   | `false`     | Desabilita o controle             |
| `readonly`     | `Boolean`                   | `false`     | Impede edição                     |
| `required`     | `Boolean`                   | `false`     | Marca como obrigatório            |
| `autofocus`    | `Boolean`                   | `false`     | Solicita foco automático          |
| `autocomplete` | `String`                    | `'off'`     | Configuração de autocomplete      |
| `maxlength`    | `Number`                    | `undefined` | Quantidade máxima de caracteres   |
| `minlength`    | `Number`                    | `undefined` | Quantidade mínima de caracteres   |
| `rows`         | `Number`                    | `4`         | Número inicial de linhas visíveis |
| `cols`         | `Number`                    | `undefined` | Número sugerido de colunas        |
| `wrap`         | `'soft' \| 'hard' \| 'off'` | `'soft'`    | Estratégia de quebra de linha     |

## Eventos

| Evento              | Payload      | Descrição                |
| ------------------- | ------------ | ------------------------ |
| `update:modelValue` | `String`     | Emitido durante a edição |
| `focus`             | `FocusEvent` | Emitido ao receber foco  |
| `blur`              | `FocusEvent` | Emitido ao perder foco   |

## Contrato do v-model

O conteúdo é emitido por:

```js
event.target.value;
```

Portanto, o payload de `update:modelValue` é sempre uma `String` durante a edição.

Mesmo quando `modelValue` inicial é numérico:

```js
123;
```

uma edição posterior será emitida como:

```js
"456";
```

Para uso comum, o `v-model` recomendado é textual:

```js
const description = ref("");
```

## rows

O padrão é:

```text
4
```

Exemplo:

```vue
<TextareaControl :rows="8" />
```

## cols

Permite definir a largura sugerida em colunas de caracteres:

```vue
<TextareaControl :cols="60" />
```

A largura visual final pode continuar sendo controlada pelo CSS do layout.

## wrap

Valores aceitos:

```text
soft
hard
off
```

Exemplo:

```vue
<TextareaControl wrap="hard" />
```

## Limites de caracteres

O componente encaminha:

```text
maxlength
minlength
```

diretamente ao elemento nativo.

Exemplo:

```vue
<TextareaControl :minlength="20" :maxlength="1000" />
```

O controle não renderiza contador visual. Essa responsabilidade pertence à camada de apresentação.

## Acessibilidade

`TextareaControl` utiliza:

```js
useFieldContext();
```

para obter:

```text
aria-describedby
aria-invalid
```

Quando existe hint:

```html
<textarea aria-describedby="description-hint"></textarea>
```

Quando existe erro:

```html
<textarea aria-describedby="description-error" aria-invalid="true"></textarea>
```

Fora de um contexto de campo, esses atributos permanecem ausentes.

## Estados nativos

São suportados diretamente:

```text
disabled
readonly
required
autofocus
```

## Apresentação visual

Assim como `InputControl`, o `TextareaControl` não possui borda visual própria.

O CSS remove:

```text
border
outline
box-shadow
background
```

para que a aparência externa seja controlada pelo campo do Design System.

Isso evita bordas duplicadas e mantém consistência visual.

## Redimensionamento

Por padrão:

```css
resize: vertical;
```

Isso permite que o usuário aumente a altura do campo sem alterar sua largura.

## Boas práticas

Use `TextareaControl` apenas como infraestrutura interna.

Na aplicação, prefira:

```text
AppTextarea
```

O componente público adiciona:

- label;
- hint;
- erro;
- required;
- acessibilidade integrada;
- composição com `BaseField`.

## Testes

Os testes estão em:

```text
tests/components/forms/controls/TextareaControl.spec.js
```

Cobertura atual:

```text
renderização
rows
cols
wrap
placeholder
autocomplete
maxlength
minlength
disabled
readonly
required
autofocus
v-model
focus
blur
```
