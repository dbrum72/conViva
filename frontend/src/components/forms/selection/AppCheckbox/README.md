# AppCheckbox

Checkbox booleano para formulários.

O componente encapsula `CheckboxControl`, adicionando:

- label;
- hint;
- mensagem de erro;
- acessibilidade;
- estado obrigatório;
- estado `indeterminate`;
- integração com o Design System.

---

# Importação

```vue
import { AppCheckbox } from '@/components/forms'
```

---

# Uso básico

```vue
<AppCheckbox v-model="accepted" id="accepted" label="Aceito os termos" />
```

---

# Com hint

```vue
<AppCheckbox
  v-model="notifications"
  id="notifications"
  label="Receber notificações"
  hint="Você poderá alterar essa opção posteriormente."
/>
```

---

# Com erro

```vue
<AppCheckbox
  v-model="accepted"
  id="accepted"
  label="Aceito os termos"
  error="Campo obrigatório."
  required
/>
```

---

# Indeterminate

```vue
<AppCheckbox
  v-model="selectAll"
  id="select-all"
  label="Selecionar todos"
  indeterminate
/>
```

---

# Props

| Prop          | Tipo    | Padrão | Descrição                   |
| ------------- | ------- | ------ | --------------------------- |
| modelValue    | Boolean | false  | Estado do checkbox          |
| id            | String  | —      | ID do controle              |
| label         | String  | ''     | Texto da label              |
| hint          | String  | ''     | Texto auxiliar              |
| error         | String  | ''     | Mensagem de erro            |
| disabled      | Boolean | false  | Desabilita o controle       |
| required      | Boolean | false  | Campo obrigatório           |
| autofocus     | Boolean | false  | Recebe foco automaticamente |
| indeterminate | Boolean | false  | Estado visual intermediário |

---

# Eventos

| Evento            | Payload    |
| ----------------- | ---------- |
| update:modelValue | Boolean    |
| focus             | FocusEvent |
| blur              | FocusEvent |

---

# Acessibilidade

O componente implementa:

- associação correta entre label e controle;
- `aria-describedby`;
- `aria-invalid`;
- indicação visual e semântica de campo obrigatório;
- associação automática entre hint/erro e o controle.

---

# Boas práticas

Utilize `AppCheckbox` para representar estados booleanos.

Exemplos:

- Aceito os termos
- Receber notificações
- Habilitar recurso
- Confirmo que li

---

# Limitações

O componente representa um **checkbox booleano simples**.

Não deve ser utilizado paro grupos de seleção múltipla.

Para seleção múltipla será criado um componente específico (`AppCheckboxGroup`).

---

# Playground

Consulte:

```text
Playground → Forms → AppCheckbox
```
