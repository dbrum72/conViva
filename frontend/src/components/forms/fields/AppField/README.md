# AppField

Componente estrutural responsável por apresentar a moldura semântica e visual de um campo de formulário.

`AppField` centraliza:

- label;
- indicador de obrigatório;
- área de controle;
- slots `prepend` e `append`;
- hint;
- mensagem de erro;
- classes de estado;
- contexto compartilhado de acessibilidade para controles descendentes.

## Responsabilidade

O componente é responsável por:

- renderizar a estrutura visual de um campo;
- associar `label` ao controle através de `for` e `id`;
- exibir hint e erro;
- priorizar erro sobre hint;
- gerar IDs auxiliares;
- fornecer estado do campo via `provide`;
- centralizar classes de estado do Design System.

`AppField` não controla diretamente o valor de inputs.

O elemento de entrada é fornecido através do slot padrão.

## Arquitetura

Fluxo típico:

```text
AppInput / AppTextarea / AppSelect / variantes
                    ↓
                 BaseField
                    ↓
                 AppField
                    ↓
              Control específico
```

`AppField` também fornece contexto para controles que utilizam:

```js
useFieldContext();
```

## Uso básico

```vue
<AppField id="email" label="E-mail">
  <input id="email" type="email">
</AppField>
```

Em uso normal, `AppField` é consumido através de `BaseField` e dos componentes públicos.

## Props

| Prop       | Tipo      | Padrão      | Descrição                           |
| ---------- | --------- | ----------- | ----------------------------------- |
| `id`       | `String`  | `undefined` | Identificador do controle associado |
| `label`    | `String`  | `''`        | Label do campo                      |
| `required` | `Boolean` | `false`     | Exibe indicador obrigatório         |
| `hint`     | `String`  | `''`        | Texto auxiliar                      |
| `error`    | `String`  | `''`        | Mensagem de erro                    |
| `disabled` | `Boolean` | `false`     | Estado visual desabilitado          |
| `readonly` | `Boolean` | `false`     | Estado visual somente leitura       |

## Slots

### default

Recebe o controle principal.

```vue
<AppField id="name" label="Nome">
  <input id="name">
</AppField>
```

### prepend

Renderiza conteúdo antes do controle.

```vue
<AppField id="amount" label="Valor">
  <template #prepend>
    R$
  </template>

  <input id="amount">
</AppField>
```

### append

Renderiza conteúdo após o controle.

```vue
<AppField id="weight" label="Peso">
  <input id="weight">

  <template #append>
    kg
  </template>
</AppField>
```

## Label

Quando `label` possui conteúdo, o componente renderiza:

```html
<label for="field-id"> Texto da label </label>
```

Quando `label` está vazio, o elemento não é renderizado.

## Required

Quando `required` é verdadeiro, o componente:

- aplica a classe `app-field--required`;
- exibe `*` junto à label;
- marca o indicador com `aria-hidden="true"`.

Exemplo:

```vue
<AppField id="name" label="Nome" required>
  ...
</AppField>
```

## Hint

Quando `hint` possui conteúdo e não existe erro:

```html
<p id="field-id-hint">Texto auxiliar</p>
```

O ID segue o padrão:

```text
{id}-hint
```

## Error

Quando `error` possui conteúdo:

```html
<p id="field-id-error">Mensagem de erro</p>
```

O ID segue:

```text
{id}-error
```

O erro tem precedência sobre o hint.

Assim, se ambos existirem:

```text
hint → não renderizado
error → renderizado
```

## Classes de estado

O componente aplica classes conforme o estado atual:

```text
app-field--required
app-field--invalid
app-field--disabled
app-field--readonly
```

### Invalid

É considerado inválido quando:

```js
Boolean(error);
```

retorna `true`.

## Contexto do campo

`AppField` fornece um contexto reativo através de:

```js
provide(FIELD_CONTEXT, fieldContext);
```

O contexto possui:

```js
{
  id,
  hintId,
  errorId,
  required,
  disabled,
  readonly,
  invalid,
}
```

Esse contexto pode ser consumido por controles descendentes usando:

```js
useFieldContext();
```

## aria-describedby

`AppField` não aplica diretamente `aria-describedby` ao controle.

Ele fornece os IDs necessários no contexto.

O `useFieldContext()` resolve:

```text
campo válido + hint
→ {id}-hint

campo inválido
→ {id}-error
```

Assim, controles como `InputControl`, `TextareaControl` e `SelectControl` recebem automaticamente a referência correta.

## aria-invalid

Quando existe erro, o contexto expõe:

```text
invalid = true
```

e `useFieldContext()` converte esse estado em:

```html
aria-invalid="true"
```

## Ausência de id

Quando `id` não é fornecido:

```text
hintId = undefined
errorId = undefined
```

Hint e erro ainda podem ser renderizados visualmente, mas não recebem IDs automáticos.

Por isso, em componentes públicos de formulário, fornecer `id` é recomendado.

## Acessibilidade

O componente contribui para acessibilidade através de:

- associação entre label e controle;
- IDs previsíveis para hint e erro;
- estado obrigatório visível;
- contexto reativo;
- suporte a `aria-describedby`;
- suporte a `aria-invalid`.

## Boas práticas

`AppField` é infraestrutura.

Na aplicação, prefira os componentes públicos:

```text
AppInput
AppTextarea
AppSelect
AppAutocomplete
AppNumber
AppCurrency
```

Use `AppField` diretamente apenas ao construir novos componentes do Design System.

## Testes

A composição de `AppField` é coberta principalmente por:

```text
tests/components/forms/fields/BaseField.spec.js
```

A cobertura atual inclui:

```text
label
required
hint
erro
precedência do erro
prepend
append
classes de estado
contexto
aria-describedby
aria-invalid
IDs auxiliares
```
