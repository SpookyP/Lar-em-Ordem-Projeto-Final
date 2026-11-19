# 🏡 Lar em Ordem

Plataforma web para organizar tudo o que diz respeito à casa: faturas e consumos, documentos,
condomínio, pedidos de assistência e ofertas de parceiros, num só sítio.

> *Projeto académico produzido na ATEC - Academia de Formação*


## 🌟 Funcionalidades

Cada conta pode ter vários perfis (por exemplo, residente e prestador de serviços) e escolher com qual entra.

- **Residente**
  - Casas e contratos (proprietário ou inquilino).
  - Faturas: upload do PDF com extração automática dos dados e gráficos de consumo comparados com a média da região.
  - Cofre digital de documentos, com notificação na aplicação quando um documento está a expirar.
  - Condomínio: entra no prédio com o código de convite, vê os avisos e os documentos.
  - Pedidos de assistência (canalização, eletricidade...), com propostas dos prestadores.
  - Ofertas dos parceiros.
- **Prestador de serviços**: vê os pedidos da sua zona e especialidade, envia propostas e acompanha as aceites.
- **Parceiro**: publica e gere ofertas.
- **Administrador de condomínio**: gere prédios, documentos e avisos (enviados por email e em tempo real).
- **Conta e privacidade**
  - Registo com confirmação de email, ou com Google/Microsoft.
  - Recuperação de password.
  - Consentimentos RGPD e eliminação da conta com anonimização dos dados.


## 🧱 Tecnologias

| Parte | Tecnologia |
|---|---|
| API | Laravel 12 (PHP 8.4+), Fortify, Sanctum (sessão SPA), Spatie Permission |
| Frontend | React 19, Vite, Tailwind CSS 4, React Router, Axios |
| Base de dados | MySQL 8.4 (container) / TiDB (produção) |
| Tempo real | Laravel Reverb (container) / Pusher (produção) |
| Servidor web | nginx + PHP-FPM (container ou no Render) |
| Ficheiros | Cloudflare R2 |
| Extração de PDFs e médias de consumo | Scripts Python 3 |


## 🗂️ Estrutura

```
.
├── compose.yml             # ambiente de desenvolvimento (Docker / Podman)
├── docker/                 # nginx e php.ini usados pelo compose
├── lar-em-ordem-api/       # API Laravel  -> ver lar-em-ordem-api/README.md
└── lar-em-ordem-frontend/  # SPA React    -> ver lar-em-ordem-frontend/README.md
```

## 🖥️ Compatibilidade

| Sistema | Com Docker / Podman (recomendado) | Sem Docker |
|---|---|---|
| Linux | ✅ | ✅ |
| macOS (Intel e Apple Silicon) | ✅ Docker Desktop ou Podman Desktop | ✅ |
| Windows 10/11 | ✅ Docker Desktop ou Podman, ambos sobre o WSL 2 | ✅ com as alternativas indicadas para o Windows |

- As imagens usadas (PHP, MySQL, nginx e Node) existem para x86 e ARM, por isso funcionam também nos Mac com Apple Silicon.
- **Windows:** o WSL 2 tem de estar ativo. Com Podman, a máquina tem de estar criada e ligada
  (`podman machine init` e `podman machine start`), e o `podman compose` precisa do `docker-compose` ou do
  `podman-compose` instalado (o Podman Desktop instala-o).
- **Windows:** os scripts `.sh` estão guardados com fim de linha LF (`.gitattributes`), por isso os containers arrancam
  mesmo com o `core.autocrlf` do git ligado.
- **Windows:** o código é montado a partir do disco do Windows. O Vite usa polling para detetar alterações
  (`vite.config.js`), por isso o hot reload funciona, mas pode ser mais lento do que em Linux ou macOS.


## 🚀 Instalação com Docker / Podman (recomendado)

