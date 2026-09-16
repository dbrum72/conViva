# AppBreadcrumb

Componente de breadcrumb baseado no estado atual do Vue Router.

`AppBreadcrumb` deriva automaticamente os itens de navegação a partir de:

```text
route.matched
```

e utiliza a propriedade:

```text
meta.breadcrumb
```

de cada registro de rota como label.

## Importação

```js
import { AppBreadcrumb } from "@/components/navigation";
```

## Uso básico

```vue
<AppBreadcrumb />
```

O componente não recebe props nesta versão.

Os itens são derivados da rota atual.

## Estrutura

```text
nav.breadcrumb
└── ol.breadcrumb__list
    ├── li.breadcrumb__item
    │   └── RouterLink
    └── li.breadcrumb__item
        └── span.breadcrumb__current
```

## Fonte dos dados

A lógica utiliza:

```js
route.matched
  .filter((record) => record.meta?.breadcrumb)
  .map((record) => ({
    label: record.meta.breadcrumb,
    name: record.name,
  }));
```

Portanto, apenas registros com:

```js
meta: {
  breadcrumb: '...',
}
```

participam do breadcrumb.

## Configuração de rota

Exemplo:

```js
{
  path: '/clients',
  name: 'clients',
  component: ClientsPage,
  meta: {
    breadcrumb: 'Clientes',
  },
}
```

Uma rota filha pode declarar:

```js
{
  path: ':id',
  name: 'client-show',
  component: ClientPage,
  meta: {
    breadcrumb: 'Cliente',
  },
}
```

Ao navegar para:

```text
/clients/10
```

o breadcrumb será:

```text
Clientes / Cliente
```

## Itens intermediários

Itens intermediários são renderizados como `RouterLink`.

Exemplo:

```text
Clientes
```

aponta para:

```js
{
  name: 'clients',
}
```

## Item atual

O último item não é transformado em link.

Ele é renderizado como:

```html
<span class="breadcrumb__current" aria-current="page"> Cliente </span>
```

Isso evita criar um link redundante para a própria página atual.

## Registros sem breadcrumb

Rotas sem:

```js
meta.breadcrumb;
```

são ignoradas.

Exemplo:

```js
{
  path: '/clients',
  name: 'clients',
}
```

não gera item no breadcrumb.

## Breadcrumb vazio

Se nenhum registro da rota atual possuir `meta.breadcrumb`, o componente mantém:

```html
<nav class="breadcrumb">
  <ol class="breadcrumb__list"></ol>
</nav>
```

Nenhum item é renderizado.

## Reatividade

O breadcrumb acompanha automaticamente mudanças de rota porque utiliza:

```js
useRoute();
```

e um:

```js
computed();
```

Ao navegar para outra rota, os itens são recalculados.

## Acessibilidade

O elemento raiz utiliza:

```html
<nav class="breadcrumb" aria-label="Breadcrumb"></nav>
```

O item atual utiliza:

```text
aria-current="page"
```

Essas decisões seguem o padrão semântico esperado para breadcrumbs.

## CSS

A aparência é centralizada em:

```text
src/assets/styles/components/breadcrumb.css
```

Classes utilizadas:

```text
.breadcrumb
.breadcrumb__list
.breadcrumb__item
.breadcrumb__link
.breadcrumb__current
```

O componente não possui estilos próprios nesta versão.

## Contrato de rota

`AppBreadcrumb` depende atualmente de duas propriedades dos registros do Vue Router:

```text
record.name
record.meta.breadcrumb
```

`record.name` é usado como destino dos links intermediários.

`record.meta.breadcrumb` é usado como label.

## Limitação atual

O breadcrumb trabalha apenas com labels estáticos definidos em `meta`.

Exemplo:

```js
meta: {
  breadcrumb: 'Cliente',
}
```

Ele não resolve automaticamente valores dinâmicos como:

```text
João da Silva
Processo 5001234-56.2026
Contrato ABC
```

Se surgir necessidade de breadcrumbs dinâmicos, o contrato deverá ser ampliado explicitamente.

## O que AppBreadcrumb não faz

A versão atual não implementa:

```text
props de items
labels assíncronas
breadcrumbs dinâmicos
ícones
menu
dropdown
overflow
compactação
home automático
separador configurável
```

Esses recursos só devem ser adicionados quando houver necessidade concreta.

## Uso no layout

Atualmente é consumido por:

```text
src/components/layout/HeaderBar.vue
```

Exemplo:

```vue
<AppBreadcrumb />
```

## Testes

Os testes estão em:

```text
tests/components/navigation/AppBreadcrumb.spec.js
```

A cobertura atual inclui:

```text
nav
aria-label
lista ordenada
breadcrumb da rota atual
aria-current
último item sem link
múltiplos níveis
links intermediários
registros sem meta.breadcrumb
lista vazia
record.name como destino
reatividade à navegação
```
