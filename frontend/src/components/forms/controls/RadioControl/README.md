# RadioControl

Controle de seleção exclusiva baseado em `<input type="radio">`.

`RadioControl` é um componente interno do Design System. Ele concentra o comportamento nativo do radio e preserva o tipo original do valor selecionado.

## Responsabilidade

O componente é responsável por:

- renderizar o elemento `<input type="radio">`;
- comparar `modelValue` com `value`;
- refletir o estado selecionado em `checked`;
- preservar valores `String`, `Number` e `Boolean`;
- emitir o valor configurado sem conversão pelo DOM;
- encaminhar estados nativos;
- encaminhar atributos ARIA;
- emitir eventos de foco e blur.

## Arquitetura

Fluxo típico:

```text
AppRadio
    ↓
RadioControl
```

O `AppRadio` adiciona:

- label;
- hint;
- mensagem de erro;
- indicador obrigatório;
- classes de estado;
- cálculo de `aria-describedby` e `aria-invalid`.

## Uso interno

```vue
<RadioControl
  v-model="status"
  id="status-active"
  name="status"
  value="active"
/>
```

Na aplicação, prefira:

```text
AppRadio
```

## Props

| Prop              | Tipo                                  | Padrão      | Descrição                         |
| ----------------- | ------------------------------------- | ----------- | --------------------------------- |
| `modelValue`      | `String \| Number \| Boolean \| null` | `null`      | Valor atualmente selecionado      |
| `value`           | `String \| Number \| Boolean`         | obrigatório | Valor representado por esta opção |
| `id`              | `String`                              | `undefined` | Identificador do controle         |
| `name`            | `String`                              | `undefined` | Nome do grupo                     |
| `disabled`        | `Boolean`                             | `false`     | Desabilita o controle             |
| `required`        | `Boolean`                             | `false`     | Marca o controle como obrigatório |
| `autofocus`       | `Boolean`                             | `false`     | Solicita foco automático          |
| `ariaInvalid`     | `Boolean \| String`                   | `undefined` | Estado ARIA de invalidez          |
| `ariaDescribedBy` | `String`                              | `undefined` | IDs dos elementos descritivos     |

## Eventos

| Evento              | Payload          | Descrição                            |
| ------------------- | ---------------- | ------------------------------------ |
| `update:modelValue` | valor de `value` | Emitido quando a opção é selecionada |
| `focus`             | `FocusEvent`     | Emitido ao receber foco              |
| `blur`              | `FocusEvent`     | Emitido ao perder foco               |

## Comparação

O estado selecionado é determinado por:

```js
Object.is(props.modelValue, props.value);
```

Isso garante comparação estrita de tipo.

Exemplos:

```js
Object.is(1, 1); // true
Object.is("1", 1); // false
Object.is(false, 0); // false
Object.is(true, true); // true
```

Essa decisão evita seleção incorreta causada por coerção de tipos.

## Preservação de tipos

O componente não utiliza:

```js
event.target.value;
```

para determinar o valor emitido.

Em vez disso, emite diretamente:

```js
props.value;
```

Isso preserva o tipo original.

### String

```vue
<RadioControl value="active" />
```

Emite:

```js
"active";
```

### Number

```vue
<RadioControl :value="20" />
```

Emite:

```js
20;
```

e não:

```js
"20";
```

### Boolean

```vue
<RadioControl :value="false" />
```

Emite:

```js
false;
```

## Agrupamento

Radios pertencentes ao mesmo grupo devem compartilhar o mesmo:

```text
name
```

e normalmente o mesmo:

```text
modelValue
```

Exemplo:

```vue
<RadioControl v-model="status" name="status" value="active" />

<RadioControl v-model="status" name="status" value="inactive" />
```

O `name` mantém a semântica nativa de grupo no navegador.

## Reatividade

O estado `checked` reage a alterações tanto de:

```text
modelValue
```

quanto de:

```text
value
```

Exemplo:

```js
modelValue = "inactive";
value = "active";
```

Resultado:

```text
checked = false
```

Após:

```js
modelValue = "active";
```

Resultado:

```text
checked = true
```

## Acessibilidade

O componente encaminha:

```text
aria-invalid
aria-describedby
```

recebidos pelas props:

```text
ariaInvalid
ariaDescribedBy
```

Exemplo:

```html
<input type="radio" aria-invalid="true" aria-describedby="status-error" />
```

A responsabilidade de calcular esses valores pertence ao `AppRadio`.

## Estados nativos

O controle encaminha diretamente:

```text
disabled
required
autofocus
```

## Foco

O CSS utiliza:

```css
box-shadow: var(--focus-ring);
```

nos estados de foco.

Isso mantém o foco perceptível e consistente com os demais controles de seleção.

## Aparência

O controle utiliza o radio nativo do navegador com:

```css
accent-color: var(--color-accent);
```

Essa abordagem preserva:

- comportamento nativo;
- teclado;
- semântica;
- integração com tecnologias assistivas.

## Boas práticas

Utilize `RadioControl` apenas como infraestrutura interna.

Na aplicação, prefira:

```text
AppRadio
```

Para representar várias opções relacionadas com label de grupo, validação compartilhada e mensagens comuns, a responsabilidade deverá pertencer a um futuro:

```text
RadioGroup
```

## Testes

Os testes estão em:

```text
tests/components/forms/controls/RadioControl.spec.js
```

Cobertura atual:

```text
renderização
checked
comparação estrita
string
number
boolean
preservação de tipos
disabled
required
autofocus
aria-invalid
aria-describedby
focus
blur
reatividade de modelValue
reatividade de value
```
