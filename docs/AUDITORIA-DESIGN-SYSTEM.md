# Auditoria de componentes do design system

Revisão estática das views e componentes Vue do conViva. A presença de um controle nativo não prova defeito funcional, mas identifica usos que ainda não consomem os componentes de formulário existentes. Ocorrências no código-fonte; um `v-for` pode renderizar várias instâncias.

## Resultado

Há controles nativos em 17 arquivos de telas e componentes de cuidado. A revisão anterior de Nova despesa substituiu somente os checkboxes: texto, seleção e descrição ainda usam elementos nativos.

| Arquivo | input | select | textarea | button |
| --- | ---: | ---: | ---: | ---: |
| frontend/src/components/care/CareAccessForm.vue | 1 | 1 | 0 | 0 |
| frontend/src/components/care/CareEntryDialog.vue | 4 | 3 | 1 | 0 |
| frontend/src/components/care/CareExecutionDialog.vue | 1 | 0 | 0 | 0 |
| frontend/src/components/care/CareScheduleFields.vue | 6 | 1 | 0 | 0 |
| frontend/src/components/care/PersonalRecipientAvatar.vue | 1 | 0 | 0 | 1 |
| frontend/src/components/care/ProposalActions.vue | 0 | 0 | 1 | 0 |
| frontend/src/components/care/RecipientProfileFields.vue | 5 | 0 | 3 | 0 |
| frontend/src/components/care/agenda/AgendaMonth.vue | 0 | 0 | 0 | 1 |
| frontend/src/components/care/agenda/AgendaOccurrence.vue | 0 | 0 | 0 | 2 |
| frontend/src/views/auth/OrganizationSelectPage.vue | 2 | 0 | 0 | 1 |
| frontend/src/views/auth/RegisterPage.vue | 2 | 0 | 0 | 0 |
| frontend/src/views/care/AgendaPage.vue | 4 | 4 | 1 | 6 |
| frontend/src/views/care/DecisionsPage.vue | 0 | 3 | 0 | 0 |
| frontend/src/views/care/FinancePage.vue | 2 | 1 | 0 | 0 |
| frontend/src/views/care/RecipientListPage.vue | 4 | 1 | 0 | 0 |
| frontend/src/views/care/RecipientPage.vue | 1 | 0 | 0 | 1 |
| frontend/src/views/organization-members/OrganizationMemberListPage.vue | 2 | 0 | 0 | 0 |

Totais: 35 `input`, 14 `select`, 6 `textarea`, 12 `button`.

## Correspondências existentes

- Texto: `AppInput`; e-mail, telefone, senha, busca e números têm variantes próprias.
- Valores monetários: `AppCurrency`.
- Seleção: `AppSelect`, que recebe `options` e preserva o tipo dos valores. Não basta trocar a tag de um `select` com `option`.
- Texto longo: `AppTextarea`.
- Datas: `AppDate` para datas; conferir o contrato de `AppInput` para `datetime-local` e preservar o fuso da funcionalidade.
- Seleções booleanas: `AppCheckbox`; seleções múltiplas também dispõem de `CheckboxGroup`. `AppCheckbox` recebe booleano, não um array.
- Arquivos: `AppFileUpload`, preservando validação, preview e permissões próprias de avatar/documentos.
- Ações: `AppButton`, com variantes semânticas `action`, `modal`, `cancel`, `filter` e `danger`.
- Abas e tabelas: existem `AppTabs` e `AppTable`; avaliar a adequação de seus contratos antes de substituir composições específicas.

## HTML que deve continuar existindo

`form`, `fieldset`, `legend`, `section`, títulos, parágrafos, listas e links de navegação têm papéis semânticos próprios. Não é necessário criar um componente para cada tag. Os controles internos de `components/forms` e `components/ui` usam HTML nativo por definição.

O HeaderBar contém cinco botões nativos internos ao componente de layout. O calendário mensal tem botões com semântica e navegação próprias; avatar, seletor de grupo e abas também têm interações específicas. Esses casos exigem avaliação de acessibilidade e estilo, em vez de substituição automática.

## Ordem recomendada para padronização

1. `CareEntryDialog`: texto, descrição, seleções e datas, mantendo o rateio e os checkboxes já padronizados.
2. Filtros de Financeiro, Central de decisões e Agenda, mantendo ids, atualização dos filtros e valores tipados.
3. Ficha do assistido, recorrência, execução e justificativa de recusa.
4. Cadastro, convites, acessos e upload de documentos/avatar.
5. Ações específicas de calendário, abas e layout, após conferir foco, teclado e responsividade.

A migração deve preservar rótulos associados, mensagens de validação, obrigatoriedade, estados de carregamento, payloads e seleções numéricas/nulas. O inventário documenta os pontos pendentes; não representa migração concluída.
