# SideBar

Componente estrutural da navegação lateral principal da aplicação.

`SideBar` reúne a marca da aplicação, a navegação principal e a região de rodapé da sidebar.

## Importação

```js
import SideBar from "@/components/layout/SideBar/index.vue";
```

## Estrutura

```text
aside.app-sidebar.app-sidebar-nav
├── header.sidebar-header
│   └── AppLogo
├── SideBarNav
└── footer.sidebar-footer
```

## Semântica

O elemento raiz é:

```html
<aside></aside>
```

Isso representa corretamente uma região complementar de navegação da interface.

## Classes estruturais

A raiz utiliza simultaneamente:

```text
app-sidebar
app-sidebar-nav
```

`app-sidebar` participa do grid principal definido em:

```text
src/assets/styles/layouts/app-layout.css
```

`app-sidebar-nav` define a estrutura interna da sidebar em:

```text
src/assets/styles/components/sidebar.css
```

## Header

A região superior utiliza:

```text
sidebar-header
```

e atualmente contém:

```text
AppLogo
```

## Navegação

A navegação principal é delegada para:

```text
SideBarNav
```

O `SideBar` não conhece diretamente os itens do menu.

## Footer

A região inferior é:

```text
sidebar-footer
```

Atualmente está vazia e reservada para funcionalidades futuras.

## Responsabilidades

```text
SideBar
→ estrutura

AppLogo
→ marca e navegação inicial

SideBarNav
→ navegação principal
```

## O que SideBar não faz

A versão atual não implementa:

```text
collapse
drawer mobile
resize
estado aberto/fechado
menu dinâmico
footer customizado por props
```

Esses comportamentos só devem ser adicionados quando houver necessidade concreta.

## Testes

Os testes estão em:

```text
tests/components/layout/SideBar.spec.js
```

A cobertura inclui:

```text
aside
app-sidebar
app-sidebar-nav
header
AppLogo
SideBarNav
footer
ordem estrutural
```
