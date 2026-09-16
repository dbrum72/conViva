# BaseField

Componente estrutural interno que compõe `AppField` com o controle principal e, quando necessário, com os slots `prepend` e `append`.

`BaseField` funciona como uma camada intermediária entre os componentes públicos de formulário e `AppField`.

## Responsabilidade

O componente é responsável por:

- encaminhar props de campo para `AppField`;
- renderizar o slot padrão;
- encaminhar o slot `prepend`;
- encaminhar o slot `append`;
- reutilizar a infraestrutura visual e de acessibilidade do `AppField`.

`BaseField` não possui estilo próprio nem contrato de props próprio.

Ele reutiliza:

```js
fieldProps;
```

e delega a apresentação visual para:

```text
AppField
```

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

Exemplo:

```text
AppTextarea
    ↓
BaseField
    ↓
AppField
    ↓
TextareaControl
```

## Uso interno

```vue
<BaseField id="description" label="Descrição">
  <TextareaControl
    id="description"
  />
</BaseField>
```

Na aplicação, prefira utilizar os componentes públicos.

## Props

`BaseField` reutiliza diretamente:

```text
fieldProps
```

definidas em:

```text
src/components/forms/shared/props/field.js
```

As props disponíveis são:

| Prop       | Tipo      | Padrão      | Descrição                           |
| ---------- | --------- | ----------- | ----------------------------------- |
| `id`       | `String`  | `undefined` | Identificador do controle associado |
| `disabled` | `Boolean` | `false`     | Estado desabilitado                 |
| `required` | `Boolean` | `false`     | Campo obrigatório                   |
| `readonly` | `Boolean` | `false`     | Estado somente leitura              |
| `label`    | `String`  | `''`        | Label do campo                      |
| `hint`     | `String`  | `''`        | Texto auxiliar                      |
| `error`    | `String`  | `''`        | Mensagem de erro                    |

## Slots

### default

Recebe o controle principal.

```vue
<BaseField id="name" label="Nome">
  <InputControl
    id="name"
  />
</BaseField>
```

### prepend

Encaminha conteúdo para o slot `prepend` do `AppField`.

```vue
<BaseField id="amount" label="Valor">
  <template #prepend>
    R$
  </template>

  <InputControl
    id="amount"
  />
</BaseField>
```

### append

Encaminha conteúdo para o slot `append` do `AppField`.

```vue
<BaseField id="weight" label="Peso">
  <InputControl
    id="weight"
  />

  <template #append>
    kg
  </template>
</BaseField>
```

## Comportamento

Internamente, `BaseField` renderiza:

```vue
<AppField
  :id="id"
  :label="label"
  :hint="hint"
  :error="error"
  :required="required"
  :disabled="disabled"
  :readonly="readonly"
>
  ...
</AppField>
```

Portanto, toda a lógica de:

- label;
- hint;
- erro;
- required;
- disabled;
- readonly;
- contexto de acessibilidade;

permanece centralizada em `AppField`.

## Por que BaseField existe?

Sem `BaseField`, cada componente público precisaria repetir a composição com `AppField`.

Exemplo de duplicação evitada:

```text
AppInput
AppTextarea
AppSelect
AppAutocomplete
```

Todos podem compartilhar a mesma estrutura intermediária.

Isso reduz:

- repetição de template;
- inconsistências de slots;
- divergências no encaminhamento de props;
- custo de manutenção.

## Acessibilidade

`BaseField` não calcula atributos ARIA diretamente.

Ao compor `AppField`, ele permite que o contexto fornecido pelo campo alcance os controles descendentes.

Assim, controles como:

```text
InputControl
TextareaControl
SelectControl
AutocompleteControl
```

podem consumir:

```js
useFieldContext();
```

e obter:

```text
aria-describedby
aria-invalid
```

## Ausência de estilo próprio

`BaseField` não possui:

```text
style.css
```

Isso é intencional.

A aparência pertence a:

```text
AppField
```

e aos controles internos.

`BaseField` é apenas uma camada de composição.

## Boas práticas

Use `BaseField` ao criar novos componentes públicos que sigam o padrão clássico:

```text
label
controle
hint / erro
```

Exemplos adequados:

```text
AppInput
AppTextarea
AppSelect
AppAutocomplete
```

Não é obrigatório utilizar `BaseField` quando o componente possui uma estrutura semântica diferente.

Por exemplo:

```text
AppCheckbox
AppRadio
AppSwitch
```

possuem layouts próprios e não dependem de `BaseField`.

## Testes

Os testes estão em:

```text
tests/components/forms/fields/BaseField.spec.js
```

Cobertura atual:

```text
slot padrão
label
required
hint
erro
precedência do erro
prepend
append
classes de estado
contexto do campo
aria-describedby
aria-invalid
ausência de id
```
