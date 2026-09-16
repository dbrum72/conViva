# AppButton

Botão base do Design System.

`AppButton` encapsula o contrato visual global de botões já definido em:

```text
src/assets/styles/components/button.css
```

e adiciona uma API Vue para variantes, tamanhos, ícones, loading, largura total e eventos.

## Importação

```js
import { AppButton } from "@/components/ui";
```

## Uso básico

```vue
<AppButton>
  Salvar
</AppButton>
```

Por padrão:

```text
type    → button
variant → primary
size    → md
```

## Variantes

Use preferencialmente as variantes semânticas:

| Variante | Cor               | Uso                                              |
| -------- | ----------------- | ------------------------------------------------ |
| `action` | Laranja           | Persistir, criar, atualizar ou executar uma ação |
| `route`  | Verde             | Navegar para outra rota                          |
| `modal`  | Marrom            | Abrir modal ou painel contextual                 |
| `filter` | Marrom contornado | Aplicar ou abrir filtros e detalhes              |
| `danger` | Vermelho          | Exclusão, cancelamento ou ação destrutiva        |

As variantes históricas continuam disponíveis por compatibilidade:

```text
primary
accent
highlight
outline
ghost
navigation
```

Exemplo:

```vue
<AppButton variant="accent">
  Confirmar
</AppButton>
```

```vue
<AppButton variant="highlight">
  Destacar
</AppButton>
```

```vue
<AppButton variant="outline">
  Cancelar
</AppButton>
```

```vue
<AppButton variant="ghost">
  Voltar
</AppButton>
```

## Relação com o CSS global

O componente sempre utiliza:

```text
.btn
```

e acrescenta a variante:

```text
.btn--primary
.btn--accent
.btn--highlight
.btn--outline
.btn--ghost
```

A aparência principal permanece centralizada em:

```text
src/assets/styles/components/button.css
```

`AppButton` não cria uma taxonomia visual paralela.

## Tamanhos

Tamanhos disponíveis:

```text
sm
md
lg
```

### Small

```vue
<AppButton size="sm">
  Pequeno
</AppButton>
```

Aplica:

```text
btn--sm
```

### Medium

```vue
<AppButton size="md">
  Médio
</AppButton>
```

`md` corresponde ao estilo base de `.btn` e não adiciona classe específica.

### Large

```vue
<AppButton size="lg">
  Grande
</AppButton>
```

Aplica:

```text
btn--lg
```

## Props

| Prop           | Tipo                                                                         | Padrão      | Descrição                       |
| -------------- | ---------------------------------------------------------------------------- | ----------- | ------------------------------- |
| `type`         | `'button' \| 'submit' \| 'reset'`                                            | `'button'`  | Tipo nativo do botão            |
| `variant`      | `'action' \| 'route' \| 'modal' \| 'filter' \| 'danger'` e variantes legadas | `'primary'` | Variante visual semântica       |
| `size`         | `'sm' \| 'md' \| 'lg'`                                                       | `'md'`      | Tamanho                         |
| `disabled`     | `Boolean`                                                                    | `false`     | Desabilita o botão              |
| `loading`      | `Boolean`                                                                    | `false`     | Coloca o botão em processamento |
| `block`        | `Boolean`                                                                    | `false`     | Ocupa toda a largura disponível |
| `icon`         | `String`                                                                     | `undefined` | Nome do ícone                   |
| `iconPosition` | `'start' \| 'end'`                                                           | `'start'`   | Posição do ícone                |
| `ariaLabel`    | `String`                                                                     | `undefined` | Nome acessível do botão         |

## Eventos

| Evento  | Payload      |
| ------- | ------------ |
| `click` | `MouseEvent` |

O evento é emitido apenas quando o botão está interativo.

## Disabled

```vue
<AppButton disabled>
  Salvar
</AppButton>
```

Quando desabilitado:

- o atributo nativo `disabled` é aplicado;
- o evento `click` não é retransmitido;
- o CSS global reduz a opacidade e remove transformações de interação.

## Loading

```vue
<AppButton loading>
  Salvando
</AppButton>
```

Quando `loading=true`:

```text
disabled efetivo = true
aria-busy = true
```

O conteúdo normal é temporariamente substituído pelo spinner.

O botão também recebe:

