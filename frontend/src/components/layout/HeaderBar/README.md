# HeaderBar

Componente estrutural do cabeçalho principal da aplicação.

`HeaderBar` organiza a região inicial e final do cabeçalho e integra o breadcrumb da rota atual.

## Importação

```js
import HeaderBar from "@/components/layout/HeaderBar/index.vue";
```

## Estrutura

```text
header.app-header.app-header-bar
├── div.app-header-bar__start
│   └── AppBreadcrumb
└── div.app-header-bar__end
```

## Semântica

O elemento raiz é:

```html
<header></header>
```

## Classes estruturais

A raiz utiliza:

```text
app-header
app-header-bar
```

`app-header` participa do grid definido em:

```text
src/assets/styles/layouts/app-layout.css
```

`app-header-bar` define o grupo visual interna em:

```text
src/assets/styles/components/header.css
```

## Região inicial

A região:

```text
app-header-bar__start
```

contém atualmente:

```text
AppBreadcrumb
```

## Região final

A região:

```text
app-header-bar__end
```

está reservada para ações futuras.

Exemplos possíveis:

```text
perfil
notificações
atalhos
ações contextuais
```

Esses recursos ainda não fazem parte do componente atual.

## Breadcrumb

`HeaderBar` delega a navegação contextual para:

```text
AppBreadcrumb
```

O breadcrumb reage automaticamente às mudanças de rota.

## Responsabilidades

```text
HeaderBar
→ estrutura do cabeçalho

AppBreadcrumb
→ contexto de navegação
```

## O que HeaderBar não faz

A versão atual não implementa:

```text
slots
ações por props
menu de usuário
notificações
busca
botão mobile
```

Esses comportamentos só devem ser introduzidos quando houver demanda real.

## Testes

Os testes estão em:

```text
tests/components/layout/HeaderBar.spec.js
```

A cobertura inclui:

```text
header
app-header
app-header-bar
região inicial
AppBreadcrumb
breadcrumb atual
reatividade à rota
região final
ordem estrutural
```
