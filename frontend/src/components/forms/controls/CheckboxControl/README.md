# CheckboxControl

Controle booleano baseado em `<input type="checkbox">`.

`CheckboxControl` é um componente interno do Design System. Ele concentra o comportamento nativo do checkbox e expõe um contrato simples para `AppCheckbox`.

## Responsabilidade

O componente é responsável por:

- renderizar o elemento `<input type="checkbox">`;
- refletir `modelValue` no estado `checked`;
- emitir alterações booleanas;
- encaminhar estados nativos;
- encaminhar atributos ARIA recebidos do componente público;
- suportar o estado DOM `indeterminate`;
- emitir eventos de foco e blur.

## Arquitetura

Fluxo típico:

```text
AppCheckbox
    ↓
CheckboxControl
```

Diferentemente de controles baseados em `BaseField`, o `CheckboxControl` recebe diretamente:

```text
ariaInvalid
ariaDescribedBy
```

porque `AppCheckbox` possui estrutura própria de label, hint e erro.

## Uso interno

```vue
<CheckboxControl v-model="accepted" id="accepted" name="accepted" />
```

Na aplicação, prefira:

```text
AppCheckbox
```

## Props

| Prop              | Tipo                | Padrão      | Descrição                         |
| ----------------- | ------------------- | ----------- | --------------------------------- |
| `modelValue`      | `Boolean`           | `false`     | Estado marcado/desmarcado         |
| `id`              | `String`            | `undefined` | Identificador do controle         |
| `name`            | `String`            | `undefined` | Nome do controle                  |
| `disabled`        | `Boolean`           | `false`     | Desabilita o checkbox             |
| `required`        | `Boolean`           | `false`     | Marca o controle como obrigatório |
| `autofocus`       | `Boolean`           | `false`     | Solicita foco automático          |
| `indeterminate`   | `Boolean`           | `false`     | Estado visual intermediário       |
| `ariaInvalid`     | `Boolean \| String` | `undefined` | Estado ARIA de invalidez          |
| `ariaDescribedBy` | `String`            | `undefined` | IDs dos elementos descritivos     |

## Eventos

| Evento              | Payload      | Descrição                    |
| ------------------- | ------------ | ---------------------------- |
| `update:modelValue` | `Boolean`    | Emitido quando o estado muda |
| `focus`             | `FocusEvent` | Emitido ao receber foco      |
| `blur`              | `FocusEvent` | Emitido ao perder foco       |

## Contrato do v-model

O valor é obtido diretamente de:

```js
event.target.checked;
```

Logo, `update:modelValue` sempre emite:

```js
true;
```

ou:

```js
false;
```

Não existe conversão textual.

## checked

O estado visual é controlado por:

```vue
:checked="modelValue"
```

Exemplo:

```js
modelValue = true;
```

produz um checkbox marcado.

## indeterminate

`indeterminate` é uma propriedade do DOM, e não um atributo HTML declarativo.

Por isso, o componente aplica o estado diretamente:

```js
inputRef.value.indeterminate = props.indeterminate;
```

A propriedade é aplicada:

- na montagem;
- sempre que `indeterminate` mudar.

Exemplo:

```vue
<CheckboxControl :model-value="false" :indeterminate="true" />
```

Um checkbox pode estar simultaneamente:

```text
checked = true
indeterminate = true
```

Esses são estados DOM independentes.

## Semântica do estado indeterminate

O estado `indeterminate` representa uma situação intermediária, normalmente usada em seleções hierárquicas.

Exemplo:

```text
Selecionar todos
├── item 1 ✓
├── item 2 ✓
└── item 3 ✗
```

O controle "Selecionar todos" pode aparecer indeterminado porque apenas parte dos itens está selecionada.

`indeterminate` é principalmente um estado visual. A lógica de negócio continua sendo controlada por `modelValue`.

## Acessibilidade

O controle encaminha:

```text
aria-invalid
aria-describedby
```

recebidos através das props:

```text
ariaInvalid
ariaDescribedBy
```

Exemplo:

```html
<input type="checkbox" aria-invalid="true" aria-describedby="terms-error" />
```

A responsabilidade de calcular esses valores pertence ao `AppCheckbox`.

## Estados nativos

São encaminhados diretamente:

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

em `:focus` e `:focus-visible`.

Isso mantém o foco perceptível e consistente com os demais controles de seleção.

## Aparência

O controle utiliza o checkbox nativo do navegador com:

```css
accent-color: var(--color-accent);
```

Essa abordagem preserva:

- semântica nativa;
- interação por teclado;
- integração com tecnologias assistivas;
- comportamento consistente entre plataformas.

## Boas práticas

Use `CheckboxControl` apenas como infraestrutura interna.

Na aplicação, prefira:

```text
AppCheckbox
```

Paro grupos de múltipla seleção, a responsabilidade deverá pertencer a um futuro:

```text
AppCheckboxGroup
```

## Limitações

O contrato atual é estritamente booleano:

```text
Boolean
```

O componente não representa diretamente:

```text
arrays de valores
seleções múltiplas
value individual paro grupos
```

Esses casos deverão ser tratados por um componente de grupo.

## Testes

Os testes estão em:

```text
tests/components/forms/controls/CheckboxControl.spec.js
```

Cobertura atual:

```text
renderização
checked
disabled
required
autofocus
aria-invalid
aria-describedby
v-model
focus
blur
indeterminate
reatividade de indeterminate
independência entre checked e indeterminate
```
