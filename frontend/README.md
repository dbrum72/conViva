# Frontend conViva

Vue 3, Vue Router, Vite, Axios e Pinia. O layout e os componentes genéricos foram adaptados da base local legalis.

- `src/state`: stores Pinia; estado limpo ao trocar grupo ou sair.
- `src/services`: chamadas Axios centralizadas.
- `src/views/care`: assistidos, cuidados, agenda, despesas e notificações.
- `src/assets/styles`: tema e estilos da aplicação.

Execute `npm install`, `npm run dev`. Valide com `npm test` e `npm run build`. A configuração local usa a API em http://127.0.0.1:8000/api e a interface em http://127.0.0.1:5186.

Consulte o README da raiz para permissões, banco e limites do ambiente local.
