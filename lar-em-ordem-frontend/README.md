# Lar em Ordem: Frontend

SPA em React 19 + Vite + Tailwind CSS 4 que consome a API Laravel. A instalação (com ou sem Docker) está no
[README da raiz](../README.md).


## Scripts

```bash
npm run dev       # servidor de desenvolvimento em http://localhost:5173
npm run lint      # ESLint (a CI falha se houver erros)
npm run build     # build de produção para dist/
npm run preview   # serve o build localmente
```


## Variáveis de ambiente

Copia o `.env.example` para `.env`. As variáveis `VITE_*` ficam embutidas no build, por isso nunca devem ter segredos.

| Variável | Para quê |
|---|---|
| `VITE_API_BASE_URL` | Endereço da API em desenvolvimento (`http://localhost:8000`). Em produção fica vazia: os pedidos vão para o próprio site e a Vercel reencaminha-os |
| `VITE_REVERB_*` | Ligação ao Reverb em desenvolvimento |
| `VITE_BROADCASTER`, `VITE_PUSHER_*` | Só em produção: `VITE_BROADCASTER=pusher` e a chave pública do Pusher |


## Estrutura

```
src/
├── pages/          # um ecrã por pasta, agrupados por perfil (Resident, ServiceProvider, Partner, CondominiumAdmin...)
├── components/     # componentes reutilizáveis (ui, form, layout, profile, condominium)
├── hooks/          # lógica dos ecrãs e pedidos à API (useHouses, useInvoices, usePagedList...)
├── services/
│   ├── api/        # um serviço por módulo da API, todos sobre o axiosClient
│   └── echo.js     # notificações em tempo real (Reverb em local, Pusher em produção)
├── context/        # sessão do utilizador (AuthContext) e casa selecionada (HousesContext)
├── routes/         # AppRoutes e as proteções: PrivateRoute, GuestRoute, RequireHouse
├── data/           # textos e configuração estática
└── utils/          # formatação, datas, erros da API, roles...
```


## Como fala com a API

- O `services/api/axiosClient.js` envia os cookies (`withCredentials`) e o token CSRF do Sanctum.
  Antes do login pede `/sanctum/csrf-cookie`.
- Se a sessão expira (401 ou 419), o utilizador é enviado para o login, exceto nos pedidos de autenticação.
- As mensagens de erro da API já chegam em português; o `utils/apiError.js` transforma qualquer erro
  (422, 403, 429, sem ligação...) numa mensagem para mostrar ao utilizador.
- As rotas protegidas usam o `PrivateRoute`, que verifica a sessão e o perfil ativo. A API valida sempre tudo outra vez:
  esconder um botão no frontend não é segurança.


## Docker

O `Dockerfile` tem duas etapas principais:
- `development`: usada pelo `compose.yml`, corre o `npm run dev` com o código montado do disco (hot reload);
- `runner`: o build de produção servido com o `serve`.


## Produção (Vercel)

O `vercel.json` define:
- **rewrites:** `/api`, `/sanctum`, `/auth` e os pedidos do Fortify (`/login`, `/logout`, `/forgot-password`,
  `/reset-password`, `/user/...`) vão para a API no Render; o resto vai para o `index.html` (rotas do React Router).
  As rotas do Fortify só são reencaminhadas quando vêm do Axios (header `X-Requested-With`), porque o React usa os
  mesmos caminhos para as suas páginas. Como tudo passa pelo mesmo domínio, o cookie de sessão é do próprio site;
- **headers de segurança:** `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` e HSTS.

As variáveis `VITE_*` de produção são definidas no painel da Vercel.