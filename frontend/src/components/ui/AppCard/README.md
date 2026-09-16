# AppCard

Container estrutural do Design System para agrupar conteúdo relacionado.

`AppCard` encapsula o contrato visual global definido em:

```text
src/assets/styles/components/card.css
```

e adiciona uma API Vue baseada em slots para header, body e footer.

## Importação

```js
import { AppCard } from "@/components/ui";
```

## Uso básico

```vue
<AppCard>
  Conteúdo do card.
</AppCard>
```

Por padrão, o componente é renderizado como:

```html
<section></section>
```

## Com título

```vue
<AppCard title="Processo">
  Informações principais do processo.
</AppCard>
```

Quando `title` está definido, o componente renderiza automaticamente:

```html
<div class="card__header">
  <h2 class="card__title">Processo</h2>
</div>
```

## Arquitetura

A estrutura visual segue:

```text
card
├── card__header
├── card__body
└── card__footer
```

O body é sempre renderizado.

Header e footer são condicionais.

## Props

| Prop      | Tipo                                   | Padrão      | Descrição             |
| --------- | -------------------------------------- | ----------- | --------------------- |
| `title`   | `String`                               | `''`        | Título padrão do card |
| `variant` | `'default' \| 'accent' \| 'highlight'` | `'default'` | Variante visual       |
| `as`      | `String`                               | `'section'` | Elemento HTML raiz    |

## Variantes

As variantes seguem diretamente o CSS global existente.

### Default

```vue
<AppCard>
  Conteúdo
</AppCard>
```

Utiliza apenas:

```text
.card
```

### Accent

```vue
<AppCard variant="accent">
  Conteúdo
</AppCard>
```

Aplica:

```text
.card--accent
```

O CSS global adiciona uma borda superior utilizando:

```text
--color-accent
```

### Highlight

```vue
<AppCard variant="highlight">
  Conteúdo
</AppCard>
```

Aplica:

```text
.card--highlight
```

O destaque utiliza:

```text
--color-highlight
```

## Relação com o CSS global

A aparência principal pertence a:

```text
src/assets/styles/components/card.css
```

O componente não duplica esses estilos.

O CSS global define:

```text
.card
.card__header
.card__body
.card__footer
.card__title
.card--accent
.card--highlight
```

`AppCard` apenas organiza a estrutura e aplica as classes corretas.

## Elemento raiz

Por padrão:

```vue
<AppCard>
```

renderiza:

```html
<section></section>
```

É possível alterar:

```vue
<AppCard as="article">
  ...
</AppCard>
```

resultando em:

```html
<article class="card"></article>
```

Outros elementos podem ser utilizados quando semanticamente apropriados.

## Slot padrão

O conteúdo principal é renderizado em:

```text
card__body
```

Exemplo:

```vue
<AppCard>
  <p>Conteúdo principal.</p>
</AppCard>
```

## Header

O header pode surgir de duas formas.

### Via title

```vue
<AppCard title="Cliente">
  ...
</AppCard>
```

### Via slot

```vue
<AppCard>
  <template #header>
    <strong>Cliente</strong>
  </template>

  ...
</AppCard>
```

Quando o slot `header` é fornecido, ele substitui o título automático.

## Header customizado

```vue
<AppCard>
  <template #header>
    <div>
      <strong>Cliente</strong>

      <AppButton
        size="sm"
        variant="ghost"
      >
        Editar
      </AppButton>
    </div>
  </template>

  Dados do cliente.
</AppCard>
```

Isso permite compor ações sem incorporar lógica específica de ações ao `AppCard`.

## Footer

O footer é renderizado apenas quando existe slot correspondente:

```vue
<AppCard title="Documento">
  Informações do documento.

  <template #footer>
    <AppButton>
      Salvar
    </AppButton>
  </template>
</AppCard>
```

A estrutura resultante utiliza:

```text
card__footer
```

## Header, body e footer

Quando todos estão presentes, a ordem é sempre:

```text
header
body
footer
```

Essa estrutura é protegida pelos testes do componente.

## Semântica

`AppCard` é um container visual, não uma semântica universal.

Escolha `as` conforme o conteúdo.

Exemplos:

```text
section → agrupamento temático
article → conteúdo independente
div     → agrupamento puramente estrutural
```

Evite escolher elementos apenas por aparência.

## Título

O título automático utiliza atualmente:

```html
<h2></h2>
```

Isso funciona bem quando o card representa uma subseção da página.

Se a hierarquia semântica exigir outro nível de heading, utilize o slot `header` e forneça explicitamente o elemento adequado.

Exemplo:

```vue
<AppCard>
  <template #header>
    <h3 class="card__title">
      Subtítulo
    </h3>
  </template>

  ...
</AppCard>
```

## Composição com AppButton

`AppCard` não possui props específicas de ações.

Ações devem ser compostas através de slots:

```vue
<AppCard title="Documento">
  Conteúdo.

  <template #footer>
    <AppButton variant="outline">
      Cancelar
    </AppButton>

    <AppButton>
      Salvar
    </AppButton>
  </template>
</AppCard>
```

Isso mantém responsabilidades separadas:

```text
AppCard   → estrutura
AppButton → ação
```

## O que AppCard não faz

A versão atual não implementa:

```text
clickable
selected
loading
collapsible
expandable
href
router-link
menu
actions prop
```

Esses comportamentos devem ser adicionados por composição ou por componentes especializados quando existir necessidade concreta.

## Acessibilidade

`AppCard` não adiciona ARIA artificialmente.

Ele preserva a semântica do elemento escolhido através de:

```text
as
```

e permite que o consumidor defina headings e controles adequados através dos slots.

## Boas práticas

Para conteúdo simples:

```vue
<AppCard title="Resumo">
  ...
</AppCard>
```

Para conteúdo independente:

```vue
<AppCard as="article" title="Publicação">
  ...
</AppCard>
```

Para ações:

```vue
<AppCard title="Processo">
  ...

  <template #footer>
    <AppButton>
      Abrir processo
    </AppButton>
  </template>
</AppCard>
```

## Playground

Consulte:

```text
Playground → UI → AppCard
```

Arquivo:

```text
src/views/playground/ui/CardPlayground.vue
```

## Testes

Os testes estão em:

```text
tests/components/ui/AppCard.spec.js
```

A cobertura atual inclui:

```text
elemento raiz
classe base
default
accent
highlight
body
slot padrão
title
h2
header
substituição do title pelo slot
footer
ordem header/body/footer
```