```text
app-button--loading
```

## Spinner

O spinner pertence ao componente e utiliza:

```text
currentColor
```

Portanto, acompanha automaticamente a cor da variante ativa.

A animação é definida em:

```text
AppButton/style.css
```

## Click durante loading

Enquanto `loading=true`, o botão não emite:

```text
click
```

Isso impede submissões ou ações duplicadas durante processamento.

## Block

```vue
<AppButton block>
  Continuar
</AppButton>
```

Aplica:

```text
width: 100%
```

através da classe:

```text
app-button--block
```

## Ícones

`AppButton` reutiliza:

```text
AppIcon
```

Exemplo:

```vue
<AppButton icon="save">
  Salvar
</AppButton>
```

O ícone recebe tamanho conforme o botão:

```text
sm → 16
md → 18
lg → 20
```

## Ícone no início

Padrão:

```vue
<AppButton icon="save" icon-position="start">
  Salvar
</AppButton>
```

## Ícone no final

```vue
<AppButton icon="arrow-right" icon-position="end">
  Continuar
</AppButton>
```

## Ícones decorativos

Quando o botão possui texto, o ícone é renderizado como decorativo através de `AppIcon`.

Assim, a semântica acessível continua sendo fornecida pelo texto do botão.

## Botão somente com ícone

```vue
<AppButton icon="close" aria-label="Fechar" />
```

Quando não existe slot padrão e existe `icon`, o botão recebe:

```text
app-button--icon-only
```

Nesse caso, forneça sempre um `aria-label` significativo.

Exemplos adequados:

```text
Salvar
Fechar
Excluir
Voltar
```

Evite:

```text
Ícone
Botão
Ação
```

## ariaLabel

```vue
<AppButton icon="save" aria-label="Salvar documento" />
```

É encaminhado diretamente para:

```html
aria-label
```

Também pode ser utilizado quando o texto visual não for suficiente para o contexto acessível.

## Submit

```vue
<form @submit.prevent="save">
  <AppButton type="submit">
    Salvar
  </AppButton>
</form>
```

O componente respeita a semântica nativa do botão.

## Reset

```vue
<AppButton type="reset">
  Limpar
</AppButton>
```

## Slot padrão

O texto ou conteúdo principal é fornecido pelo slot:

```vue
<AppButton>
  Salvar alterações
</AppButton>
```

Quando existe conteúdo, ele é envolvido por:

```text
app-button__label
```

## Acessibilidade

`AppButton` preserva a semântica nativa:

```html
<button></button>
```

e suporta:

```text
disabled
aria-busy
aria-label
```

Os ícones que acompanham texto são decorativos.

Botões somente com ícone devem possuir `ariaLabel`.

## Focus

O comportamento global de foco utiliza os tokens já existentes do Design System.

O componente não remove a semântica nativa do botão.

## Taxonomia visual

As variantes atuais têm os seguintes papéis gerais:

```text
primary   → ação principal ligada à marca
accent    → ação de apoio/destaque secundário
highlight → ação com maior ênfase visual
outline   → ação alternativa
ghost     → ação discreta
```

A escolha deve considerar hierarquia visual, não apenas preferência de cor.

## O que AppButton não faz

A versão atual não implementa:

```text
router-link
<a>
menu button
dropdown
split button
button group
confirmation
tooltip
```

Esses comportamentos devem ser implementados em componentes compostos quando houver necessidade concreta.

## Boas práticas

Para ação principal:

```vue
<AppButton>
  Salvar
</AppButton>
```

Para alternativa:

```vue
<AppButton variant="outline">
  Cancelar
</AppButton>
```

Para ação discreta:

```vue
<AppButton variant="ghost">
  Voltar
</AppButton>
```

Para processamento:

```vue
<AppButton type="submit" :loading="saving">
  Salvar
</AppButton>
```

## Playground

Consulte:

```text
Playground → UI → AppButton
```

Arquivo:

```text
src/views/playground/ui/ButtonPlayground.vue
```

## Testes

Os testes estão em:

```text
tests/components/ui/AppButton.spec.js
```

A cobertura atual inclui:

```text
renderização
type
variant
size
block
disabled
click
loading
aria-busy
spinner
ícone start
ícone end
aria-label
icon-only
ícone decorativo
```
