# E03 — Ficha por tipo de assistido

Implementação de 28/09/2026. A ficha reúne identificação e seções de rotina e saúde, com acesso independente por área. O fluxo existente Vue → Pinia → Axios → Laravel foi reutilizado.

## Entrega

- Criança/adolescente: escola, referências de pessoas autorizadas, preferências, instruções e contatos de apoio. O registro de pessoas autorizadas é informativo; não concede permissões no aplicativo nem substitui autorizações da instituição.
- Adulto: preferências e autonomia, instruções cotidianas e contatos de apoio. O tipo adulto não presume incapacidade nem altera sua representação.
- Pet: espécie e raça existentes, identificação opcional, instruções de alimentação/passeio e contatos veterinários na área de saúde.
- Formulários com exemplos curtos e campos opcionais, sem exigir dados de outros tipos. Contatos estruturados com nome, vínculo/serviço e telefone, até dez por seção. Campos desconhecidos e dados específicos incompatíveis com o tipo são rejeitados no backend.
- A tela Assistido do grupo apresenta a ficha vigente, abaixo do cartão com avatar e identificação. O botão Editar ficha abre o formulário de revisão na mesma tela; a ficha foi removida da tela de registros de cuidados. A edição usa a revisão cadastral existente; propostas e comparações apresentam as duas seções com rótulos legíveis. Mudanças permanecem pendentes até os aceites necessários.

## Autorização e persistência

`routine_profile` e `health_profile` são campos JSON validados por chaves explícitas, adicionados à migration de criação de `care_recipients`, conforme a política do protótipo. Não há migration incremental.

Os campos ficam ocultos na serialização padrão do model, inclusive em relações de notificações e propostas. O serviço `RecipientProfile` libera cada seção somente após autorização da área. Listagem, detalhe, payloads e comparações da central, além das respostas de decisão/retirada, respeitam esse filtro. Alterar saúde exige edição de saúde; uma mudança nessa seção revalida o acesso de todos os participantes necessários, inclusive votos anteriores, antes de ser aplicada. Recusa motivada continua possível sem revelar o conteúdo restrito.

A edição copia os dados da ficha, preservando a versão exibida até a resposta do servidor. Troca de grupo/sessão fecha e limpa o formulário. Respostas atrasadas continuam protegidas pelas stores existentes.

## Validação

- Frontend: 43 testes em 12 arquivos aprovados; inclui campos dos três tipos, edição e seções autorizadas.
- Build de produção aprovado: 2.043 módulos.
- Backend: na execução conjunta, 63 testes passaram, com 492 asserções; os sete testes de comprovantes falharam na preparação por ausência de tabelas. Os testes concorrentes usam `DatabaseMigrations`, que remove as tabelas ao terminar; os comprovantes usam `DatabaseTransactions` e pressupõem schema migrado. A execução conjunta, portanto, não ficou integralmente verde.
- Comprovantes executados separadamente após reconstrução: **7 testes / 59 asserções aprovados**, com rollback transacional.
- Os novos testes verificam ficha pendente, aceite, isolamento de saúde na ficha/central/notificações, perda de acesso antes do aceite, recusa sem vazamento e validação de campos/contatos.
- Pint e `git diff --check` aprovados.
- `conviva_db` reconstruído com migrations e seeder após os testes destrutivos. Nenhum `.env` ou arquivo/banco do legalis alterado. O seeder cria permissões, não contas de acesso.

Não houve inspeção visual em navegador nem auditoria com leitor de tela. A composição da suíte conjunta permanece como pendência de infraestrutura de testes; os comprovantes devem ser executados separadamente sobre o schema migrado.

Próxima etapa sequencial: E04 — agenda e ocorrências recorrentes.

## Ajuste anterior à E04 — revisão por qualquer responsável (28/09/2026)

Removida a exigência de autoria do cadastro para propor revisão ou arquivamento. Responsáveis convidados têm a mesma capacidade de revisão dos responsáveis criadores, desde que mantenham vínculo e acesso vigentes. Listagem e detalhe informam `can_manage_profile`; a interface usa essa capacidade. Permissões por área, inclusive saúde, continuam sendo verificadas no backend.

Quando há um único responsável ativo vinculado, a proposta é aplicada imediatamente, sem votos de terceiros. Com vários responsáveis, a submissão representa a anuência do proponente, e todos os demais precisam aceitar. Um aceite parcial, silêncio, recusa ou retirada não substitui a ficha vigente. Autoria do cadastro permanece no histórico; somente o autor de cada proposta pode retirá-la. As rotas diretas permitem alteração pelo único responsável, inclusive convidado, mas continuam bloqueando alterações unilaterais quando há outros responsáveis ou proposta pendente.

Validação específica: cinco testes backend, 51 asserções, aprovados. Cobrem três responsáveis com aceite parcial/final, convidado como único responsável, revisão de rotina/saúde, recusa, retirada, arquivamento e negação a cuidador/observador/acesso expirado. Usam transações com rollback sobre o schema migrado, sem reconstruir o banco.

Frontend após o ajuste: 44 testes em 12 arquivos e build de produção aprovados. Pint e verificação de whitespace aprovados.

## Ajuste anterior à E04 — ações na Central de Decisões (28/09/2026)

Aceitar, recusar com justificativa e retirar a própria proposta ficam concentrados nos cartões da Central. Conforme esclarecimento do usuário, “revogar” neste pedido significa recusar uma proposta pendente; não desfazer um aceite já registrado. A recusa exige motivo não vazio, limitado a 2.000 caracteres, e reutiliza a validação e os endpoints existentes do Laravel.

Cada cartão permite consultar a comparação antes de decidir, mostra bloqueios e respostas individuais e apresenta apenas ações autorizadas pelo backend. Falhas preservam o motivo digitado; sucesso atualiza a listagem e informa o resultado. Os formulários de resposta foram removidos da tela de cuidados e do detalhe da proposta. Notificações, links de cuidados e novas revisões abrem a Central com a proposta selecionada, revalidada no servidor. O detalhe continua disponível para consulta.

As permissões, a exigência de unanimidade e a preservação da versão vigente continuam no backend. Não houve alteração de banco nesta mudança.

Validação da centralização: 46 testes frontend em 12 arquivos aprovados; build aprovado (2.045 módulos); `git diff --check` sem erros. Cobertura inclui resposta de cadastro/cuidado, recusa vazia, preservação do motivo em falha, bloqueios/capacidades, atualização da lista, proposta selecionada sem duplicação e proteção das stores contra respostas de outro contexto. Não houve inspeção visual em navegador nem nova execução backend, pois os endpoints e as regras de decisão não foram alterados neste ajuste.


Nota posterior — E04 (28/09/2026): a pendência de composição da suíte foi resolvida ordenando os ensaios concorrentes depois dos testes comuns/transacionais no `phpunit.xml`. A suíte completa passou com 91 testes e 739 asserções. Evidências em `ENTREGA-E04.md`; os resultados acima permanecem como histórico da E03.
