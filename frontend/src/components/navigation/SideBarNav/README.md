# SideBarNav

Componente responsável por renderizar a navegação principal da sidebar.

`SideBarNav` utiliza a configuração central de menu definida em:

```text
src/config/menu.js
```

e transforma cada item em um `SideBarItem`.

## Importação

```js
import { SideBarNav } from "@/components/navigation";
```

## Uso básico

```vue
<SideBarNav />
```

O componente não recebe props nesta versão.

## Estrutura

```text
nav.sidebar-nav
├── SideBarItem
├── SideBarItem
└── ...
```

## Fonte dos dados

Os itens vêm de:

```text
src/config/menu.js
```

Exemplo:

```js
export default [
  {
    id: "dashboard",
    label: "Dashboard",
    name: "dashboard",
    icon: "dashboard",
  },
  {
    id: "playground",
    label: "Playground",
    name: "playground",
    icon: "playground",
  },
];
```

## Renderização

O componente utiliza:

```vue
<SideBarItem v-for="item in menuItems" :key="item.id" :item="item" />
```

Portanto, `item.id` é utilizado como chave de renderização.

## Navegação

A responsabilidade de construir o `RouterLink` pertence a:

```text
SideBarItem
```

`SideBarNav` apenas coordena a coleção de itens.

## Acessibilidade

O elemento raiz utiliza:

```html
<nav class="sidebar-nav" aria-label="Navegação principal"></nav>
```

Isso identifica semanticamente a região como navegação principal.

## CSS

A aparência é definida em:

```text
src/assets/styles/components/sidebar.css
```

Classe principal:

```text
.sidebar-nav
```

Os estilos dos itens pertencem a:

```text
.sidebar-item
.sidebar-item__icon
.sidebar-item__label
```

## Ícones

Os nomes de ícones definidos no menu devem existir no registry:

```text
src/icons/index.js
```

Exemplo:

```js
{
  icon: 'playground',
}
```

O `SideBarItem` encaminha esse valor para `AppIcon`.

## Estado ativo

O estado ativo é controlado pelo Vue Router através de:

```text
router-link-exact-active
```

A aparência desse estado pertence ao CSS global da sidebar.

## children

O menu atual pode conter:

```js
children: [];
```

mas `SideBarNav` não implementa navegação hierárquica nesta versão.

A propriedade não é consumida pelo componente atual.

## O que SideBarNav não faz

A versão atual não implementa:

```text
submenu
accordion
menu hierárquico
grupos
separadores
permissões
badges
collapse
busca
itens dinâmicos
menu remoto
```

Esses comportamentos devem ser introduzidos apenas quando existir necessidade concreta.

## Responsabilidades

```text
menu.js
→ define os dados

SideBarNav
→ percorre os dados

SideBarItem
→ renderiza cada link

AppIcon
→ renderiza o ícone
```

Essa separação evita acoplamento entre configuração, estrutura e apresentação.

## Uso atual

`SideBarNav` é consumido por:

```text
src/components/layout/SideBar.vue
```

Exemplo:

```vue
<SideBarNav />
```

## Testes

Os testes estão em:

```text
tests/components/navigation/SideBarNav.spec.js
```

A cobertura atual inclui:

```text
nav
classe sidebar-nav
aria-label
quantidade de itens
Dashboard
Playground
ícone dashboard
ícone playground
estado ativo dashboard
estado ativo playground
```
