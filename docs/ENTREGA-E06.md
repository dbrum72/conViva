# E06 — Lembretes e preferências

O módulo Notifications reúne a caixa de entrada, o contador de não lidas, as preferências e o envio de lembretes. A tela de Notificações carrega o histórico em páginas de 25 itens. A API anterior mantém a resposta em lista quando chamada sem o parâmetro `page`.

Cada usuário ativa os canais interno e e-mail por grupo. Ambos começam desativados. A adesão a ocorrências de terceiros é independente e exige acesso de leitura vigente à área. As preferências incluem antecedência de 5 a 1.440 minutos, silêncio no fuso do grupo e resumo diário por área a partir das 18h. O silêncio suspende o envio enquanto está ativo. O resumo considera os eventos elegíveis no momento do processamento.

Os lembretes cobrem propostas pendentes de decisão e ocorrências publicadas próximas ou vencidas sem registro. A consulta de ocorrências vencidas considera o último dia. O job verifica novamente a inscrição, o vínculo, a área, a versão e a execução. Lembretes internos de propostas abrem a Central. Os de ocorrências abrem a data na Agenda. O e-mail usa texto genérico, sem nomes de assistidos ou detalhes de cuidados.

## Operação e validação

- Execute o scheduler e o worker Laravel em produção. `notifications:dispatch-reminders` enfileira a cada cinco minutos. `notifications:check-reminders` retorna falha quando deixa de observar o dispatcher ou, havendo inscritos, o worker por 15 minutos. O cache deve ser compartilhado entre os processos.
- A chave de entrega é única por grupo, usuário, evento e canal. Falhas do transporte acionam até três tentativas e são registradas sem conteúdo do cuidado. O aceite do transporte não confirma leitura. Se o transporte aceitar e a persistência falhar, o e-mail poderá ser reenviado.
- A migration foi aplicada no banco local. O envio foi validado com transporte em memória. A operação com SMTP real e worker contínuo depende da configuração do ambiente de implantação. Push web segue condicionado a infraestrutura e compatibilidade.
