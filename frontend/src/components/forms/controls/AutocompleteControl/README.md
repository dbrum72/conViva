# AutocompleteControl

Controle interno de autocomplete baseado no padrão WAI-ARIA Combobox.

`AutocompleteControl` é responsável pela interação direta entre o campo textual e a lista de opções. Ele não renderiza label, hint ou mensagem de erro; essas responsabilidades pertencem ao `AppAutocomplete` e à infraestrutura de campo.

## Responsabilidade

O componente é responsável por:

- renderizar o `<input>` com `role="combobox"`;
- controlar abertura e fechamento da lista;
- filtrar opções pelo texto pesquisado;
- renderizar `listbox` e `option`;
- controlar opção ativa;
- permitir navegação por teclado;
- selecionar opções por mouse ou teclado;
- preservar o tipo original dos valores;
- emitir `modelValue` e `searchValue` separadamente;
- expor atributos ARIA do combobox;
- consumir o contexto do campo para `aria-describedby` e `aria-invalid`.

## Arquitetura

Fluxo típico:

```text
AppAutocomplete
        ↓
BaseField
        ↓
AutocompleteControl
```

O `AppAutocomplete` compõe a estrutura pública do campo.

O `AutocompleteControl` concentra o comportamento de interação.

## Uso interno

```vue
<AutocompleteControl
  v-model="selectedValue"
  v-model:searchValue="search"
  id="user"
  :options="options"
/>
```

Na aplicação, prefira:

```text
AppAutocomplete
```

## Props

| Prop              | Tipo                                            | Padrão                           | Descrição                         |
| ----------------- | ----------------------------------------------- | -------------------------------- | --------------------------------- |
| `modelValue`      | `String \| Number \| Boolean \| Object \| null` | `null`                           | Valor atualmente selecionado      |
| `searchValue`     | `String`                                        | `''`                             | Texto digitado no input           |
| `id`              | `String`                                        | `undefined`                      | Identificador do controle         |
| `name`            | `String`                                        | `undefined`                      | Nome do controle                  |
| `placeholder`     | `String`                                        | `''`                             | Placeholder                       |
| `disabled`        | `Boolean`                                       | `false`                          | Desabilita o controle             |
| `required`        | `Boolean`                                       | `false`                          | Marca como obrigatório            |
| `autofocus`       | `Boolean`                                       | `false`                          | Solicita foco automático          |
| `autocomplete`    | `String`                                        | `'off'`                          | Configuração HTML de autocomplete |
| `options`         | `Array`                                         | `[]`                             | Opções disponíveis                |
| `optionLabel`     | `String`                                        | `'label'`                        | Propriedade usada como texto      |
| `optionValue`     | `String`                                        | `'value'`                        | Propriedade usada como valor      |
| `noResultsText`   | `String`                                        | `'Nenhum resultado encontrado.'` | Mensagem de estado vazio          |
| `minSearchLength` | `Number`                                        | `0`                              | Quantidade mínima de caracteres   |
| `openOnFocus`     | `Boolean`                                       | `true`                           | Abre a lista ao receber foco      |
| `clearable`       | `Boolean`                                       | `false`                          | Prop reservada no contrato atual  |

## Eventos

| Evento               | Payload           | Descrição                   |
| -------------------- | ----------------- | --------------------------- |
| `update:modelValue`  | valor selecionado | Atualiza a seleção          |
| `update:searchValue` | `String`          | Atualiza o texto pesquisado |
| `focus`              | `FocusEvent`      | Emitido ao receber foco     |
| `blur`               | `FocusEvent`      | Emitido ao perder foco      |
| `open`               | —                 | Emitido ao abrir a lista    |
| `close`              | —                 | Emitido ao fechar a lista   |

## Dois estados independentes

O componente mantém dois contratos distintos.

### modelValue

Representa a opção efetivamente selecionada.

Exemplo:

```js
20;
```

### searchValue

Representa o texto do input.

Exemplo:

```js
"Oper";
```

Isso permite pesquisar sem alterar automaticamente a seleção atual.

## Filtragem

As opções visíveis são calculadas a partir de:

```js
props.searchValue;
```

O texto é normalizado com:

```js
.trim()
.toLocaleLowerCase()
```

A filtragem utiliza:

```js
String(getOptionLabel(option)).toLocaleLowerCase().includes(search);
```

Portanto, a pesquisa é aplicada sobre o label da opção.

## Busca vazia

Quando `searchValue` está vazio:

```js
return props.options;
```

Logo, todas as opções são exibidas, desde que `minSearchLength` permita.

## minSearchLength

Antes da filtragem, o componente verifica:

```js
search.length < props.minSearchLength;
```

Quando a condição é verdadeira:

```js
return [];
```

Exemplo:

```vue
<AutocompleteControl :min-search-length="2" />
```

Com apenas um caractere digitado, nenhuma opção é apresentada.

## Opções com objetos

Formato padrão:

```js
[
  {
    label: "Administrador",
    value: 10,
  },
  {
    label: "Operador",
    value: 20,
  },
];
```

## optionLabel e optionValue

Estruturas personalizadas são suportadas:

```js
[
  {
    id: 100,
    name: "Ana",
  },
  {
    id: 200,
    name: "Carlos",
  },
];
```

```vue
<AutocompleteControl option-label="name" option-value="id" />
```

## Opções primitivas

Também são aceitas:

```js
["Aberto", "Fechado"];
```

Nesse caso, o próprio valor é utilizado como label e value.

## Preservação de tipos

O valor selecionado é obtido diretamente da opção original:

```js
getOptionValue(option);
```

Portanto, valores como:

```js
20;
false;
("admin");
```

