# SwitchControl

Controle booleano baseado em `<input type="checkbox" role="switch">`.

`SwitchControl` é um componente interno do Design System. Ele concentra a semântica e o comportamento nativo de um switch acessível, enquanto `AppSwitch` adiciona label, hint, erro e demais elementos de apresentação.

## Responsabilidade

O componente é responsável por:

- renderizar um checkbox nativo com `role="switch"`;
- refletir `modelValue` no estado `checked`;
- manter `aria-checked` sincronizado com o valor atual;
- emitir alterações booleanas;
- encaminhar estados nativos;
- encaminhar atributos ARIA;
- emitir eventos de foco e blur;
- fornecer a apresentação visual do switch.

## Arquitetura

Fluxo típico:

```text
AppSwitch
    ↓
SwitchControl
```

O `AppSwitch` adiciona:

- label;
- hint;
- mensagem de erro;
- indicador obrigatório;
- classes de estado;
- cálculo de `aria-describedby`;
- cálculo de `aria-invalid`.

## Uso interno

```vue
<SwitchControl v-model="enabled" id="notifications" name="notifications" />
```

Na aplicação, prefira:

```text
AppSwitch
```

## Props

| Prop              | Tipo                | Padrão      | Descrição                         |
| ----------------- | ------------------- | ----------- | --------------------------------- |
| `modelValue`      | `Boolean`           | `false`     | Estado atual do switch            |
| `id`              | `String`            | `undefined` | Identificador do controle         |
| `name`            | `String`            | `undefined` | Nome do controle                  |
| `disabled`        | `Boolean`           | `false`     | Desabilita o switch               |
| `required`        | `Boolean`           | `false`     | Marca o controle como obrigatório |
| `autofocus`       | `Boolean`           | `false`     | Solicita foco automático          |
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

Portanto, `update:modelValue` sempre emite:

```js
true;
```

ou:

```js
false;
```

Não existe conversão textual.

## Semântica

O elemento renderizado é:

```html
<input type="checkbox" role="switch" />
```

O uso de um checkbox nativo preserva:

- comportamento por teclado;
- foco nativo;
- integração com formulários;
- semântica compreendida pelo navegador.

`role="switch"` comunica às tecnologias assistivas que o controle representa um estado ligado/desligado, e não apenas uma seleção genérica.

## aria-checked

O estado semântico do switch é exposto por:

```text
aria-checked
```

Exemplo ativado:

```html
<input type="checkbox" role="switch" aria-checked="true" />
```

Exemplo desativado:

```html
<input type="checkbox" role="switch" aria-checked="false" />
```

O atributo acompanha reativamente `modelValue`.

## checked

O estado visual nativo é controlado por:

```vue
:checked="modelValue"
```

Assim:

```js
modelValue = true;
```

produz:

```text
checked = true
aria-checked = "true"
```

e:

```js
modelValue = false;
```

produz:

```text
checked = false
aria-checked = "false"
```

## Reatividade

Mudanças em `modelValue` atualizam tanto:

```text
checked
```

quanto:

```text
aria-checked
```

Exemplo:

```js
modelValue = false;
```

após:

```js
modelValue = true;
```

o elemento passa automaticamente para o estado ligado.

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
<input
  type="checkbox"
  role="switch"
  aria-checked="false"
  aria-invalid="true"
  aria-describedby="notifications-error"
/>
```

A responsabilidade de calcular hint, erro e IDs pertence ao `AppSwitch`.

## Estados nativos

O componente encaminha diretamente:

```text
disabled
required
autofocus
```

## Foco

O switch possui foco visual através de:

```css
.switch-control:focus-visible {
  outline: none;
  box-shadow: var(--focus-ring);
}
```

O anel de foco deve permanecer perceptível para navegação por teclado.

## Aparência

O switch utiliza:

```css
appearance: none;
```

para substituir a aparência padrão do checkbox por uma representação visual própria.

A trilha utiliza tokens do Design System:

```text
--color-surface-muted
--color-border-strong
--color-accent
--radius-full
```

O indicador interno é criado com:

```css
.switch-control::before
```

Quando ativado, ele é deslocado para a posição oposta.

## Motion

As transições utilizam tokens compartilhados:

```text
--duration-fast
--ease-standard
```

Isso mantém o movimento consistente com o restante do Design System.

## Disabled

Quando desabilitado:

```css
opacity: 0.65;
cursor: not-allowed;
```

A semântica nativa também é preservada através do atributo:

```html
disabled
```

## AppSwitch ou AppCheckbox?

Embora ambos utilizem valores booleanos, possuem semânticas diferentes.

### SwitchControl / AppSwitch

Use quando a interação altera imediatamente um estado ou configuração:

```text
Ativar notificações
Salvar automaticamente
Habilitar sincronização
Permitir atualizações automáticas
```

### CheckboxControl / AppCheckbox

Use para seleção, confirmação ou consentimento:

```text
Aceito os termos
Selecionar registro
Confirmo que li
Incluir anexo
```

A diferença é semântica, não apenas visual.

## Boas práticas

Utilize `SwitchControl` apenas como infraestrutura interna.

Na aplicação, prefira:

```text
AppSwitch
```

O texto apresentado pela camada pública deve descrever claramente o estado controlado.

Prefira:

```text
Ativar notificações
Salvar automaticamente
```

Evite:

```text
Sim
Não
Ativo
Opção
```

## Testes

Os testes estão em:

```text
tests/components/forms/controls/SwitchControl.spec.js
```

Cobertura atual:

```text
renderização
role switch
checked
aria-checked
disabled
required
autofocus
aria-invalid
aria-describedby
v-model
focus
blur
reatividade de modelValue
```
