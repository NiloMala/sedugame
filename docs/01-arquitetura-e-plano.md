# Expedição do Saber — Arquitetura e Plano de Implementação

> Deriva de [00-spec-original.md](./00-spec-original.md). Reconcilia o brief pedagógico/funcional com a infraestrutura real disponível. Última atualização: 2026-08-05.

## 1. Repositório

Monorepo único: **https://github.com/NiloMala/sedugame.git** (branch principal `main`), com a seguinte estrutura na raiz:

```
sedugame/
├── docs/       ← este diretório (arquitetura, schema, contrato de API, histórico de revisões do frontend)
├── backend/    ← projeto Laravel 11 (eu)
├── app/, components/, lib/, public/  ← projeto Next.js (GPT), na raiz do repo
├── package.json, next.config.mjs, tsconfig.json, tailwind.config.ts  ← config do Next.js
```

> **Nota (2026-08-06)**: o plano original previa o Next.js dentro de `frontend/`. Na prática o GPT gerou o projeto direto na raiz do repositório, e por simplicidade decidimos manter assim em vez de reorganizar — reduziria risco reescrever imports/config à toa. `backend/` continua isolado na sua própria pasta. Um `.gitignore` só na raiz cobre `backend/vendor`, `backend/.env`, `node_modules/`, `.next/`, etc.

> **Nota (2026-08-06)**: domínio real definido — `educacaocaraguatatuba.com.br`, com o projeto publicado sob o subdomínio/pasta `bora` na VPS (`/home/educacaocaraguatatuba.com.br/domains/bora`). Isso substitui `SEUDOMINIO` nos exemplos abaixo. Ver também decisão de roteamento único-domínio logo adiante nesta seção.

## 2. Decisões que divergem do brief original

O brief original recomenda Next.js + Laravel + PostgreSQL/PostGIS + Redis + Docker. A infraestrutura real é: VPS com **CyberPanel** (OpenLiteSpeed), banco **MySQL/MariaDB gerenciado** (phpMyAdmin), **Cloudflare** na frente (DNS proxied + SSL), acesso **SSH completo**, sem Redis provisionado. Além disso, o frontend será construído **em paralelo por outra IA (GPT)**, o que exige separação real entre back e front.

| Item | Brief original | Decisão final | Motivo |
|---|---|---|---|
| Frontend | Next.js (implícito acoplado) | Next.js em **pasta própria (`frontend/`) do mesmo monorepo**, consumindo API | Permite o GPT construir em paralelo sem depender do código Laravel, sem precisar gerenciar dois repositórios |
| Comunicação front/back | Não especificada | **API REST pura** (Laravel Sanctum, auth por cookie SPA) | Necessário para dois codebases independentes |
| Banco de dados | PostgreSQL + PostGIS | **MySQL/MariaDB 8+** (o gerenciado via phpMyAdmin) | É o banco já contratado; não há Postgres disponível |
| Cálculo geográfico | PostGIS nativo | **Fórmula de Haversine** em PHP, sobre colunas `decimal(10,7)` lat/lng | O próprio brief já previa essa alternativa (seção 14); evita dependência de extensão espacial |
| Cache / Queue / Session | Redis | Driver **`database`** | Não há Redis no servidor hoje; dá para migrar depois sem mudar código de negócio (só o `.env`) |
| Deploy | Docker | **SSH direto** (git pull + composer + PM2) | CyberPanel gerencia PHP nativamente via OpenLiteSpeed; não há orquestração Docker no plano |
| WebSockets (modo ao vivo) | Citado na stack geral | **Adiado para Fase 2** | O próprio brief já classifica "modo professor ao vivo" como Fase 2 (seção 52); evita provisionar Redis/Reverb antes de validar o MVP |
| Hospedagem do Next.js | Não especificada | **Mesmo VPS do CyberPanel**, via Node + PM2 atrás do OpenLiteSpeed | Confirmado com o cliente — tudo num único servidor |

Tudo que não está nesta tabela segue o brief original sem alteração.

