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

```
Cloudflare (DNS proxied · SSL Full Strict)
   │
   ├── app.SEUDOMINIO  ───────────► VPS:3000 (Next.js via PM2)
   │                                    reverse proxy no OpenLiteSpeed
   │
   └── api.SEUDOMINIO  ───────────► VPS (Laravel · PHP 8.3-FPM/LSAPI via CyberPanel)
                                          │
                                          ├── MySQL/MariaDB (gerenciado, phpMyAdmin)
                                          ├── Storage local (storage/app/public + symlink)
                                          └── Cron: * * * * * php artisan schedule:run
```

Como `app.*` e `api.*` compartilham o mesmo domínio raiz, a autenticação do Sanctum pode usar **cookies httpOnly** em vez de token em `localStorage` — mais seguro contra XSS e sem necessidade de gerenciar refresh token manualmente. Isso é uma decisão de segurança, não só de conveniência.

## 5. Checklist de infraestrutura (execução manual sua, no CyberPanel/Cloudflare)

1. CyberPanel → criar subdomínio `api.SEUDOMINIO` (site Laravel).
2. CyberPanel → criar subdomínio `app.SEUDOMINIO` (site proxy para o Node).
3. CyberPanel → emitir SSL (AutoSSL/Let's Encrypt) para os dois subdomínios.
4. CyberPanel → definir PHP 8.3 como versão ativa do site `api.SEUDOMINIO`.
5. Cloudflare → criar registros DNS (A ou CNAME) para `api` e `app`, com proxy **ativado** (nuvem laranja).
6. Cloudflare → SSL/TLS → modo **Full (strict)** (exige certificado válido no CyberPanel, não self-signed — já resolvido pelo AutoSSL do passo 3).
7. Criar banco MySQL + usuário dedicado via phpMyAdmin (não usar root; permissões só no banco do projeto).
8. Instalar Node.js LTS + PM2 no VPS via SSH, se ainda não houver.
9. No CyberPanel, configurar o vHost de `app.SEUDOMINIO` como **proxy reverso** para `localhost:3000` (OpenLiteSpeed → External App, ou via Rewrite Rules apontando pro processo Node).

A partir daqui (deploy do código em si) fico responsável quando o projeto estiver pronto para subir — deixo os comandos documentados abaixo para quando chegarmos lá.

### Deploy do Laravel (referência para quando formos publicar)
```bash
git clone <repo-backend> api && cd api
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan config:cache && php artisan route:cache
```
`.env` relevante:
```
APP_URL=https://api.SEUDOMINIO
SANCTUM_STATEFUL_DOMAINS=app.SEUDOMINIO
SESSION_DOMAIN=.SEUDOMINIO
DB_CONNECTION=mysql
QUEUE_CONNECTION=database
CACHE_STORE=database
```
Cron (via CyberPanel → Cron Jobs, ou crontab direto):
```
* * * * * php /caminho/api/artisan schedule:run >> /dev/null 2>&1
```

### Deploy do Next.js (referência)
```bash
git clone <repo-frontend> app && cd app
npm ci
npm run build
pm2 start npm --name app-frontend -- start
pm2 save && pm2 startup
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
| 4 — Polimento MVP | Export pdf/xlsx (hoje só csv); CRUD admin de `achievements`/`levels`; testes E2E; revisão de conteúdo real (as ~120 questões — trabalho pedagógico, não só código); deploy produção no CyberPanel | Pendente |

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

## 9. Perguntas em aberto (não bloqueiam o início, mas precisam de resposta até o Sprint 2)

- Nome de domínio real (para substituir `SEUDOMINIO` nos exemplos acima).
- Chave da OpenAI API (para as features de IA assistiva do professor — seção 25 do brief).
- Login simplificado do aluno (brief seção 37 cita isso, mas não define o mecanismo — usuário/senha curta gerado pela escola? Login via código de turma?).
- Existe sistema escolar (SED, ou similar) para integração futura (seção 36), ou cadastro será 100% manual no MVP?
