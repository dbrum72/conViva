# AppSwitch

Componente booleano para ativar ou desativar configurações e estados da aplicação.

O `AppSwitch` encapsula o `SwitchControl` e adiciona:

- label;
- hint;
- mensagem de erro;
- estado obrigatório;
- estado desabilitado;
- acessibilidade;
- integração com o Design System.

## Importação

```js
import { AppSwitch } from "@/components/forms";
```

## Uso básico

```vue
<script setup>
import { ref } from "vue";

import { AppSwitch } from "@/components/forms";

const notifications = ref(false);
</script>

<template>
  <AppSwitch
    v-model="notifications"
    id="notifications"
    name="notifications"
    label="Ativar notificações"
  />
</template>
```

O `v-model` é estritamente booleano:

```js
true;
false;
```

## Estado inicialmente ativado

```vue
<script setup>
import { ref } from "vue";

const automaticSave = ref(true);
</script>

<template>
  <AppSwitch
    v-model="automaticSave"
    id="automatic-save"
    name="automatic-save"
    label="Salvar automaticamente"
  />
</template>
```

## Com hint

```vue
<AppSwitch
  v-model="automaticSave"
  id="automatic-save"
  name="automatic-save"
  label="Salvar automaticamente"
  hint="As alterações serão salvas enquanto você trabalha."
/>
```

Quando não existe erro, o `hint` é associado ao controle por `aria-describedby`.

## Com erro

```vue
<AppSwitch
  v-model="enabled"
  id="feature-enabled"
  name="feature-enabled"
  label="Ativar configuração"
  error="Esta configuração é obrigatória."
  required
/>
```

Quando `error` possui conteúdo:

- o hint deixa de ser exibido;
- a mensagem de erro é exibida;
- `aria-invalid="true"` é aplicado ao controle;
- `aria-describedby` referencia a mensagem de erro;
- a classe `app-switch--invalid` é aplicada ao componente.

## Obrigatório

```vue
<AppSwitch
  v-model="enabled"
  id="required-feature"
  name="required-feature"
  label="Ativar configuração obrigatória"
  required
/>
```

O componente exibe `*` junto à label e encaminha `required` ao controle nativo.

## Desabilitado

```vue
<AppSwitch
  v-model="enabled"
  id="unavailable-feature"
  name="unavailable-feature"
  label="Configuração indisponível"
  disabled
/>
```

## Props

| Prop         | Tipo      | Padrão      | Descrição                         |
| ------------ | --------- | ----------- | --------------------------------- |
| `modelValue` | `Boolean` | `false`     | Estado atual do switch            |
| `id`         | `String`  | `undefined` | Identificador do controle         |
| `name`       | `String`  | `undefined` | Nome do controle                  |
| `label`      | `String`  | `''`        | Texto apresentado ao usuário      |
| `hint`       | `String`  | `''`        | Texto auxiliar                    |
| `error`      | `String`  | `''`        | Mensagem de erro                  |
| `disabled`   | `Boolean` | `false`     | Desabilita o controle             |
| `required`   | `Boolean` | `false`     | Marca o controle como obrigatório |
| `autofocus`  | `Boolean` | `false`     | Solicita foco automático          |

## Eventos

| Evento              | Payload      | Descrição                             |
| ------------------- | ------------ | ------------------------------------- |
| `update:modelValue` | `Boolean`    | Emitido quando o estado é alterado    |
| `focus`             | `FocusEvent` | Emitido quando o controle recebe foco |
| `blur`              | `FocusEvent` | Emitido quando o controle perde foco  |

## Semântica

Internamente, o `SwitchControl` utiliza:

```html
<input type="checkbox" role="switch" />
```

O estado é exposto também através de:

```text
aria-checked="true"
```

ou:

```text
aria-checked="false"
```

`aria-checked` acompanha reativamente o valor de `modelValue`.

O valor emitido é obtido de:

```js
event.target.checked;
```

Portanto, `update:modelValue` sempre possui valor booleano.

## Acessibilidade

O `AppSwitch` implementa:

- controle `checkbox` nativo;
- `role="switch"`;
- `aria-checked`;
- associação entre `label` e `id`;
- `aria-invalid`;
- `aria-describedby`;
- `required`;
- `disabled`;
- navegação e ativação nativas pelo teclado;
- foco visual.

Sempre forneça um `id` único ao componente para garantir a associação correta entre label, hint, erro e controle.

## AppSwitch ou AppCheckbox?

Embora ambos trabalhem com valores booleanos, representam intenções diferentes.

### AppSwitch

Use para alterar imediatamente um estado ou configuração:

```text
Ativar notificações
Salvar automaticamente
Habilitar sincronização
Permitir atualizações automáticas
```

O usuário espera que a alteração tenha efeito ao alternar o controle.

### AppCheckbox

Use para seleção, confirmação ou consentimento:

```text
Aceito os termos de uso
Selecionar este registro
Incluir anexos
Confirmo que li as informações
```

Uma checkbox frequentemente participa de uma ação posterior, como o envio de um formulário.

## Boas práticas

Prefira labels que descrevam claramente o estado controlado:

```text
Ativar notificações
Salvar automaticamente
Habilitar sincronização
```

Evite labels ambíguas como:

```text
Sim
Não
Ativo
Opção
```

O significado do estado ligado e desligado deve ser compreensível sem depender exclusivamente da aparência visual do switch.

## Playground

Consulte:

```text
Playground → Forms → AppSwitch
```
