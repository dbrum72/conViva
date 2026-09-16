# InputIconButton

Botão interno para ações representadas visualmente por ícones dentro dos controles de formulário.

> Componente de infraestrutura interna do Design System. Não deve ser utilizado diretamente pelas views da aplicação.

## Responsabilidade

`InputIconButton` padroniza ações compactas baseadas em ícones utilizadas por componentes de formulário.

Exemplos de uso incluem:

```text
mostrar senha
ocultar senha
limpar pesquisa
```

Sua estrutura utiliza um elemento nativo:

```html
<button type="button"></button>
```

## Uso interno

Exemplo:

```vue
<InputIconButton aria-label="Limpar pesquisa" @click="clear">
  <InputIcon>
    <AppIcon
      name="close"
      :size="18"
    />
  </InputIcon>
</InputIconButton>
```

## Props

| Prop        | Tipo      | Obrigatória | Padrão  | Descrição              |
| ----------- | --------- | ----------- | ------- | ---------------------- |
| `ariaLabel` | `String`  | sim         | —       | Nome acessível da ação |
| `disabled`  | `Boolean` | não         | `false` | Desabilita o botão     |

## ariaLabel

`ariaLabel` é obrigatório:

```js
ariaLabel: {
  type: String,
  required: true,
}
```

Ele é encaminhado para:

```html
aria-label
```

Exemplo:

```vue
<InputIconButton aria-label="Mostrar senha">
  ...
</InputIconButton>
```

Como o conteúdo visual normalmente é apenas um ícone, o `aria-label` fornece o significado da ação às tecnologias assistivas.

## disabled

O estado desabilitado é encaminhado diretamente ao botão nativo:

```vue
:disabled="disabled"
```

O padrão é:

```js
false;
```

Exemplo:

```vue
<InputIconButton aria-label="Limpar pesquisa" disabled>
  ...
</InputIconButton>
```

## Evento click

O componente declara:

```text
click
```

e retransmite o evento nativo:

```vue
@click="$emit('click', $event)"
```

Uso:

```vue
<InputIconButton aria-label="Mostrar senha" @click="toggleVisibility">
  ...
</InputIconButton>
```

## Slot

O componente possui um slot padrão:

```text
default
```

Normalmente utilizado com:

```text
InputIcon
    ↓
AppIcon
```

Exemplo:

```vue
<InputIconButton aria-label="Ocultar senha">
  <InputIcon>
    <AppIcon
      name="eye-off"
      :size="18"
    />
  </InputIcon>
</InputIconButton>
```

## Semântica HTML

O componente utiliza explicitamente:

```html
type="button"
```

Isso evita que o botão dispare acidentalmente o `submit` quando estiver dentro de um `<form>`.

## Acessibilidade

Toda instância exige um nome acessível através de:

```text
ariaLabel
```

O conteúdo visual não deve ser usado como única fonte semântica da ação.

Exemplos adequados:

```text
Mostrar senha
Ocultar senha
Limpar pesquisa
```

Evite labels genéricas como:

```text
Ícone
Botão
Ação
```

## Escopo

`InputIconButton` pertence à camada:

```text
src/components/forms/internal/
```

É um detalhe de implementação do Design System e não constitui componente público para consumo direto pelas views.

Componentes públicos, como variantes de formulário, podem reutilizá-lo para oferecer ações iconográficas consistentes.