## 3. Stack final

**Backend**
- Laravel 11.x, PHP 8.3
- Laravel Sanctum (auth SPA por cookie, mesmo domínio raiz)
- MySQL 8 / MariaDB
- Cache, sessão e fila no driver `database`
- Laravel Scheduler (cron) + `queue:work` supervisionado
- Integração OpenAI API (assistente de conteúdo, nunca publica sem revisão — seção 25 do brief)

**Frontend** (pasta `frontend/` do mesmo monorepo, construído com apoio do GPT — ver [04-prompt-frontend-gpt.md](./04-prompt-frontend-gpt.md))
- Next.js 14+ (App Router), TypeScript
- Tailwind CSS
- MapLibre GL JS + OpenStreetMap (nunca Google Maps)
- TanStack Query (React Query) + Zustand
- PWA (Fase 2, mas estruturar desde já)

**Infraestrutura**
- 1 VPS com CyberPanel (OpenLiteSpeed)
- 2 subdomínios: `api.SEUDOMINIO` (Laravel) e `app.SEUDOMINIO` (Next.js)
- Node.js + PM2 para o processo do Next.js
- Cloudflare: DNS proxied (nuvem laranja), SSL/TLS em modo **Full (strict)**
- Storage: local (`storage/app/public`) no MVP; Cloudflare R2 fica como opção futura se o volume de mídia crescer

## 4. Arquitetura de infraestrutura

> **Atualização (2026-08-06)**: em vez dos dois subdomínios (`app.*`/`api.*`) previstos originalmente, a decisão final foi **um único domínio com dois caminhos**: `bora.educacaocaraguatatuba.com.br`. O OpenLiteSpeed encaminha `/api/*` e `/sanctum/*` para o Laravel (via contexto PHP/LSAPI nativo, servindo `backend/public`) e tudo o mais para o Next.js (via proxy reverso para o processo PM2 em `127.0.0.1:3000`). Motivo: elimina CORS entre front e back (mesma origem), simplifica cookie de sessão do Sanctum (não precisa de `SESSION_DOMAIN` com ponto líder nem lista de domínios stateful) e usa só 1 registro DNS/certificado em vez de 2.

```
Cloudflare (DNS proxied · SSL Full Strict)
   │
   └── bora.educacaocaraguatatuba.com.br ───► VPS (OpenLiteSpeed via CyberPanel)
                                                  │
                                                  ├── /api/* , /sanctum/* ──► Laravel (PHP 8.3 LSAPI, docroot backend/public)
                                                  │                              │
                                                  │                              ├── MySQL/MariaDB (gerenciado, phpMyAdmin)
                                                  │                              ├── Storage local (storage/app/public + symlink)
                                                  │                              └── Cron: * * * * * php artisan schedule:run
                                                  │
                                                  └── /* (resto) ──► proxy reverso ──► 127.0.0.1:3000 (Next.js via PM2)
```

Como front e back agora são a **mesma origem**, a autenticação do Sanctum usa cookies httpOnly com `SESSION_DOMAIN=null` (escopo automático pro host exato) — mais simples que o cenário multi-subdomínio, e ainda mais seguro contra XSS que token em `localStorage`. `config/cors.php` continua existindo (adicionado em 2026-08-06 — estava faltando e quebrava qualquer chamada cross-origin, inclusive em dev local) como defesa em profundidade, mas deixa de ser estritamente necessário em produção por conta da mesma origem.

## 5. Checklist de infraestrutura — ✅ feito em produção em 2026-08-06

Caminho real do projeto na VPS: `/home/educacaocaraguatatuba.com.br/domains/bora` (não `bora.educacaocaraguatatuba.com.br/` — essa pasta existia por padrão do CyberPanel mas foi só um estágio intermediário; o `docRoot` do vhost aponta pra dentro de `domains/bora/backend/public`). Dono dos arquivos: usuário do sistema `educa3642` (o mesmo que roda o PHP via LSAPI — rodar `composer`/`npm`/`artisan` sempre como esse usuário, via `sudo su - educa3642`, nunca como o usuário pessoal do admin).

