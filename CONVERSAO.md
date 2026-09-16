# Conversão do conViva

Decisões de 16/09/2026: base local do legalis somente para leitura; alterações exclusivamente no conViva; MySQL `conviva_db`; Laravel e Pinia da base; layout adaptado. O banco `db_legalis` e o banco antigo `convive_db` não são alvos da reconstrução.

## Aproveitamento

- Legalis: autenticação JWT, cadastro, contexto de organização, isolamento, convites, gestão de membros e perfis, componentes de formulário, navegação, layout e tema.
- Organizações passam a representar grupos de cuidados; cada grupo pode ter várias pessoas e pets.
- Tarefas, eventos, registros, documentos e despesas são adaptados ao contexto de cada assistido. Integrações jurídicas, processos, OAB, DJEN, DataJud e faturamento jurídico não pertencem ao novo produto.

## Permissões

Referências consultadas em 16/09/2026:

- [Caring Village — Member roles](https://help.caringvillage.com/hc/en-us/articles/218121268-What-do-the-member-roles-mean): administrador, círculo próximo, amigos e acesso revogado; distinção de acesso a medicamentos, documentos e registros privados.
- [Lotsa Helping Hands — Roles](https://www.lotsahelpinghands.com/help/new-features-roles.html): líderes, coordenadores e membros; participação em múltiplos grupos.

Decisão inicial, substituída pelo modelo de consentimento abaixo: administrador gerencia o grupo; coordenador organiza cuidados; cuidador executa cuidados autorizados; observador consulta os dados autorizados. Membros não administrativos recebem acesso explícito por assistido, com áreas separadas (rotina, saúde, documentos e despesas), opção de edição e expiração. Revogação e suspensão têm efeito no backend. Administradores têm acesso integral dentro do próprio grupo, nunca entre grupos. Convites para o grupo não concedem automaticamente acesso a todos os assistidos.

## Escopo da validação

Migrations em banco exclusivo, testes de isolamento entre grupos e assistidos, perfis e revogação, autenticação, cuidados, documentos privados, rateio e build da interface. Sem importação de dados pessoais do legalis e sem envio de e-mails reais durante a validação.

## Validação executada

- MySQL `conviva_db` reconstruído com migrations e perfis iniciais.
- 13 testes de backend, 45 asserções: isolamento, autenticação, áreas, revogação, expiração, suspensão, documentos, rateio, múltiplos grupos e convites.
- 7 testes de Pinia aprovados para compartilhamento de estado, respostas atrasadas, tratamento de erros e notificações.
- Build de produção concluído.
- Hashes dos 280 arquivos de origem conferidos sem divergências.
- Conferência de interface local com conta temporária, sem dados reais de assistidos.

E-mails permanecem em log no desenvolvimento. Notificações são internas; doses recorrentes e push automático não fazem parte desta implementação.


## Revisão: cuidado compartilhado sem imposição unilateral

A orientação do usuário substituiu administrador/coordenador por responsável. A política atual está no README e no CONTEXT.MD. As fontes de mercado foram referências iniciais de acesso por área; a regra de autoria, aceite individual, recusa motivada e preservação do acordo anterior deriva da especificação do usuário.

Migration incremental aplicada em conviva_db, sem reconstrução. 24 testes de backend / 117 asserções e 10 testes de frontend aprovados.

## Consolidação das migrations no protótipo

Por orientação do usuário, conviva_db é descartável nesta fase. Campos de revisão, participantes afetados e execução vinculada, além das tabelas de propostas e decisões, foram incorporados à migration 2026_09_16_100000_create_care_tables.php. A incremental 2026_09_16_200000_add_shared_care_decisions.php e sua conversão de dados antigos foram removidas. O banco é reconstruído diretamente com o esquema atual.
