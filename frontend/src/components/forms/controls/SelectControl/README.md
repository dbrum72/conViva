# SelectControl

Controle HTML de seleção baseado em `<select>`.

`SelectControl` é um componente interno do Design System. Ele concentra o comportamento nativo de seleção e a normalização das opções, enquanto `AppSelect` adiciona a estrutura de campo, label, hint, erro e demais aspectos de apresentação.

## Responsabilidade

O componente é responsável por:

- renderizar o elemento `<select>`;
- renderizar placeholder opcional;
- renderizar opções primitivas ou objetos;
- permitir configuração de `optionLabel` e `optionValue`;
- preservar o tipo original dos valores selecionados;
- emitir alterações através de `update:modelValue`;
- emitir eventos de foco e blur;
- consumir o contexto do campo para atributos ARIA.

## Arquitetura

Fluxo típico:

```text
AppSelect
    ↓
BaseField
    ↓
SelectControl
```

`SelectControl` não deve implementar:

- label do campo;
- hint;
- mensagem de erro;
- busca;
- filtragem;
- múltipla seleção;
- carregamento remoto de opções.

Para seleção com pesquisa, utilize `AppAutocomplete`.

## Uso interno

```vue
<SelectControl v-model="status" id="status" name="status" :options="options" />
```

Na aplicação, prefira utilizar:

```text
AppSelect
```

## Props

| Prop          | Tipo                                            | Padrão      | Descrição                                 |
| ------------- | ----------------------------------------------- | ----------- | ----------------------------------------- |
| `modelValue`  | `String \| Number \| Boolean \| Object \| null` | `null`      | Valor atualmente selecionado              |
| `id`          | `String`                                        | `undefined` | Identificador do controle                 |
| `name`        | `String`                                        | `undefined` | Nome do controle                          |
| `placeholder` | `String`                                        | `''`        | Opção inicial desabilitada                |
| `disabled`    | `Boolean`                                       | `false`     | Desabilita o controle                     |
| `required`    | `Boolean`                                       | `false`     | Marca a seleção como obrigatória          |
| `autofocus`   | `Boolean`                                       | `false`     | Solicita foco automático                  |
| `options`     | `Array`                                         | `[]`        | Opções disponíveis                        |
| `optionLabel` | `String`                                        | `'label'`   | Propriedade utilizada como texto da opção |
| `optionValue` | `String`                                        | `'value'`   | Propriedade utilizada como valor da opção |

## Eventos

| Evento              | Payload                  | Descrição                             |
| ------------------- | ------------------------ | ------------------------------------- |
| `update:modelValue` | valor da opção ou `null` | Emitido quando a seleção muda         |
| `focus`             | `FocusEvent`             | Emitido quando o controle recebe foco |
| `blur`              | `FocusEvent`             | Emitido quando o controle perde foco  |

## Opções com objetos

O formato padrão esperado é:

```js
const options = [
  {
    label: "Ativo",
    value: "active",
  },
  {
    label: "Inativo",
    value: "inactive",
  },
];
```

Uso:

```vue
<SelectControl v-model="status" :options="options" />
```

## optionLabel e optionValue

Quando a estrutura dos objetos utiliza outros nomes, configure explicitamente as propriedades.

```js
const users = [
  {
    id: 10,
    name: "Ana",
  },
  {
    id: 20,
    name: "Carlos",
  },
];
```

```vue
<SelectControl
  v-model="userId"
  :options="users"
  option-label="name"
  option-value="id"
/>
```

Nesse exemplo:

```text
label → name
value → id
```

e `userId` receberá:

```js
10;
```

ou:

```js
20;
```

## Opções primitivas

Também são aceitas opções primitivas:

```js
const options = ["Pendente", "Concluído", "Cancelado"];
```

```vue
<SelectControl v-model="status" :options="options" />
```

Nesse caso, cada valor é utilizado simultaneamente como label e value.

## Preservação de tipos

O componente não utiliza diretamente:

```js
event.target.value;
```

como valor final do `v-model`.

Isso é importante porque o DOM representa o valor das opções como texto em diversas situações.

Em vez disso, o componente identifica a posição selecionada e recupera a opção original em:

```js
props.options;
```

Depois emite:

```js
getOptionValue(selectedOption);
```

Isso preserva valores como:

```js
1;
false;
("active");
```

sem conversões indevidas.

### Exemplo numérico

```js
const options = [
  { label: "Baixa", value: 1 },
  { label: "Alta", value: 2 },
];
```

A seleção de `Alta` produz:

```js
2;
```

e não:

```js
"2";
```

### Exemplo booleano

```js
const options = [
  { label: "Sim", value: true },
  { label: "Não", value: false },
];
```

O componente preserva:

```js
true;
false;
```

como booleanos.

## Placeholder

Quando `placeholder` possui conteúdo, é criada uma opção inicial:

```html
<option value="" disabled>Selecione...</option>
```

Exemplo:

```vue
<SelectControl v-model="status" placeholder="Selecione..." :options="options" />
```

Quando `placeholder` está vazio, essa opção não é renderizada.

O placeholder não representa uma opção válida do domínio.

## Seleção inválida

Se o índice selecionado não corresponder a uma entrada válida de `options`, o componente emite:

```js
null;
```

Isso impede que valores artificiais do DOM sejam tratados como opções válidas.

## Acessibilidade

`SelectControl` utiliza:

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
<select aria-describedby="status-hint"></select>
```

Quando existe erro:

```html
<select aria-describedby="status-error" aria-invalid="true"></select>
```

Fora de um contexto de campo, esses atributos permanecem ausentes.

## Estados nativos

O componente encaminha diretamente:

```text
disabled
required
autofocus
```

ao `<select>`.

## Apresentação visual

`SelectControl` não cria a borda externa do campo.

Seu CSS mantém:

```text
border: 0
outline: none
box-shadow: none
background: transparent
```

A apresentação do campo pertence à camada externa do Design System.

Essa separação evita bordas duplicadas e mantém consistência com:

```text
InputControl
TextareaControl
AutocompleteControl
```

## Boas práticas

Use `SelectControl` internamente quando:

- o conjunto de opções for conhecido;
- o usuário precisar selecionar uma única opção;
- busca textual não for necessária.

Na aplicação, prefira:

```text
AppSelect
```

Para conjuntos maiores que exigem pesquisa, prefira:

```text
AppAutocomplete
```

## Testes

Os testes estão em:

```text
tests/components/forms/controls/SelectControl.spec.js
```

A cobertura atual inclui:

```text
renderização
placeholder
opções primitivas
opções com objetos
optionLabel
optionValue
valores numéricos
valores string
valores booleanos
seleção inválida
disabled
required
autofocus
focus
blur
```
