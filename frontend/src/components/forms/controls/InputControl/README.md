# InputControl

Controle HTML de entrada baseado em `<input>`.

`InputControl` é um componente interno do Design System. Ele não renderiza label, hint ou mensagem de erro. Essas responsabilidades pertencem aos componentes de campo, como `AppInput`, `AppEmail`, `AppPassword`, `AppPhone`, `AppURL`, `AppNumber` e `AppCurrency`.

## Responsabilidade

O componente é responsável por:

- renderizar o elemento `<input>`;
- encaminhar atributos nativos;
- emitir alterações de valor;
- emitir eventos de foco e blur;
- consumir o contexto de campo para atributos ARIA;
- manter a apresentação visual desacoplada do campo externo.

## Arquitetura

Fluxo típico:

```text
AppInput / variantes
        ↓
BaseField
        ↓
InputControl
```

`InputControl` não deve implementar:

- label;
- hint;
- mensagem de erro;
- validação de domínio;
- parsing numérico;
- máscaras;
- formatação monetária.

Essas responsabilidades pertencem às camadas superiores.

## Uso interno

```vue
<InputControl v-model="value" id="name" name="name" type="text" />
```

Na prática, o componente é normalmente utilizado dentro de um campo público, e não diretamente pela aplicação.

## Props

| Prop           | Tipo               | Padrão      | Descrição                         |
| -------------- | ------------------ | ----------- | --------------------------------- |
| `modelValue`   | `String \| Number` | `''`        | Valor exibido pelo input          |
| `type`         | `String`           | `'text'`    | Tipo HTML do input                |
| `id`           | `String`           | `undefined` | Identificador do controle         |
| `name`         | `String`           | `undefined` | Nome do controle                  |
| `placeholder`  | `String`           | `''`        | Placeholder                       |
| `disabled`     | `Boolean`          | `false`     | Desabilita o controle             |
| `readonly`     | `Boolean`          | `false`     | Impede edição                     |
| `required`     | `Boolean`          | `false`     | Marca o controle como obrigatório |
| `autofocus`    | `Boolean`          | `false`     | Solicita foco automático          |
| `autocomplete` | `String`           | `'off'`     | Configuração de autocomplete      |
| `maxlength`    | `Number`           | `undefined` | Comprimento máximo                |
| `minlength`    | `Number`           | `undefined` | Comprimento mínimo                |
| `inputmode`    | `String`           | `undefined` | Sugere teclado virtual            |
| `min`          | `Number \| String` | `undefined` | Valor mínimo                      |
| `max`          | `Number \| String` | `undefined` | Valor máximo                      |
| `step`         | `Number \| String` | `undefined` | Incremento numérico               |

## Eventos

| Evento              | Payload      | Descrição                |
| ------------------- | ------------ | ------------------------ |
| `update:modelValue` | `String`     | Emitido durante a edição |
| `focus`             | `FocusEvent` | Emitido ao receber foco  |
| `blur`              | `FocusEvent` | Emitido ao perder foco   |

## Contrato do v-model

O DOM sempre fornece o conteúdo editado através de:

```js
event.target.value;
```

Por isso, o componente emite `String` durante a edição, mesmo quando:

```vue
type="number"
```

Exemplo:

```text
entrada: 25
payload emitido: "25"
```

Conversões específicas pertencem à camada superior.

Por exemplo:

- `AppNumber` converte para `number`;
- `AppCurrency` faz parsing e formatação monetária;
- campos textuais mantêm `string`.

## Acessibilidade

`InputControl` utiliza:

```js
useFieldContext();
```

para obter:

```text
aria-describedby
aria-invalid
```

Quando renderizado dentro de um campo com hint:

```html
<input aria-describedby="email-hint" />
```

Quando há erro:

```html
<input aria-describedby="email-error" aria-invalid="true" />
```

Se nenhum contexto de campo estiver disponível, os atributos ARIA permanecem ausentes.

## Estados nativos

O componente suporta diretamente:

```text
disabled
readonly
required
autofocus
```

Esses estados são encaminhados ao elemento HTML.

## Apresentação visual

`InputControl` não possui borda visual própria.

O CSS remove:

```text
border
outline
box-shadow
background
```

para que o contêiner externo do campo seja responsável pela aparência.

Isso evita bordas duplicadas e mantém consistência entre os diferentes tipos de campos.

## Foco

O foco visual principal pertence ao contêiner do campo.

`InputControl` não cria um segundo anel de foco próprio.

## Boas práticas

Utilize `InputControl` apenas como infraestrutura interna.

Para código de aplicação, prefira componentes públicos:

```text
AppInput
AppEmail
AppPassword
AppPhone
AppURL
AppNumber
AppCurrency
AppSearch
```

## Testes

Os testes estão em:

```text
tests/components/forms/controls/InputControl.spec.js
```

Cobertura atual:

```text
renderização
type
inputmode
placeholder
autocomplete
maxlength
minlength
min
max
step
disabled
readonly
required
autofocus
v-model
focus
blur
```
