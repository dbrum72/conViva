# Foto pessoal do assistido

Entrega de 18/09/2026, solicitada antes de retomar E02.

O responsável clica no avatar da sidebar para selecionar, visualizar e salvar uma foto do assistido. Pode substituir ou remover sua imagem. O diálogo explica que a escolha aparece somente para ele. Ausência de imagem usa as iniciais do grupo. O nome do grupo continua visível e a seleção/criação de grupos permanece em Meus assistidos.

Não é uma revisão cadastral compartilhada: não gera proposta, aceite ou notificação para os demais. Cada par usuário/assistido tem um registro próprio em `care_recipient_avatars`, com índice único. A nova tabela foi criada por migration própria, aplicada isoladamente após verificar o banco efetivo `conviva_db`, sem reconstrução do banco.

GET, POST e DELETE `/api/my-recipient-avatar` determinam o usuário autenticado e o assistido do grupo ativo no servidor. Exigem perfil responsável, vínculo ativo e acesso vigente ao assistido ativo. Não aceitam identificador de outro autor para escolha da foto. O contexto retorna `can_manage_avatar` para apresentação do botão.

Arquivos JPG, PNG e WebP até 2 MB e 4096 × 4096 pixels são validados no Laravel e armazenados no disco privado. Não há URL pública. A leitura autenticada retorna `private, no-store` e `nosniff`. Substituição e remoção excluem o arquivo anterior. Falha de persistência limpa o novo arquivo. Bloqueio transacional por usuário serializa substituições; concorrência real MySQL não foi ensaiada.

Frontend segue componente → Pinia → Axios. A prévia local não substitui a foto persistida antes da confirmação do servidor. Troca de grupo/sessão limpa imagens e invalida respostas antigas; URLs de blob são revogadas.

Validação: suíte backend de 48 testes/347 asserções aprovada antes da última ampliação; os quatro testes finais de avatar passaram com 62 asserções, cobrindo isolamento entre responsáveis e grupos, substituição/remoção, arquivo inválido, expiração, suspensão e falta de acesso. Frontend: 27 testes aprovados, incluindo respostas atrasadas e falha de upload. Build de produção aprovado. Testes PHP usam SQLite em memória e armazenamento simulado; não foi realizado envio pelo navegador nesta entrega.
