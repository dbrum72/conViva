# Foto pessoal do assistido

Entrega de 18/09/2026, solicitada antes de retomar E02.

O responsável clica no avatar da sidebar para selecionar, visualizar e salvar uma foto do assistido. Pode substituir ou remover sua imagem. O diálogo explica que a escolha aparece somente para ele. Ausência de imagem usa as iniciais do grupo. O nome do grupo continua visível e a seleção/criação de grupos permanece em Meus assistidos.

Não é uma revisão cadastral compartilhada: não gera proposta, aceite ou notificação para os demais. Cada par usuário/assistido tem um registro próprio em `care_recipient_avatars`, com índice único. A nova tabela foi criada por migration própria, aplicada isoladamente após verificar o banco efetivo `conviva_db`, sem reconstrução do banco.

GET, POST e DELETE `/api/my-recipient-avatar` determinam o usuário autenticado e o assistido do grupo ativo no servidor. Exigem perfil responsável, vínculo ativo e acesso vigente ao assistido ativo. Não aceitam identificador de outro autor para escolha da foto. O contexto retorna `can_manage_avatar` para apresentação do botão.

Arquivos JPG, PNG e WebP até 2 MB e 4096 × 4096 pixels são validados no Laravel e armazenados no disco privado. Não há URL pública. A leitura autenticada retorna `private, no-store` e `nosniff`. Substituição e remoção excluem o arquivo anterior. Falha de persistência limpa o novo arquivo. Bloqueio transacional por usuário serializa substituições; concorrência real MySQL não foi ensaiada.

Frontend segue componente → Pinia → Axios. A prévia local não substitui a foto persistida antes da confirmação do servidor. Troca de grupo/sessão limpa imagens e invalida respostas antigas; URLs de blob são revogadas.

Validação original: suíte backend de 48 testes e 347 asserções. Quatro testes finais de avatar passaram com 62 asserções, cobrindo isolamento entre responsáveis e grupos, substituição, remoção, arquivo inválido, expiração, suspensão e falta de acesso. No frontend passaram 27 testes e o build. Os testes PHP dessa entrega usaram SQLite em memória e armazenamento simulado.

Ajuste de 28/09/2026: o cartão em Assistido do grupo reutiliza a foto pessoal da sidebar pela mesma store Pinia. Substituir ou remover a foto atualiza ambas as exibições; o cartão só mostra a imagem no grupo ativo autorizado. Sem foto, mantém o marcador de identificação. Não há nova requisição ou URL pública.

## Exibição e recorte em 15/11/2026

- `frontend/src/modules/care/components/RecipientAvatar.vue` compartilha a apresentação da foto na sidebar, em Meus assistidos e no cartão de Assistido do grupo. Em Meus assistidos, a alternativa à foto é a primeira letra do nome em maiúscula. A sidebar usa as iniciais do grupo e o cartão Assistido do grupo preserva seu marcador de identificação.
- Meus assistidos consulta a foto pessoal de cada grupo elegível com o cabeçalho `X-Tenant` daquele grupo. A consulta preserva o grupo selecionado. A rota autenticada aplica a autorização já existente. A lista cancela consultas pendentes e revoga URLs locais ao atualizar os grupos ou sair da tela.
- O editor `PersonalRecipientAvatar.vue` usa `AppFileUpload` e mantém a imagem escolhida no navegador durante o ajuste. Zoom e posição definem o recorte quadrado. Ao salvar, envia o recorte como PNG de até 512 × 512 pixels pela rota privada existente. Fotos anteriores permanecem disponíveis.
- Os quatro testes do avatar passaram, incluindo a conversão do enquadramento em PNG. A suíte do frontend chegou a 87 testes após a migração de formulários. O build de produção passou.
