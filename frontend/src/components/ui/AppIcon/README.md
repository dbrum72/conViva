# AppIcon

Componente de ícone do Design System, baseado no registry central definido em:

```text
src/icons/index.js
```

`AppIcon` resolve um nome semântico para um componente de ícone e centraliza tamanho, espessura de traço e comportamento de acessibilidade.

## Importação

```js
import { AppIcon } from "@/components/ui";
```

## Uso básico

```vue
<AppIcon name="save" />
```

## Registry

Os ícones disponíveis são registrados em:

```text
src/icons/index.js
```

Exemplo:

```js
export const icons = {
  save: Save,
  "arrow-right": ArrowRight,
  file: FileText,
};
```

O consumidor utiliza apenas a chave semântica:

```vue
<AppIcon name="arrow-right" />
```

## Props

| Prop          | Tipo               | Padrão      | Descrição                                      |
| ------------- | ------------------ | ----------- | ---------------------------------------------- |
| `name`        | `String`           | obrigatório | Nome registrado do ícone                       |
| `size`        | `Number \| String` | `20`        | Largura e altura                               |
| `strokeWidth` | `Number \| String` | `2`         | Espessura do traço                             |
| `decorative`  | `Boolean`          | `true`      | Define se o ícone é apenas decorativo          |
| `label`       | `String`           | `undefined` | Nome acessível quando o ícone não é decorativo |

## Tamanho

```vue
<AppIcon name="save" :size="24" />
```

O tamanho é encaminhado ao componente de ícone.

## Stroke width

```vue
<AppIcon name="save" :stroke-width="1.5" />
```

O padrão é:

```text
2
```

## Ícone decorativo

Por padrão:

```js
decorative = true;
```

O componente aplica:

```text
aria-hidden="true"
```

e não fornece `aria-label`.

Isso é adequado quando o ícone acompanha texto ou não transmite informação essencial.

Exemplo:

```vue
<AppButton icon="save">
  Salvar
</AppButton>
```

Nesse caso, o texto do botão fornece a semântica.

## Ícone semântico

Quando o próprio ícone transmite informação:

```vue
<AppIcon name="settings" :decorative="false" label="Configurações" />
```

o componente utiliza:

```text
aria-hidden="false"
aria-label="Configurações"
```

## Fallback

Quando `name` não existe no registry:

```js
icons[props.name] ?? icons.file;
```

o componente utiliza:

```text
file
```

como fallback.

Exemplo:

```vue
<AppIcon name="icone-inexistente" />
```

renderiza o ícone registrado como:

```text
file
```

Esse comportamento evita quebra visual em runtime, mas nomes inválidos devem ser corrigidos na origem.

## Ícones atualmente registrados

Consulte sempre:

```text
src/icons/index.js
```

O registry é a fonte de verdade.

Entre os nomes atualmente utilizados pelo Design System estão:

```text
dashboard
users
user
scale
calendar
wallet
file
settings
search
eye
eye-off
close
clear
email
phone
link
save
arrow-right
```

## Adicionando um ícone

Importe o componente em:

```text
src/icons/index.js
```

Exemplo:

```js
import { Download } from "@lucide/vue";
```

e registre uma chave semântica:

```js
export const icons = {
  ...,
  download: Download,
}
```

Depois:

```vue
<AppIcon name="download" />
```

Prefira nomes semânticos e estáveis.

## Reatividade

Alterações em `name` são refletidas automaticamente:

```vue
<AppIcon :name="currentIcon" />
```

porque a resolução utiliza um `computed`.

## Arquitetura

Fluxo:

```text
AppIcon
   ↓
icons[name]
   ↓
@lucide/vue
```

O componente consumidor não precisa importar diretamente ícones do Lucide.

## Benefícios do registry central

O registry evita:

```text
imports duplicados
nomes inconsistentes
dependência direta de Lucide nas views
troca de biblioteca espalhada pela aplicação
```

Views e componentes devem preferir:

```text
AppIcon
```

em vez de importar diretamente componentes de `@lucide/vue`.

## Acessibilidade

Regra prática:

```text
ícone + texto
→ decorative=true

ícone sozinho com significado
→ decorative=false + label
```

Em botões somente com ícone, a semântica normalmente deve ficar no próprio botão através de `aria-label`.

## Playground

`AppIcon` ainda não possui Playground próprio nesta versão.

Ele já aparece indiretamente em vários componentes, principalmente:

```text
AppButton
AppSearch
AppPassword
InputVariant
SideBarItem
```

Um Playground dedicado só deve ser criado se houver necessidade de catálogo visual do registry.

## Testes

Os testes estão em:

```text
tests/components/ui/AppIcon.spec.js
```

A cobertura atual inclui:

```text
renderização SVG
save
arrow-right
fallback file
size
strokeWidth
decorative
label
aria-hidden
aria-label
reatividade do name
```