1. CyberPanel → site `bora.educacaocaraguatatuba.com.br` criado, SSL emitido (Let's Encrypt, já configurado em `vhssl` no vhost.conf).
2. PHP 8.3 (`lsphp83`) ativo pro site.
3. Cloudflare → DNS proxied (nuvem laranja) + SSL/TLS Full Strict.
4. MySQL: banco + usuário dedicados via phpMyAdmin (não root).
5. Node.js 20 + PM2 instalados; app registrado com `pm2 start npm --name bora-frontend -- start -- -p 3000 -H 127.0.0.1`, `pm2 save`, e `pm2 startup systemd -u educa3642 --hp /home/educacaocaraguatatuba.com.br` rodado como root (`nilton.prado`, via sudo) pra sobreviver a reboot.
6. **`vhost.conf` do OpenLiteSpeed** — a parte que mais deu trabalho, documentada em detalhe abaixo.

### 5.1 `vhost.conf` final que funciona (roteamento de domínio único)

Depois de várias tentativas erradas (guardadas aqui como aviso pra não repetir — ver seção 5.2), a combinação que funcionou:

- `docRoot` aponta direto pro `backend/public` (Laravel é o handler "padrão" do vhost inteiro).
- **Nenhum** rewrite `[P]` no bloco `rewrite {}` (essa abordagem nunca funcionou nesse OpenLiteSpeed/CyberPanel — sempre dava `Proxy target is not defined on external application list`, mesmo testando `type proxy` e `type webserver` no `extprocessor`, e mesmo com a sintaxe heredoc `<<<END_rules ... END_rules` correta).
- Em vez disso, **Contexts** explícitos: `/api` e `/sanctum` (PHP, apontando pro mesmo `backend/public`, com rewrite interno pra `index.php`) e `/` (tipo `proxy`, referenciando um `extprocessor` nomeado do tipo `proxy` — esse padrão, via Context + `handler`, funciona; o rewrite `[P]` direto não).

```
docRoot                   /home/educacaocaraguatatuba.com.br/domains/bora/backend/public
vhDomain                  $VH_NAME
vhAliases                 www.$VH_NAME
adminEmails               informatica.educativa@caraguatatuba.sp.gov.br
enableGzip                1
enableIpGeo               1

index  {
  useServer               0
  indexFiles              index.php, index.html
}

errorlog $VH_ROOT/logs/educacaocaraguatatuba.com.br.error_log {
  useServer               0
  logLevel                WARN
  rollingSize             10M
}

accesslog $VH_ROOT/logs/educacaocaraguatatuba.com.br.access_log {
  useServer               0
  logFormat               "%h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\""
  logHeaders              5
  rollingSize             10M
  keepDays                10
  compressArchive         1
}

phpIniOverride  {

}

scripthandler  {
  add                     lsapi:educa36425512 php
}

extprocessor educa36425512 {
  type                    lsapi
  address                 UDS://tmp/lshttpd/educa36425512.sock
  maxConns                10
  env                     LSAPI_CHILDREN=10
  initTimeout             60
  retryTimeout            0
  persistConn             1
  pcKeepAliveTimeout      1
  respBuffer              0
  autoStart               1
  path                    /usr/local/lsws/lsphp83/bin/lsphp
  extUser                 educa3642
  extGroup                educa3642
  memSoftLimit            2047M
  memHardLimit            2047M
  procSoftLimit           400
  procHardLimit           500
}

extprocessor nextjs-bora {
  type                    proxy
  address                 127.0.0.1:3000
  maxConns                100
  initTimeout             60
  retryTimeout            0
  respBuffer              0
}

rewrite  {
  enable                  1
  autoLoadHtaccess        1
}

context /.well-known/acme-challenge {
  location                /usr/local/lsws/Example/html/.well-known/acme-challenge
  allowBrowse             1
  rewrite  {
    enable                  0
  }
  addDefaultCharset       off
  phpIniOverride  {

  }
}

context /api {
  location                /home/educacaocaraguatatuba.com.br/domains/bora/backend/public
  allowBrowse             1
  rewrite  {
    enable                  1
    rules                    RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
  }
  addDefaultCharset       off
  phpIniOverride  {

  }
}

context /sanctum {
  location                /home/educacaocaraguatatuba.com.br/domains/bora/backend/public
  allowBrowse             1
  rewrite  {
    enable                  1
    rules                    RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
  }
  addDefaultCharset       off
  phpIniOverride  {

  }
}

context / {
  type                    proxy
  handler                 nextjs-bora
  addDefaultCharset       off
}

vhssl  {
  keyFile                 /etc/letsencrypt/live/bora.educacaocaraguatatuba.com.br/privkey.pem
  certFile                /etc/letsencrypt/live/bora.educacaocaraguatatuba.com.br/fullchain.pem
  certChain               1
  sslProtocol             24
  enableECDHE             1
  renegProtection         1
  sslSessionCache         1
  enableSpdy              15
  enableStapling           1
  ocspRespMaxAge           86400
}
```

**Não** tem bloco `module cache { storagePath ... }` (LSCache) — foi removido de propósito. Ele guardava respostas de rotas de API/autenticadas (`/api/*`, `/sanctum/*`) em cache no próprio servidor, o que além de causar bugs de "resposta desatualizada" durante o próprio deploy, é um risco real de segurança num app com sessão (poderia vazar resposta de um usuário pra outro). O Next.js já faz o próprio cache de página; não precisa de mais uma camada aqui.

### 5.2 A pegadinha que mais custou tempo: `SCRIPT_NAME` e o front controller do Laravel

Mesmo com os Contexts `/api`/`/sanctum` corretos (acima), toda chamada pra essas rotas dava **404 do próprio Laravel** (`Route [login] not defined` ou `The route campaigns could not be found`) — a rota existia (`php artisan route:list` confirmava), mas o framework recebia o caminho **sem o prefixo** (`/campaigns` em vez de `/api/campaigns`).

Causa: quando o OpenLiteSpeed serve um Context que "monta" o app num sub-caminho (aqui, `/api` e `/sanctum`, dentro de um vhost cujo domínio principal serve outra coisa), ele define `SCRIPT_NAME`/`PHP_SELF` refletindo esse sub-caminho. O Symfony (por baixo do Laravel) usa isso pra calcular a "base URL" do app e subtrai esse prefixo da URI antes de rotear.

Correção, em [backend/public/index.php](../backend/public/index.php): forçar `$_SERVER['SCRIPT_NAME']` e `$_SERVER['PHP_SELF']` pra `/index.php` incondicionalmente, antes de `Request::capture()`. Como esse arquivo está sempre fisicamente na raiz de `public/`, esse valor é sempre correto — em dev local, em produção normal, ou nesse cenário de Context. Não quebra nenhum outro ambiente (38 testes locais continuam passando).

### 5.3 Tentativas que NÃO funcionaram (não repetir)

- `rewrite { rules RewriteRule ^(.*)$ http://127.0.0.1:3000$1 [P,L] }` (com ou sem sintaxe heredoc, com `extprocessor` do tipo `proxy` ou `webserver` nomeado igual ao endereço) — sempre deu `[REWRITE] Proxy target is not defined on external application list`. Não descobrimos a sintaxe certa; a alternativa via Context (`type proxy` + `handler`) funciona e foi o que ficou.
- Context `/` (proxy) **sem** Context dedicado pra `/api`/`/sanctum` — o Context `/` vira o "pega tudo" e engole as rotas de API também, já que nada mais específico as intercepta antes.
- `module cache` (LSCache) ativo no mesmo vhost que serve API autenticada — cache fica dessincronizado do código real e é risco de vazamento de dado entre usuários.

### Deploy do Laravel (referência, depois do setup inicial acima)
```bash
sudo su - educa3642
cd /home/educacaocaraguatatuba.com.br/domains/bora
git pull
cd backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache
```
`.env` relevante (não sobrescrever depois da primeira vez — `.env` não vem do git):
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://bora.educacaocaraguatatuba.com.br
SANCTUM_STATEFUL_DOMAINS=bora.educacaocaraguatatuba.com.br
SESSION_DOMAIN=null
CORS_ALLOWED_ORIGINS=https://bora.educacaocaraguatatuba.com.br
DB_CONNECTION=mysql
QUEUE_CONNECTION=database
CACHE_STORE=database
```
> Nota: `DatabaseSeeder` roda também `CaraguatatubaCampaignSeeder` (conteúdo de demonstração, ~8 questões — não é o conteúdo final do MVP, que ainda depende da Sprint 4). Em produção semeamos só os catálogos (`RoleSeeder`, `LevelSeeder`, `GradeSeeder`, `SubjectSeeder`, `SchoolYearSeeder`) via `--class=`, sem o conteúdo de demonstração.

Cron (pendente confirmar se já está ativo — ver seção 9):
```
* * * * * php /home/educacaocaraguatatuba.com.br/domains/bora/backend/artisan schedule:run >> /dev/null 2>&1
```

### Deploy do Next.js (referência)
```bash
sudo su - educa3642
cd /home/educacaocaraguatatuba.com.br/domains/bora
git pull
npm ci
echo "NEXT_PUBLIC_API_URL=https://bora.educacaocaraguatatuba.com.br" > .env.production
npm run build   # NEXT_PUBLIC_* é embutido no bundle NO BUILD — precisa do .env.production antes desse passo
pm2 restart bora-frontend
pm2 save
```

### Cloudflare — regra de cache obrigatória

Sem isso, respostas de `/api/*`/`/sanctum/*` podem ficar presas em cache do CDN (foi um dos bugs que mais confundiu durante o deploy). Cloudflare → **Caching → Cache Rules**:
```
(starts_with(http.request.uri.path, "/api/")) or (starts_with(http.request.uri.path, "/sanctum/"))
→ Elegibilidade de cache: Ignorar cache (Bypass)
```

## 6. Divisão de trabalho

**Eu (backend Laravel)** entrego:
- Projeto Laravel 11 completo: models, migrations, controllers, form requests, policies, API resources
- Regras de pontuação, distância (Haversine), progressão de XP/níveis, conquistas
- Seeders das 3 campanhas do MVP (~120 questões)
- O contrato de API em [03-api-contract.md](./03-api-contract.md) — **é a fonte da verdade que o frontend segue**

**GPT (frontend Next.js)**, via o prompt pronto em [04-prompt-frontend-gpt.md](./04-prompt-frontend-gpt.md), entrega:
- Projeto Next.js consumindo a API acima
- Todas as telas da seção 56/57 do brief
- Componentes de gamificação (barra de XP, passaporte, mapa MapLibre, conquistas, tela de missão)

**Ponto crítico de sincronismo**: qualquer mudança de endpoint/formato precisa ser refletida nos dois lados. Sempre que eu alterar o contrato de API, aviso explicitamente para você repassar ao GPT.

## 7. Roadmap

### Fase 1 — MVP (baseado na seção 50 do brief)

| Sprint | Entregas | Status |
|---|---|---|
| 1 — Fundação | Setup Laravel + Next.js, ambientes dev/hml/prod, Sanctum, roles/permissions (aluno, professor, admin), CRUD de escolas/turmas/disciplinas/habilidades | ✅ Concluído (2026-08-06) |
| 2 — Núcleo pedagógico + Gameplay | Modelagem campanha → missão → etapa → questão; CRUD admin (com options/hints/location aninhados); fluxo de tentativa completo (8 tipos de questão); pontuação (Haversine + pistas + tempo + sequência); XP/níveis/conquistas; passaporte; seed de demonstração (campanha "Conhecendo Caraguatatuba", 3 missões, 8 questões) | ✅ Concluído (2026-08-06) — 20 testes de feature passando |
| 3 — Painéis e relatórios | `/api/teacher/*` (turmas, atividades + resultados, relatório de turma + export CSV); `/api/reports/*` (escola e rede, com habilidades críticas, taxa de participação/conclusão) | ✅ Concluído (2026-08-06) — 29 testes de feature passando |
| 3.5 — Gamificação avançada | Sequência de dias (streak_days, antes só uma coluna não usada); colecionáveis (brief seção 18 — nunca existia); avatar ilustrado com acessório desbloqueável (nunca foto real, decisão LGPD/ECA); recompensa de missão/conquista modelada (`reward_collectible_item_id`) | ✅ Concluído (2026-08-07) — 38 testes de feature passando |
| 4 — Polimento MVP | Export pdf/xlsx (hoje só csv); CRUD admin de `achievements`/`levels`/`collectible_items`; testes E2E; revisão de conteúdo real (as ~120 questões — trabalho pedagógico, não só código); deploy produção no CyberPanel | Deploy em produção ✅ concluído (2026-08-06, ver seção 5); CRUD admin de achievements/levels/collectible-items ✅ concluído (2026-08-06); export pdf/xlsx ✅ concluído (2026-08-06); teste E2E (nível HTTP) da jornada completa ✅ concluído (2026-08-06, `FullJourneyE2ETest` — achou e corrigiu 2 gaps reais: faltava endpoint pra vincular professor↔turma↔disciplina, e `StudentPasswordController` não checava escola do aluno); 63 testes de feature passando; falta revisão de conteúdo real e E2E de navegador (Playwright/Cypress, decisão de infra à parte) |

Escopo do MVP, público e volume de conteúdo seguem exatamente a seção 50 do brief (3 campanhas, 6º/7º anos, ~120 questões) — o seed atual (~8 questões) é só prova de que o pipeline funciona ponta a ponta, não o conteúdo final.

### Fase 2 e Fase 3
Seguem as seções 52 e 53 do brief sem alteração. Revisamos o escopo detalhado quando o MVP estiver validado com uso real — não vale a pena planejar em detalhe agora algo que pode mudar com o feedback de professores/alunos.

## 8. Segurança e LGPD (aplicado desde o Sprint 1, não deixado para o final)

Da seção 49 do brief, os itens que viram requisito de arquitetura desde o início:
- Senhas com hash (`bcrypt`/`argon2`, padrão Laravel);
- HTTPS obrigatório (garantido pelo Cloudflare Full Strict + AutoSSL);
- Rate limiting nas rotas de auth e nas de resposta de questão (evita brute-force e flood de tentativas);
- Ranking nominal **opt-in**, nunca exibido por padrão;
- `audit_logs` desde o Sprint 1 para ações administrativas e de publicação de conteúdo;
- Exclusões administrativas via soft delete (`deleted_at`), não hard delete.

> **Pendência de segurança (achada em 2026-08-06 via `composer audit`, ao instalar dompdf/excel — não é regressão minha, é do framework)**: `laravel/framework` 11.x tem 3 advisories abertos, um com CVE (`CVE-2026-48019`, injeção CRLF na regra de validação de e-mail padrão). O patch só existe a partir do Laravel 12.60+ — não tem backport pro 11.x. Corrigir de verdade exige upgrade de major version (11→12, possivelmente →13), com toda a regressão que isso implica; não é algo pra fazer de passagem. Fica registrado aqui como item a priorizar antes do MVP ir a público em escala, não crítico pro estágio atual (piloto/demo).

## 9. Perguntas em aberto (não bloqueiam o início, mas precisam de resposta até o Sprint 2)

- Nome de domínio real (para substituir `SEUDOMINIO` nos exemplos acima).
- Chave da OpenAI API (para as features de IA assistiva do professor — seção 25 do brief).
- Login simplificado do aluno (brief seção 37 cita isso, mas não define o mecanismo — usuário/senha curta gerado pela escola? Login via código de turma?).
- Existe sistema escolar (SED, ou similar) para integração futura (seção 36), ou cadastro será 100% manual no MVP?
