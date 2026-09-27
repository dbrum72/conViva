# Contexto do conViva

Cuidados compartilhados de crianças, pessoas adultas sob cuidados e pets, sem exigir conversa direta entre os responsáveis.

Grupos (organizations) isolam dados. Usuários podem participar de vários grupos. Cada assistido pertence a um grupo. O backend verifica vínculo ativo pelo X-Tenant.

Perfis: responsável, cuidador e observador. Responsável organiza seus registros e decide por si; não controla outro responsável. Cuidador é terceiro contratado/autorizado a executar cuidados nas áreas concedidas. Observador só visualiza áreas autorizadas. O perfil do grupo não concede acesso automático a todos os assistidos.

A autoria limita edição, cancelamento de registros e exclusão de documentos. Rotinas e compromissos dependem do aceite dos demais responsáveis. Designação de prestador, rateio e participantes afetados também geram decisões individuais. Recusa exige motivo; silêncio não autoriza.

care_proposals guarda versões imutáveis das propostas; care_decisions registra cada resposta, motivo e data. O registro vigente continua válido durante mudanças pendentes. Uma proposta inicial pendente não integra a agenda nem gera dívida. Cancelamentos preservam o histórico. Execuções por prestador designado geram registro próprio ligado por related_entry_id.

Responsáveis não podem suspender/rebaixar/remover outros responsáveis. Alterações diretas no cadastro compartilhado ficam bloqueadas. O responsável autor pode propor revisão ou arquivamento pela central de decisões; o cadastro vigente só muda após todos os aceites necessários. Área de rotina não é concedida automaticamente aos observadores/cuidadores.

Fluxo: componente Vue → Pinia em frontend/src/state → Axios em frontend/src/services → controller → serviços Laravel. Autorização e decisões ficam no backend. MySQL conviva_db. Nunca alterar arquivos ou banco de legalis. Design System somente consultivo. Nesta fase de projeto, conviva_db é descartável: alterar diretamente as migrations de criação, sem incrementais para campos, e recriar somente esse banco quando necessário.

Na seção Grupo e cuidadores, outro responsável não pode alterar a função nem a situação de um responsável. O próprio responsável pode mudar sua função ou desativar seu vínculo, inclusive quando for o único, com atualização imediata das permissões.

Cada grupo possui no máximo um assistido, inclusive arquivado (índice único em care_recipients.organization_id). Usuários podem participar de vários grupos com funções e acessos independentes. Convites devem conter exatamente um care_accesses para o assistido ativo do grupo e suas áreas. Entrada no grupo e vínculo ao assistido são criados atomicamente no aceite. Revalidar responsabilidade ativa do remetente no aceite.

A entrada após autenticação é Meus assistidos (organizations.select): visão agregada via GET /api/groups, sem exigir tenant selecionado. Retorna somente grupos ativos com vínculo ativo e assistido autorizado; perfil e capacidades calculados por grupo. Abrir assistido exige selectOrganization antes de navegar. Nunca combinar permissões de grupos distintos.

O avatar do assistido é uma preferência pessoal de cada responsável, vinculada ao usuário e ao assistido, sem modificar o cadastro compartilhado nem exigir aceite de terceiros. Cada responsável com vínculo e acesso vigentes pode enviar, substituir ou remover somente sua própria foto. As imagens ficam em armazenamento privado, disponíveis apenas ao autor por rota autenticada com validação de grupo e acesso. A sidebar mostra essa foto e o nome do grupo ativo; sem foto, usa iniciais. Troca e criação de grupos permanecem somente em Meus assistidos na sidebar. JPG, PNG ou WebP até 2 MB e 4096 × 4096 pixels.

E02: central paginada de decisões e revisão cadastral com histórico próprio. Participantes exigidos são fixados por versão. Mudança na rede de responsáveis ou perda de acesso de autor/participante bloqueia aceite e aplicação, sem apagar votos; recusa motivada e retirada pelo autor autorizado continuam possíveis. Notificações novas abrem a proposta com nova verificação de acesso. Detalhes em ENTREGA-E02.md.

Testes automatizados usam MySQL conviva_db por orientação do usuário e são destrutivos: recriam tabelas. A suíte verifica ambiente, driver e nome efetivo do banco antes das migrations. Reconstruir conviva_db com migrations e seeder após executar a suíte. Os resultados SQLite da E00/E01 permanecem somente como histórico.

Pagamentos podem conter um recibo/comprovante privado (PDF, JPG, PNG ou WebP até 20 MB), vinculado à própria parcela. Upload exige edição financeira e de documentos; download exige leitura de ambas. Anexo posterior é permitido quando ainda não existe arquivo, preservando o horário de pagamento. Comprovantes existentes não são sobrescritos. Detalhes e validação em COMPROVANTES-PAGAMENTO.md. Os testes específicos de comprovantes usam transações com rollback sobre o schema já migrado e não recriam tabelas; isso não altera o caráter destrutivo da suíte completa.
