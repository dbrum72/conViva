# E08 — Despesas e documentos contextualizados

## Despesas

A tela Despesas filtra por período, categoria, participante e situação. Categorias são opcionais e ficam na versão da despesa. A Central mostra mudanças de categoria junto aos demais detalhes da proposta. A consulta usa o vencimento ou, na ausência dele, a data de cadastro no fuso do grupo.

`GET /api/expenses` aceita `category`, `participant`, `status`, `page` e `per_page`, além dos filtros de período existentes. O participante pode ser autor, titular de parcela ou participante de uma decisão da despesa. O filtro seleciona despesas inteiras e os totais abrangem todas as páginas do resultado. O Laravel soma em centavos inteiros somente despesas confirmadas, os pagamentos declarados e o saldo a pagar. A interface identifica as parcelas aceitas e a situação dos pagamentos.

O pagamento permanece restrito à própria parcela, com os comprovantes privados da [entrega antecipada](COMPROVANTES-PAGAMENTO.md). Uma parcela paga bloqueia edição ou cancelamento da despesa. Um eventual ajuste compensatório deverá ter fluxo próprio, com referência, justificativa e novos aceites.

## Documentos

Na aba Documentos, é possível filtrar os arquivos por categoria e data de envio no fuso do grupo. O autor pode editar a categoria, a validade informativa e vincular o documento a um registro de cuidado ou instrução. O vínculo também pode ser definido no envio. Uma vez criado, é permanente para preservar o escopo de acesso. O arquivo permanece privado.

A listagem e o download de um documento vinculado exigem leitura em Documentos e na área do registro, no mesmo assistido e grupo. Envio, edição e exclusão exigem edição em Documentos. Edição e exclusão exigem autoria. A API lista os documentos em `GET /api/recipients/{recipient}/documents` com filtros opcionais `category`, `from` e `to`. `PATCH /api/recipients/{recipient}/documents/{document}` atualiza os metadados.

A exclusão retorna sucesso após confirmar a remoção do arquivo e dos metadados. Se o armazenamento falhar e o arquivo persistir, os metadados continuam disponíveis para nova tentativa. Se o arquivo já tiver sido removido e a gravação no banco falhar, uma nova tentativa conclui a remoção do registro. Falha no cadastro aciona a limpeza do arquivo recém-enviado.

## Persistência e validação

Os campos opcionais de documentos foram incluídos na migration de criação e aplicados em `conviva_db` pelo utilitário protegido `php backend/tools/apply-contextual-documents.php`, preservando os dados existentes. As categorias de despesas usam o JSON de detalhes existente.

Os 13 testes MySQL específicos de despesas, documentos e comprovantes passaram com 210 asserções, usando transações com rollback. Incluem totais entre páginas, isolamento das áreas vinculadas e nova tentativa após falha de exclusão.

Os 88 testes do frontend, o build de produção e a verificação do Pint passaram. A interface de documentos reutiliza os controles compartilhados e o padrão de diálogo de `CareEntryDialog`. A paginação de despesas segue a navegação da Central de Decisões. A validação das telas foi automatizada.

A próxima etapa é **E09 — Histórico, exportação e privacidade**.
