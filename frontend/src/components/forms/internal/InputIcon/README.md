# InputIcon

Wrapper visual interno para ícones utilizados nos controles de formulário.

> Componente de infraestrutura interna do Design System. Não deve ser utilizado diretamente pelas views da aplicação.

## Responsabilidade

`InputIcon` fornece um contêiner visual padronizado para ícones inseridos em campos e controles.

Sua estrutura é:

```vue
<span class="input-icon" aria-hidden="true">
  <slot />
</span>
```

## Uso interno

Exemplo:

```vue
<InputIcon>
  <AppIcon
    name="search"
    :size="18"
  />
</InputIcon>
```

É utilizado por componentes de formulário que precisam inserir ícones em regiões como `prepend` ou `append`.

## Slot

O componente possui apenas o slot padrão:

```text
default
```

O conteúdo normalmente é um componente de ícone.

## Acessibilidade

O wrapper utiliza:

```html
aria-hidden="true"
```

Portanto, seu conteúdo é tratado como decorativo pelas tecnologias assistivas.

`InputIcon` não deve ser utilizado para transmitir sozinho informação essencial ao usuário.

Quando o ícone representar uma ação interativa, utilize a infraestrutura apropriada, como:

```text
InputIconButton
```

com um `aria-label` significativo.

## API pública

O componente não possui props nem eventos próprios.

Seu contrato é deliberadamente mínimo.

## Escopo

`InputIcon` pertence à camada:

```text
src/components/forms/internal/
```

Isso significa que é um detalhe de implementação do Design System.

Componentes públicos devem reutilizá-lo internamente quando necessário, mas views e funcionalidades da aplicação não devem depender diretamente dele.