mantêm seus tipos originais.

O componente não depende de `event.target.value` para selecionar opções.

## Seleção por mouse

Cada opção utiliza:

```vue
@mousedown.prevent="selectOption(option)"
```

O uso de `mousedown.prevent` permite selecionar a opção antes que o input perca o foco.

Ao selecionar:

```js
emit("update:modelValue", getOptionValue(option));
```

e:

```js
emit("update:searchValue", String(getOptionLabel(option)));
```

Depois a lista é fechada.

## Abertura da lista

A lista é aberta por:

```js
openList();
```

A função não abre quando:

```text
disabled = true
```

ou quando a lista já está aberta.

Ao abrir:

```js
isOpen = true;
```

e a primeira opção visível passa a ser ativa quando houver resultados.

## openOnFocus

Por padrão:

```js
openOnFocus = true;
```

Assim, ao receber foco:

```js
openList();
```

é executado.

Quando:

```vue
:open-on-focus="false"
```

o foco sozinho não abre a lista.

Digitar, entretanto, continua chamando `openList()`.

## Fechamento

A função:

```js
closeList();
```

define:

```js
isOpen = false;
activeIndex = -1;
```

e emite:

```text
close
```

## focusout

O contêiner monitora:

```vue
@focusout="handleFocusOut"
```

Se o próximo foco permanecer dentro do componente, a lista continua aberta.

Caso contrário, é fechada.

## Navegação por teclado

O controle suporta atualmente:

```text
ArrowDown
ArrowUp
Enter
Escape
```

### ArrowDown

Move a opção ativa para frente.

A navegação é circular:

```text
última opção
    ↓
primeira opção
```

### ArrowUp

Move a opção ativa para trás.

Também é circular:

```text
primeira opção
    ↑
última opção
```

### Enter

Seleciona a opção atualmente ativa.

### Escape

Fecha a lista.

## Opção ativa

A posição ativa é armazenada em:

```js
activeIndex;
```

Quando válida, o input recebe:

```text
aria-activedescendant
```

com o ID da opção.

Formato:

```text
{id}-option-{index}
```

Exemplo:

```text
user-option-1
```

## Listbox

Quando aberta, a lista é renderizada como:

```html
<ul role="listbox"></ul>
```

Seu ID segue:

```text
{id}-listbox
```

Exemplo:

```text
user-listbox
```

## Opções

Cada resultado utiliza:

```html
<li role="option"></li>
```

e recebe:

```text
aria-selected
```

conforme a comparação entre:

```js
getOptionValue(option);
```

e:

```js
props.modelValue;
```

A comparação utiliza:

```js
Object.is();
```

## Estado vazio

Quando:

```js
visibleOptions.length === 0;
```

é renderizado um item com:

```text
noResultsText
```

Padrão:

```text
Nenhum resultado encontrado.
```

Esse item utiliza:

```html
role="option" aria-disabled="true"
```

## WAI-ARIA Combobox

O input utiliza:

```html
role="combobox"
```

e expõe:

```text
aria-expanded
aria-controls
aria-activedescendant
aria-invalid
aria-describedby
```

## aria-expanded

Reflete diretamente:

```js
isOpen;
```

Exemplo fechado:

```text
aria-expanded="false"
```

Exemplo aberto:

```text
aria-expanded="true"
```

## aria-controls

Quando existe `id`, o input referencia a listbox:

```text
{id}-listbox
```

## aria-activedescendant

Quando existe uma opção ativa válida:

```text
{id}-option-{index}
```

é fornecido ao input.

Quando nenhuma opção está ativa:

```text
undefined
```

## aria-selected

Cada opção informa se representa o `modelValue` atual.

Exemplo:

```html
<li role="option" aria-selected="true"></li>
```

## Contexto do campo

O componente utiliza:

```js
useFieldContext();
```

para obter:

```text
ariaDescribedBy
ariaInvalid
```

Isso permite integração com:

```text
BaseField
AppField
```

através do `AppAutocomplete`.

## Disabled

Quando desabilitado:

- o input recebe `disabled`;
- a lista não pode ser aberta por `openList()`;
- o CSS utiliza `cursor: not-allowed`.

## Apresentação visual

O input interno não possui borda própria:

```text
border: 0
outline: 0
box-shadow: none
background: transparent
```

A borda externa pertence à infraestrutura do campo.

A listbox, por outro lado, possui apresentação própria:

```text
background
border
border-radius
box-shadow
max-height
overflow-y
```

## Opção ativa

A opção ativa recebe:

```text
autocomplete-control__option--active
```

e compartilha o mesmo estado visual de hover.

## clearable

A prop:

```js
clearable;
```

faz parte do contrato atual, com padrão:

```js
false;
```

mas não possui comportamento implementado no `AutocompleteControl` nesta versão.

Ela deve ser considerada reservada para evolução futura.

## Boas práticas

Use `AutocompleteControl` apenas dentro da infraestrutura do Design System.

Na aplicação, prefira:

```text
AppAutocomplete
```

O componente público adiciona:

- label;
- hint;
- erro;
- required;
- disabled;
- readonly;
- composição com `BaseField`.

## Testes

Os testes estão em:

```text
tests/components/forms/controls/AutocompleteControl.spec.js
```

A cobertura atual inclui:

```text
renderização
combobox
placeholder
autocomplete
disabled
required
autofocus
abertura
fechamento
openOnFocus
pesquisa
filtragem
estado vazio
minSearchLength
optionLabel
optionValue
opções primitivas
boolean
seleção por mouse
ArrowDown
ArrowUp
Enter
Escape
aria-controls
aria-expanded
aria-activedescendant
aria-selected
focus
blur
```
