# Lar em Ordem: API

API em Laravel 12 que serve o frontend React. A instalação (com ou sem Docker) está no
[README da raiz](../README.md); aqui fica o que é preciso saber para trabalhar no código.


## Autenticação

- **Sanctum em modo SPA:** sessão com cookie e proteção CSRF, sem tokens no browser.
  O frontend pede `/sanctum/csrf-cookie` antes do primeiro `POST`.
- **Fortify:**
  - rotas já prontas: `/login`, `/logout`, `/forgot-password`, `/reset-password`, `/user/profile-information` e `/user/password`;
  - o registo do Fortify está desligado: usa-se `/api/auth/register`, que só cria a conta depois de o email ser confirmado;
  - 2FA e passkeys estão desligados em `config/fortify.php`.
- **Login social (Google e Microsoft):** `/auth/{provider}/redirect` e `/auth/{provider}/callback` em `routes/web.php`.
  O URL de callback a registar no Google/Microsoft é `APP_URL/auth/{provider}/callback`.
- **Perfis:** são roles do Spatie Permission (`resident`, `service_provider`, `partner`, `condominium_admin`).
  Cada perfil tem a sua tabela com os dados próprios e uma conta pode ter vários.


## Estrutura

```
app/
├── Actions/Fortify/     # registo, perfil e passwords (validação incluída)
├── Http/
│   ├── Controllers/     # finos: validam, autorizam e chamam um serviço
│   ├── Requests/        # FormRequests com regras e mensagens em pt-PT
│   └── Resources/       # o JSON devolvido ao frontend (escondem campos sensíveis)
├── Services/            # lógica de negócio, um por módulo
├── Policies/            # quem pode ver/alterar cada recurso
├── Models/              # agrupados por módulo
├── Notifications/       # emails (confirmação de registo, reset de password, avisos)
├── Events/              # eventos em tempo real (Reverb/Pusher)
├── Jobs/ Console/       # tarefas agendadas
└── Support/             # utilitários pequenos (ex.: Search, para pesquisas LIKE seguras)
routes/
├── api.php              # /api/v1/..., inclui os módulos de routes/modules/
├── modules/             # uma rota por módulo: auth, property, invoice, vault, condominium...
└── web.php              # login social e /forgot-password com rate limit
lang/pt_PT/              # todas as mensagens da API em português de Portugal
scripts/                 # scripts Python (extração de PDFs e médias de consumo)
```

**Convenções**
- Todas as rotas de `/api/v1` exigem sessão (`auth:sanctum`), exceto o catálogo público (zonas, categorias e health).
- Cada `throttle` tem um nome próprio (ex.: `throttle:10,1,register`). Sem nome, as rotas partilhariam o mesmo contador.
  A `RouteProtectionTest` falha se uma rota pública ficar sem limite.
- Ficheiros sempre através do `FileStorageService` (disco `r2`). Os ficheiros pertencem ao utilizador que os enviou.
- Os dados pessoais são apagados ou anonimizados pelos serviços em `app/Services/Privacy` (RGPD).


## Tarefas em segundo plano

| Processo | Comando | O que faz |
|---|---|---|
| Fila | `php artisan queue:work` | Envia os emails e as notificações |
| Scheduler | `php artisan schedule:work` | Verifica validades de documentos (todos os dias às 08:00) e calcula as médias de consumo (dia 15 de cada mês e 1 de fevereiro) |
| Reverb | `php artisan reverb:start` | WebSockets para as notificações em tempo real (só em local; em produção é o Pusher) |

No Docker, cada um tem o seu container (`queue`, `scheduler`, `reverb`).


## Scripts Python (extração de PDFs)

Usados pelo Cofre Digital (extração da data de validade), pela extração de faturas e pelo cálculo das médias de consumo.
Requerem **Python 3** no ambiente onde o PHP corre. No Docker já vêm instalados. Sem Docker, o `composer setup`
instala-os com o pip; se o pip recusar instalar no Python do sistema (comum em Linux), usa um venv:

```bash
sh scripts/setup_python.sh
```

Isto cria o `.venv/` (ignorado pelo git) com as dependências de `scripts/requirements.txt`.
No `.env`, aponta o `PYTHON_PATH` para ele: `.venv/bin/python` (no Windows: `.venv\Scripts\python.exe`).

> No Windows sem `sh`: `python -m venv .venv` e `.venv\Scripts\pip install -r scripts\requirements.txt`.

**Sem Python?** O upload de documentos funciona na mesma, mas as datas (`issue_date`, `expiration_date`)
têm de ser preenchidas à mão (a API devolve `extraction.success = false`).


## Variáveis de ambiente

As variáveis que é preciso preencher, e o que cada uma espera, estão no [README da raiz](../README.md#-variáveis-do-env).
As de produção estão em `.env.render.example`. Nunca coloques valores reais no git.


## Testes

```bash
php artisan test                                   # todos
php artisan test --filter AccountFormsTest         # um ficheiro
```

Os testes usam a base definida no `.env`. Para não apagar os dados de demonstração, corre-os com SQLite em memória,
como a CI:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test
```

- `tests/Feature/`: pedidos HTTP completos, por módulo.
- `tests/Unit/`: regras e serviços isolados.
- `tests/Feature/Security/`: protege a tabela de rotas (autenticação e rate limits).


## Produção (Render)

O `Dockerfile.render` cria uma imagem com nginx, PHP-FPM, a fila e o scheduler, geridos pelo supervisord
(`docker/render/`). Ao arrancar, o `start.sh` corre as migrações e faz cache da configuração, das rotas e das views.