**Requisitos:** Git e um gestor de containers com Compose (Docker Desktop ou Podman).
Os comandos usam `podman compose`; com Docker é `docker compose`.

1. **Clonar o repositório**
   ```bash
   git clone https://github.com/SpookyP/Lar-em-Ordem-Projeto-Final.git
   cd Lar-em-Ordem-Projeto-Final
   ```

2. **Criar os `.env`** a partir dos exemplos e preencher o da API ([ver abaixo](#-variáveis-do-env))
   ```bash
   cp lar-em-ordem-api/.env.example lar-em-ordem-api/.env
   cp lar-em-ordem-frontend/.env.example lar-em-ordem-frontend/.env
   ```

3. **Arrancar**
   ```bash
   podman compose up -d --build
   ```

4. **Abrir** http://localhost:5173 e entrar com uma das [contas de demonstração](#-contas-de-demonstração).

O resto é automático:
- a imagem da API já traz as dependências PHP, o Python e os requisitos de `scripts/requirements.txt`;
- no primeiro arranque, o container `api` gera a `APP_KEY`, espera pelo MySQL, cria as tabelas e
  preenche-as com dados de demonstração (pode demorar alguns minutos: `podman compose logs -f api`);
- nos arranques seguintes só corre as migrações novas, e os dados ficam guardados.

| Serviço | Endereço | Para quê |
|---|---|---|
| `frontend` | http://localhost:5173 | SPA React (Vite, com hot reload) |
| `nginx` + `api` | http://localhost:8000 | API Laravel (PHP-FPM atrás do nginx) |
| `reverb` | ws://localhost:8080 | Notificações em tempo real |
| `mysql` | 127.0.0.1:3306 | Base de dados `laremordemdb` |
| `queue` | — | Envia emails e notificações em segundo plano |
| `scheduler` | — | Tarefas agendadas (avisos de validade, médias de consumo) |

**Comandos úteis**
```bash
podman compose logs -f api                                # ver os logs da API
podman compose exec api php artisan migrate:fresh --seed  # repor a base de dados de demonstração
podman compose down                                       # parar (os dados ficam guardados)
podman compose down -v                                    # parar e apagar a base de dados
```


## 🔧 Variáveis do `.env`

Com Docker, os valores do `.env.example` já servem para a base de dados, a sessão e o tempo real, e o `.env` do
frontend não precisa de alterações. No `lar-em-ordem-api/.env` só é preciso preencher:

| Variável | Quando | O que pôr |
|---|---|---|
| `MAIL_MAILER` | Sempre (o registo precisa de confirmar o email) | `log`: os emails ficam em `lar-em-ordem-api/storage/logs/laravel.log`, de onde se copia o link. `smtp`: envio real, com as quatro variáveis abaixo |
| `MAIL_HOST`, `MAIL_PORT` | Só com `smtp` | Servidor SMTP, ex.: Brevo, `smtp-relay.brevo.com` e `587` |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | Só com `smtp` | Login e chave SMTP do serviço |
| `MAIL_FROM_ADDRESS` | Sempre | Remetente dos emails, ex.: `noreply@laremordem.pt` (vazio, o envio falha) |
| `CLOUDFLARE_R2_ACCESS_KEY_ID`, `CLOUDFLARE_R2_SECRET_ACCESS_KEY` | Para o upload de faturas e documentos | Chaves de API do R2 (Cloudflare > R2 > Manage API tokens). Pedir à equipa |
| `CLOUDFLARE_R2_BUCKET` | Idem | Nome do bucket |
| `CLOUDFLARE_R2_ENDPOINT` | Idem | `https://<id-da-conta>.r2.cloudflarestorage.com` |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` | Opcional, login com Google | Credenciais OAuth do Google Cloud, com o redirect `http://localhost:8000/auth/google/callback` |
| `MICROSOFT_CLIENT_ID`, `MICROSOFT_CLIENT_SECRET` | Opcional, login com Microsoft | App registada no Microsoft Entra, com o redirect `http://localhost:8000/auth/microsoft/callback` |

A `APP_KEY` fica vazia: é gerada no primeiro arranque. As credenciais reais nunca vão para o git.

Sem Docker, muda também:

| Variável | O que pôr |
|---|---|
| `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD` | `127.0.0.1` e o utilizador da tua instalação MySQL (com a base `laremordemdb` já criada) |
| `REVERB_HOST` | `localhost` |
| `PYTHON_PATH` | Executável do Python: `python3` (Linux/macOS) ou `python` (Windows); `.venv/bin/python` se usares o venv |


## 💻 Instalação sem Docker

**Requisitos**
- PHP 8.4+ com as extensões `pdo_mysql`, `mbstring`, `exif`, `bcmath` e `gd`.
- Composer 2, Node.js 22, MySQL 8.4 e Python 3 com o pip.

```bash
# API
cd lar-em-ordem-api
cp .env.example .env           # e preencher (ver a tabela acima)
composer setup                 # composer install + requisitos Python + APP_KEY
php artisan migrate --seed

# Frontend
cd ../lar-em-ordem-frontend
cp .env.example .env
npm ci
```

> Se o pip recusar instalar no Python do sistema (comum em Linux), usa um venv: `sh scripts/setup_python.sh`
> e `PYTHON_PATH=.venv/bin/python` (ver o [README da API](lar-em-ordem-api/README.md#scripts-python-extração-de-pdfs)).

Depois, cada um num terminal:
```bash
# em lar-em-ordem-api
php artisan serve              # API em http://localhost:8000
php artisan reverb:start       # tempo real em ws://localhost:8080
php artisan queue:work         # emails e notificações
php artisan schedule:work      # tarefas agendadas

# em lar-em-ordem-frontend
npm run dev                    # http://localhost:5173
```

> Abre o site em `localhost` e não em `127.0.0.1`: o cookie de sessão está configurado para `localhost`.


## 👤 Contas de demonstração

Criadas pelo seeder. A password de todas é `password`.

| Email | Perfil |
|---|---|
| `morador@example.com` | Residente (com casa num condomínio) |
| `residente@example.com` | Residente e prestador de serviços |
| `prestador@example.com` | Prestador de serviços |
| `parceiro@example.com` | Parceiro |
| `condominio@example.com` | Administrador de condomínio (código de convite do prédio: `CAPELA23`) |


## 🧪 Testes

```bash
# API (com Docker): usa uma base SQLite em memória, como a CI, e não mexe nos dados de demonstração
podman compose exec -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: api php artisan test

# Frontend
cd lar-em-ordem-frontend
npm run lint
npm run build
```

> Sem as variáveis `DB_*`, os testes correm na base MySQL de desenvolvimento e apagam os dados de demonstração
> (repõe-nos com `php artisan migrate:fresh --seed`).

A CI (`.github/workflows/integrations.yaml`) corre os testes da API, o lint e o build do frontend em cada pull request para a `main`.


## ☁️ Produção

- **Frontend** na Vercel. O browser só fala com a Vercel, que reencaminha os pedidos à API para o Render
  (ver `lar-em-ordem-frontend/vercel.json`), por isso o cookie de sessão é do próprio site.
- **API** no Render, com `lar-em-ordem-api/Dockerfile.render`. Um só container corre nginx, PHP-FPM, a fila e o scheduler.
- **Serviços externos:** TiDB Cloud (MySQL), Cloudflare R2 (ficheiros), Pusher (tempo real) e Brevo (email).

As variáveis de produção estão descritas em `lar-em-ordem-api/.env.render.example` e
`lar-em-ordem-frontend/.env.example`. Os valores reais estão só no Render e na Vercel.


## ✍️ Autores

Este projeto foi realizado por:
- Madalena Ferreira
- Leonor Soares
- João Ribeiro
- Diogo Jarrais
- Rafael Praça