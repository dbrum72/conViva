# Contexto do conViva

Cuidados compartilhados de crianças, pessoas adultas sob cuidados e pets, sem exigir conversa direta entre os responsáveis.

Grupos (organizations) isolam dados. Usuários podem participar de vários grupos. Cada assistido pertence a um grupo. O backend verifica vínculo ativo pelo X-Tenant.

Perfis: responsável, cuidador e observador. Responsável organiza seus registros e decide por si; não controla outro responsável. Cuidador é terceiro contratado/autorizado a executar cuidados nas áreas concedidas. Observador só visualiza áreas autorizadas. O perfil do grupo não concede acesso automático a todos os assistidos.

A autoria limita edição, cancelamento de registros e exclusão de documentos. Rotinas e compromissos dependem do aceite dos demais responsáveis. Designação de prestador, rateio e participantes afetados também geram decisões individuais. Recusa exige motivo; silêncio não autoriza.

care_proposals guarda versões imutáveis das propostas; care_decisions registra cada resposta, motivo e data. O registro vigente continua válido durante mudanças pendentes. Uma proposta inicial pendente não integra a agenda nem gera dívida. Cancelamentos preservam o histórico. Execuções por prestador designado geram registro próprio ligado por related_entry_id.

Responsáveis não podem suspender/rebaixar/remover outros responsáveis. Alterações cadastrais do assistido compartilhado ficam bloqueadas para evitar imposição unilateral. Área de rotina não é concedida automaticamente aos observadores/cuidadores.

Fluxo: componente Vue → Pinia em frontend/src/state → Axios em frontend/src/services → controller → serviços Laravel. Autorização e decisões ficam no backend. MySQL conviva_db. Nunca alterar arquivos ou banco de legalis. Design System somente consultivo. Nesta fase de projeto, conviva_db é descartável: alterar diretamente as migrations de criação, sem incrementais para campos, e recriar somente esse banco quando necessário.

Na seção Grupo e cuidadores, outro responsável não pode alterar a função nem a situação de um responsável. O próprio responsável pode mudar sua função ou desativar seu vínculo, inclusive quando for o único, com atualização imediata das permissões.

Cada grupo possui no máximo um assistido, inclusive arquivado (índice único em care_recipients.organization_id). Usuários podem participar de vários grupos com funções e acessos independentes. Convites devem conter exatamente um care_accesses para o assistido ativo do grupo e suas áreas. Entrada no grupo e vínculo ao assistido são criados atomicamente no aceite. Revalidar responsabilidade ativa do remetente no aceite.

A entrada após autenticação é Meus assistidos (organizations.select): visão agregada via GET /api/groups, sem exigir tenant selecionado. Retorna somente grupos ativos com vínculo ativo e assistido autorizado; perfil e capacidades calculados por grupo. Abrir assistido exige selectOrganization antes de navegar. Nunca combinar permissões de grupos distintos.
