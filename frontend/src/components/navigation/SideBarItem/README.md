# SideBarItem

Unidade navegacional da sidebar principal.

`SideBarItem` recebe um objeto `item` e o transforma em um `RouterLink` com label e ícone opcional.

## Importação

```js
import { SideBarItem } from "@/components/navigation";
```

## Uso básico

```vue
<SideBarItem
  :item="{
    id: 'dashboard',
    name: 'dashboard',
    label: 'Dashboard',
    icon: 'dashboard',
  }"
/>
```

## Contrato do item

O componente exige minimamente:

```js
{
  name: String,
  label: String,
}
```

Exemplo:

```js
{
  name: 'dashboard',
  label: 'Dashboard',
}
```

`icon` é opcional:

```js
{
  name: 'dashboard',
  label: 'Dashboard',
  icon: 'dashboard',
}
```

## Prop

| Prop   | Tipo     | Obrigatória | Descrição                         |
| ------ | -------- | ----------- | --------------------------------- |
| `item` | `Object` | sim         | Configuração do item de navegação |

O validator atual exige:

```js
typeof item.name === "string";
typeof item.label === "string";
```

## Destino

O destino é construído com:

```js
{
  name: item.name,
}
```

Portanto, `item.name` deve corresponder a uma rota nomeada do Vue Router.

Exemplo:

```js
{
  name: 'playground',
  label: 'Playground',
}
```

gera um link para:

```js
{
  name: 'playground',
}
```

## Label

A label é renderizada em:

```html
<span class="sidebar-item__label"> ... </span>
```

## Ícone

Quando `item.icon` existe:

```vue
<AppIcon class="sidebar-item__icon" :name="item.icon" :size="20" />
```

O nome deve existir no registry central:

```text
src/icons/index.js
```

Exemplo:

```js
{
  icon: 'dashboard',
}
```

## Item sem ícone

`icon` não é obrigatório.

```js
{
  name: 'settings',
  label: 'Configurações',
}
```

Nesse caso, apenas a label é renderizada.

## Estado ativo

Como o componente utiliza `RouterLink`, o Vue Router aplica automaticamente:

```text
router-link-exact-active
```

quando a rota corresponde exatamente ao item.

O CSS global utiliza essa classe para destacar o item ativo.

## CSS

A aparência pertence a:

```text
src/assets/styles/components/sidebar.css
```

Classes utilizadas:

```text
.sidebar-item
.sidebar-item__icon
.sidebar-item__label
.router-link-exact-active
```

O componente não possui `style.css` próprio.

## Tokens visuais

O estado ativo utiliza:

```text
--color-brand
--color-surface-accent
```

O hover utiliza:

```text
--color-surface-muted
```

Esses tokens pertencem ao tema atual do Design System.

## Acessibilidade

O componente preserva a semântica nativa de link por meio de `RouterLink`.

O ícone é decorativo por padrão através de `AppIcon`, enquanto a label textual fornece o nome acessível do link.

## Fonte de dados atual

Atualmente `SideBarItem` é consumido por:

```text
SideBarNav
```

que utiliza:

```text
src/config/menu.js
```

como fonte de itens.

Exemplo:

```js
{
  id: 'dashboard',
  label: 'Dashboard',
  name: 'dashboard',
  icon: 'dashboard',
}
```

## id e children

Embora `menu.js` possa conter propriedades como:

```text
id
children
```

`SideBarItem` não depende delas nesta versão.

Seu contrato funcional atual utiliza apenas:

```text
name
label
icon
```

## O que SideBarItem não faz

A versão atual não implementa:

```text
submenu
children
badge
tooltip
disabled
external link
target
permission
collapse
nested navigation
```

Esses comportamentos só devem ser adicionados quando houver necessidade concreta na navegação.

## Testes

Os testes estão em:

```text
tests/components/navigation/SideBarItem.spec.js
```

A cobertura atual inclui:

```text
RouterLink
classe base
label
destino
ícone
ausência de ícone
ícone playground
estado ativo
estado inativo
navegação por clique
```
