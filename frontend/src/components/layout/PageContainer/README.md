# PageContainer

Container estrutural de conteúdo das páginas da aplicação.

`PageContainer` centraliza largura máxima, espaçamento horizontal e espaçamento vertical através da classe global `page`.

## Importação

```js
import { PageContainer } from "@/components/layout";
```

## Uso básico

```vue
<PageContainer>
  Conteúdo da página
</PageContainer>
```

## Estrutura

```text
section.page
└── slot default
```

## Semântica

O elemento raiz é:

```html
<section></section>
```

Isso representa uma região de conteúdo da página.

## CSS

A aparência é definida em:

```text
src/assets/styles/layouts/page-layout.css
```

A classe:

```text
.page
```

define atualmente:

```text
width
max-width
margin-inline
padding-inline
padding-block
```

## Slot padrão

Todo o conteúdo é fornecido pelo slot padrão:

```vue
<PageContainer>
  <h1>Título</h1>
  <p>Conteúdo</p>
</PageContainer>
```

## Responsabilidade

`PageContainer` não implementa lógica de domínio.

Sua responsabilidade é apenas:

```text
conter
centralizar
limitar largura
aplicar espaçamento
```

## Uso atual

Atualmente é utilizado em páginas como:

```text
DashboardPage
PlaygroundPage
```

## Registro global

`PageContainer` não deve depender de registro global.

Prefira import explícito:

```js
import { PageContainer } from "@/components/layout";
```

ou:

```js
import PageContainer from "@/components/layout/PageContainer/index.vue";
```

## O que PageContainer não faz

A versão atual não implementa:

```text
header
footer
toolbar
breadcrumb
grid
loading
scroll
variants
larguras configuráveis
```

Essas responsabilidades pertencem a outros componentes.

## Testes

Os testes estão em:

```text
tests/components/layout/PageContainer.spec.js
```

A cobertura atual inclui:

```text
section
classe page
slot padrão
preservação de elementos
slot vazio
```
