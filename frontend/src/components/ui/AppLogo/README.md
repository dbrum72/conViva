# AppLogo

Componente de marca da aplicação.

`AppLogo` renderiza a identidade textual do sistema como um `RouterLink`, mantendo como destino padrão a rota `dashboard`.

## Importação

```js
import { AppLogo } from "@/components/ui";
```

## Uso básico

```vue
<AppLogo />
```

Por padrão:

```text
text      → conViva
to        → { name: 'dashboard' }
ariaLabel → conViva — ir para o início
```

## Estrutura

O componente utiliza:

```text
RouterLink
└── app-logo__text
```

O estilo visual é definido globalmente em:

```text
src/assets/styles/components/logo.css
```

## Props

| Prop        | Tipo               | Padrão                         | Descrição               |
| ----------- | ------------------ | ------------------------------ | ----------------------- |
| `text`      | `String`           | `'conViva'`                    | Texto visual da marca   |
| `to`        | `String \| Object` | `{ name: 'dashboard' }`        | Destino do `RouterLink` |
| `ariaLabel` | `String`           | `'conViva — ir para o início'` | Nome acessível do link  |

## Texto

```vue
<AppLogo text="conViva" />
```

Também pode ser personalizado:

```vue
<AppLogo text="Portal de Cuidados" />
```

## Destino

O destino padrão é:

```js
{
  name: 'dashboard',
}
```

Pode ser alterado com objeto:

```vue
<AppLogo :to="{ name: 'home' }" />
```

ou string:

```vue
<AppLogo to="/home" />
```

## Acessibilidade

O componente aplica `aria-label` diretamente ao link.

Por padrão:

```text
conViva — ir para o início
```

É possível personalizar:

```vue
<AppLogo aria-label="Voltar para o painel principal" />
```

O texto visual continua disponível dentro do link através de:

```text
app-logo__text
```

## Navegação

`AppLogo` mantém intencionalmente a navegação dentro do próprio componente.

Isso reflete o comportamento esperado de uma marca de aplicação: clicar no logo deve levar o usuário à página inicial ou ao painel principal.

A rota, porém, não é rígida. Ela pode ser substituída através da prop `to`.

## CSS

A aparência é centralizada em:

```text
src/assets/styles/components/logo.css
```

Classes utilizadas:

```text
.app-logo
.app-logo__text
```

O componente não possui `style.css` próprio.

## Uso no layout

Atualmente o componente é utilizado no:

```text
src/components/layout/SideBar.vue
```

Exemplo:

```vue
<header class="sidebar-header">
  <AppLogo />
</header>
```

## O que AppLogo não faz

A versão atual não implementa:

```text
imagem
símbolo
ícone
modo compacto
variante
tamanho
tema próprio
logo responsivo
```

Esses recursos só devem ser adicionados caso surja uma necessidade concreta de produto.

## Boas práticas

Para o comportamento padrão:

```vue
<AppLogo />
```

Para outro destino:

```vue
<AppLogo :to="{ name: 'home' }" />
```

Para outro nome acessível:

```vue
<AppLogo aria-label="Ir para a página inicial" />
```

## Testes

Os testes estão em:

```text
tests/components/ui/AppLogo.spec.js
```

A cobertura atual inclui:

```text
RouterLink
texto padrão
texto customizado
classe base
destino dashboard
destino por objeto
destino por string
aria-label padrão
aria-label customizado
navegação por clique
```
