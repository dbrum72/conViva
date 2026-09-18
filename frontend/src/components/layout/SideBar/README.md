# SideBar

Navegação lateral principal do conViva.

- Cabeçalho: nome do grupo ativo e foto pessoal do assistido (iniciais quando ausente). O responsável pode clicar no avatar para enviar, substituir ou remover somente sua própria foto. Não permite trocar de grupo.
- Corpo: SideBarNav. O item “Meus assistidos” é o único acesso da sidebar à seleção de assistido e criação de grupos.
- Rodapé: símbolo e nome do conViva.

O nome completo do grupo é preservado no atributo title quando não cabe no cabeçalho. O botão do avatar possui nome acessível e abre um AppDialog com prévia e explicação de privacidade. PersonalRecipientAvatar usa Pinia → serviço Axios; a foto é obtida como blob autenticado, sem URL pública ou token na URL. URLs locais são revogadas e respostas antigas descartadas ao trocar grupo ou sessão.

A abertura e o fechamento da sidebar são controlados pelo layout. Este componente não realiza chamadas de API nem mudanças de contexto.
