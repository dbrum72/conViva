# E09 — Histórico, exportação e privacidade

## Contrato

- Histórico do assistido do grupo ativo, inclusive se arquivado, com paginação de 25 eventos e filtro por área e período. A consulta usa a data de registro no fuso do grupo, com início e fim inclusivos. O padrão são 30 dias e cada consulta pode abranger até 366 dias.
- A fonte são propostas e decisões dos cuidados, revisões cadastrais, execuções vinculadas e suas correções. O título de uma proposta vem da versão correspondente. Revisões cadastrais aparecem em Rotina com descrição genérica e sem conteúdo da ficha. Dados de documentos e anexos ficam nas telas autorizadas de origem.
- Novas exportações usam PDF e os mesmos filtros aplicados na tela. O documento identifica o período, fuso, áreas, solicitante e autoria dos registros. Exportações anteriores em CSV e HTML seguem disponíveis até a expiração, com as mesmas verificações de acesso.
- A API registra o solicitante, grupo, assistido, áreas autorizadas selecionadas, período, instante e formato. A geração é enfileirada para qualquer volume e usa leitura em lotes. O worker e o download revalidam o acesso. Perder acesso a qualquer área incluída bloqueia a entrega do arquivo completo. O arquivo fica privado e expira 24 horas após a solicitação. A limpeza roda a cada hora e o metadado de auditoria é retido por 90 dias.
- Rotas autenticadas com contexto de grupo: `GET /api/history`, `POST /api/history/exports`, `GET /api/history/exports/{id}` e `GET /api/history/exports/{id}/download`. A criação retorna 202. Uma exportação de outro usuário ou grupo retorna 404. Área revogada retorna 403, geração indisponível retorna 409 e arquivo ausente ou expirado retorna 410.

## Inventário e política proposta para o piloto

| Dados | Finalidade | Retenção e descarte |
| --- | --- | --- |
| Conta, credenciais, vínculos, convites e concessões | Autenticar e autorizar por grupo e área | Manter durante o vínculo. Revogar sessões e concessões no encerramento. Expirar tokens, convites e solicitações de recuperação conforme seus contratos. |
| Ficha do assistido, cuidados, decisões, execuções e correções | Coordenar os cuidados e preservar o histórico compartilhado | Manter durante a atividade do grupo. Em até 90 dias após encerramento do grupo, revisar a necessidade de conservação e excluir o que tiver prazo aprovado para descarte. |
| Documentos, comprovantes e fotos pessoais | Contextualizar registros e personalizar a exibição | Guardar em disco privado. Remover fotos pessoais no encerramento da conta e descartar documentos e comprovantes conforme a política de seus registros de origem. |
| Preferências, notificações e metadados de exportação | Entregar alertas e permitir auditabilidade | Apagar preferências pessoais no encerramento. Revisar a necessidade de notificações encerradas em 90 dias. Manter metadados de exportação por 90 dias. Arquivos de exportação ficam disponíveis por 24 horas. |
| Logs operacionais e backups | Diagnóstico e recuperação em caso de falha | Proposta de 30 dias para ambos. Backups criptografados com acesso restrito e rotação automática no ambiente de implantação. Excluir o diretório de exportações da rotina de backup. Depois de restaurar um backup, reaplicar os encerramentos e descartes antes de liberar o acesso. |

O encerramento de conta é um procedimento operacional a validar antes do piloto. Verificar a identidade do solicitante, tratar a responsabilidade de grupos com um único responsável, bloquear o login e a recuperação, invalidar sessões e acessos, limpar dados pessoais separáveis e preservar as referências de autoria exigidas pelos registros compartilhados. A identificação pessoal remanescente deve ser revisada para decidir quando substituí-la por um identificador estável sem dados de contato. A trilha registra o pedido e sua execução com acesso operacional restrito. Os prazos não automatizados, a operação de backup e as políticas de encerramento dependem de revisão especializada antes do lançamento público.

Logs operacionais de exceções da API e do banco guardam tipo e localização do erro. O job de exportação propaga uma mensagem genérica ao falhar. A auditoria de exportação fica em `history_exports`. A operação requer scheduler e um worker de fila em produção.

## Critérios de aceite

- Paginar e ordenar por instante de registro, com identificador estável para desempate. Mostrar data da ocorrência separadamente quando disponível.
- Excluir eventos de outro grupo e de áreas sem concessão, inclusive ao exportar.
- Preservar a execução original junto com a correção e identificar a referência corrigida.
- Recusar download após revogação, expiração ou troca de contexto. Descartar arquivos expirados e preservar temporariamente o metadado da solicitação.
- Entregar PDF diretamente para download, com autoria e período.
