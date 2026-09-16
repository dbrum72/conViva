# AppDate

Variante de entrada para datas civis, baseada na infraestrutura compartilhada de `AppInput` e `InputVariant`.

`AppDate` utiliza o controle nativo:

```html
<input type="date" />
```

e mantém o `modelValue` como texto no formato:

```text
YYYY-MM-DD
```

sem conversão para `Date`, timestamp ou timezone.

## Importação

```js
import { AppDate } from "@/components/forms";
```

## Uso básico

```vue
<script setup>
import { ref } from "vue";

import { AppDate } from "@/components/forms";

const hearingDate = ref("2026-08-07");
</script>

<template>
  <AppDate
    v-model="hearingDate"
    id="hearing-date"
    name="hearing-date"
    label="Data da audiência"
  />
</template>
```

## Contrato do valor

O valor público é:

```ts
string;
```

no formato:

```text
YYYY-MM-DD
```

ou string vazia:

```text
''
```

Exemplos:

```js
"2026-08-07";
"2027-01-15";
"";
```

## Por que não usar Date?

`AppDate` não transforma o valor em:

```js
Date;
```

Isso é intencional.

Uma data civil como:

```text
2026-08-07
```

não representa necessariamente um instante no tempo.

Converter diretamente para:

```js
new Date("2026-08-07");
```

pode introduzir semântica de timezone e produzir diferenças de calendário dependendo do contexto em que o valor for manipulado.

O componente preserva exatamente:

```text
2026-08-07
```

## Arquitetura

Fluxo principal:

```text
AppDate
    ↓
InputVariant
    ↓
AppInput
    ↓
InputControl
```

`AppDate` especializa o campo sem duplicar a infraestrutura base.

## Configuração da variante

A implementação utiliza:

```js
{
  type: 'date',
  autocomplete: props.autocomplete,
  inputmode: undefined,
  icon: 'calendar',
  iconSize: 18,
  showIcon: props.showIcon,
}
```

Portanto:

```text
type     → date
icon     → calendar
iconSize → 18
```

## Props

`AppDate` reutiliza `appInputProps` e especializa:

| Prop           | Tipo               | Padrão      | Descrição                      |
| -------------- | ------------------ | ----------- | ------------------------------ |
| `modelValue`   | `String`           | `''`        | Data no formato `YYYY-MM-DD`   |
| `type`         | `String`           | `'date'`    | Tipo semântico da variante     |
| `autocomplete` | `String`           | `'off'`     | Configuração de autocomplete   |
| `min`          | `String`           | `undefined` | Menor data permitida           |
| `max`          | `String`           | `undefined` | Maior data permitida           |
| `step`         | `Number \| String` | `1`         | Incremento nativo do input     |
| `showIcon`     | `Boolean`          | `true`      | Controla o ícone de calendário |

Também herda props como:

```text
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
```

## min

`min` utiliza o mesmo formato do valor:

```text
YYYY-MM-DD
```

Exemplo:

```vue
<AppDate v-model="date" min="2026-08-01" />
```

## max

Exemplo:

```vue
<AppDate v-model="date" max="2026-12-31" />
```

## Intervalo

```vue
<AppDate v-model="date" min="2026-08-01" max="2026-12-31" />
```

é encaminhado ao DOM como:

```html
<input type="date" min="2026-08-01" max="2026-12-31" />
```

## step

O padrão é:

```js
1;
```

Exemplo:

```vue
<AppDate v-model="date" :step="2" />
```

O significado específico de `step` segue a semântica nativa de `<input type="date">`.

## Valor vazio

O componente aceita:

```js
"";
```

Exemplo:

```js
const date = ref("");
```

Nenhuma conversão automática para:

```js
null;
```

é realizada.

## v-model

Alterações do input são retransmitidas como texto.

Exemplo:

```text
usuário seleciona:
07/08/2026
```

A interface visual depende do navegador e locale, mas o valor DOM permanece:

```js
"2026-08-07";
```

e esse é o valor emitido pelo componente.

## Autocomplete

O padrão atual é:

```text
off
```

Pode ser configurado quando houver semântica apropriada.

Exemplo:

```vue
<AppDate autocomplete="bday" />
```

## Required

```vue
<AppDate v-model="date" id="date" label="Data" required />
```

O componente reutiliza a infraestrutura do `AppInput` para indicar obrigatoriedade.

## Hint

```vue
<AppDate
  v-model="date"
  id="date"
  label="Data"
  hint="Informe a data da audiência."
/>
```

## Erro

```vue
<AppDate
  v-model="date"
  id="date"
  label="Data"
  error="Informe uma data válida."
/>
```

Quando existe erro:

- o hint é ocultado;
- `aria-describedby` referencia a mensagem;
- `aria-invalid="true"` é aplicado ao input.

## Disabled

```vue
<AppDate v-model="date" disabled />
```

## Readonly

```vue
<AppDate v-model="date" readonly />
```

A disponibilidade e a experiência visual de `readonly` em `input[type="date"]` podem variar conforme o navegador, mas o atributo é encaminhado pelo componente.

## Slots

Os slots:

```text
prepend
append
```

são preservados.

Exemplo:

```vue
<AppDate v-model="date" label="Período">
  <template #prepend>
    Início
  </template>
</AppDate>
```

## Ícone

A variante utiliza:

```text
calendar
```

com tamanho:

```text
18
```

por padrão.

Para ocultar:

```vue
<AppDate :show-icon="false" />
```

## Formatação visual

`AppDate` não implementa uma camada própria de formatação como:

```text
DD/MM/YYYY
```

A representação visual do seletor nativo é controlada pelo navegador e pelo sistema operacional.

O contrato de dados continua sendo:

```text
YYYY-MM-DD
```

## Validação

O componente fornece suporte nativo a:

```text
required
min
max
step
```

mas regras de domínio permanecem externas.

Exemplos:

```text
data não pode cair em feriado
data precisa ser posterior à intimação
prazo deve respeitar dias úteis
data precisa ocorrer dentro do exercício
```

Essas regras não pertencem ao componente visual.

## Timezone

`AppDate` não executa:

```js
new Date(...)
Date.parse(...)
toISOString()
toLocaleDateString()
```

Isso impede que uma data sem horário adquira artificialmente um timezone.

Quando for necessário converter a data para um instante temporal, essa decisão deve ser tomada explicitamente pela camada de negócio.

## AppDate ou DatePicker?

`AppDate` utiliza o seletor nativo do navegador.

É adequado para a maioria dos formulários de cuidados que precisam apenas selecionar uma data.

Um futuro `DatePicker` customizado só deve ser introduzido quando houver requisitos que o controle nativo não consiga atender, como:

```text
calendário visual padronizado
bloqueio complexo de datas
múltiplas datas
intervalos avançados
navegação customizada
marcação de eventos
```

## Playground

Consulte:

```text
Playground → Forms → AppDate
```

Arquivo:

```text
src/views/playground/forms/DatePlayground.vue
```

## Testes

Os testes estão em:

```text
tests/components/forms/AppDate.spec.js
```

Cobertura atual:

```text
type date
label
YYYY-MM-DD
valor vazio
ausência de conversão de timezone
min
max
step
autocomplete
disabled
readonly
required
autofocus
hint
erro
aria-describedby
aria-invalid
focus
blur
slots
```
