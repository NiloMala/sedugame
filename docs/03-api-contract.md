# Contrato de API — Expedição do Saber

> Fonte da verdade para integração entre o backend Laravel (eu) e o frontend Next.js (GPT, ver [04-prompt-frontend-gpt.md](./04-prompt-frontend-gpt.md)). Qualquer mudança aqui precisa ser replicada nos dois lados. Cobre o escopo do MVP (seção 50 do [brief](./00-spec-original.md)); endpoints de Fase 2/3 (modo ao vivo, criador de campanhas, IA) ficam esboçados no fim, sem compromisso de estabilidade ainda.
>
> **Revisão 2026-08-06**: corrigido `POST /api/login` de `{ email, password }` para `{ login, password }` (login aceita e-mail OU RA — essa era uma inconsistência minha, não do time de frontend) e adicionados endpoints que faltavam (`/api/activities`, `/api/admin/settings`, reset de senha de aluno, payloads de `ordering`/`matching`/`fill_blank`/`multiple_choice`). Ver [05-frontend-round2.md](./05-frontend-round2.md) para o que precisa ser ajustado no frontend por causa disso.

## Convenções gerais

- Base URL: `https://api.SEUDOMINIO`
- Todas as rotas de negócio sob o prefixo `/api`
- Formato: JSON (`Content-Type: application/json`, `Accept: application/json`)
- Auth: Laravel Sanctum, **modo SPA por cookie** (não Bearer token) — ver seção "Autenticação" abaixo
- Toda resposta de sucesso vem envelopada:
  ```json
  { "data": { }, "meta": { } }
  ```
  `meta` só aparece quando há paginação.
- Listagens paginadas seguem o padrão do `paginate()` do Laravel:
  ```json
  {
    "data": [ ],
    "meta": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 20,
      "total": 93
    },
    "links": {
      "first": "...",
      "last": "...",
      "prev": null,
      "next": "..."
    }
  }
  ```
- Erros seguem o padrão de validação do Laravel (`422`) e um formato próprio para os demais:
  ```json
  // 422 Unprocessable Entity (validação)
  { "message": "The given data was invalid.", "errors": { "email": ["The email field is required."] } }

  // 401 / 403 / 404 / 500
  { "message": "Descrição curta do erro" }
  ```
- Datas em ISO 8601 UTC (`2026-08-05T14:30:00Z`); o frontend converte para exibição local.

## Autenticação (Sanctum SPA — cookie, não token)

Como `app.SEUDOMINIO` e `api.SEUDOMINIO` compartilham o domínio raiz, usamos autenticação por **cookie httpOnly**, mais segura que token em `localStorage`. Fluxo:

```
1. GET  https://api.SEUDOMINIO/sanctum/csrf-cookie   (sem body; seta cookie XSRF-TOKEN)
2. POST https://api.SEUDOMINIO/api/login              (credentials; body: { email, password })
3. Cookie de sessão é setado automaticamente pelo navegador
4. Toda requisição subsequente deve ir com { credentials: 'include' } (fetch) / withCredentials: true (axios)
   e o header X-XSRF-TOKEN (lido do cookie XSRF-TOKEN) em métodos não-GET
```

Endpoints:
```
GET  /sanctum/csrf-cookie
POST /api/login              { login, password }                  -> 204, seta cookie
POST /api/logout                                                   -> 204
POST /api/forgot-password    { email }                             -> 204   (só staff — ver nota abaixo)
POST /api/reset-password     { token, email, password, password_confirmation } -> 204
GET  /api/me                                                       -> usuário autenticado (ver shape abaixo)
```

**Atenção — `login` não é sempre um e-mail.** O campo `login` aceita:
- **e-mail** para staff (professor, coordenador, diretor, admin);
- **RA** (`registration_number`, só dígitos) para aluno — login simplificado (seção 37 do brief).

O backend decide qual é qual: se o valor bater no formato de e-mail, busca em `users.email`; senão, busca em `students.registration_number`. **O campo do formulário de login não pode ser `<input type="email">`** (isso bloqueia o navegador de aceitar um RA puramente numérico) — use `type="text"` com um placeholder tipo "E-mail ou RA".

`POST /api/forgot-password` e o fluxo de recuperação por e-mail só existem pra staff. Aluno não recupera senha sozinho: se esquecer, o admin da escola reseta pelo endpoint abaixo, que devolve a senha padrão da rede (o aluno troca depois de logar):
```
POST /api/admin/students/{id}/reset-password   (role: school_admin | department_admin)
  -> 200 { "data": { "message": "Senha do aluno resetada para o padrão da rede." } }
```

`GET /api/me` — resposta:
```json
{
  "data": {
    "id": 42,
    "name": "Maria Silva",
    "email": "maria@escola.edu.br",
    "role": "student",
    "avatar_url": "https://api.SEUDOMINIO/storage/avatars/42.png",
    "school": { "id": 3, "name": "EMEF João de Barro" },
    "student": {
      "id": 17,
      "class": { "id": 5, "name": "6ºA" },
      "level": { "id": 2, "name": "Aprendiz de Viajante", "order": 2 },
      "experience": 1340,
      "experience_to_next_level": 660,
      "streak_days": 4,
      "avatar_base": "compass"
    }
  }
}
```
`avatar_url` (nível raiz, todos os perfis) é foto de perfil real — só faz sentido pra staff. `student.avatar_base` é o personagem ilustrado do aluno (nunca foto) — ver seção "Avatar e Coleções". Pra montar o avatar completo do aluno (base + acessório equipado), use `GET /api/avatar` ou `GET /api/passport`.
O campo `role` é o slug (`student`, `teacher`, `coordinator`, `director`, `school_admin`, `department_admin`, `super_admin`) e define o que o frontend renderiza (menus, rotas protegidas). O objeto `student` só existe se `role === 'student'`; equivalente futuro para `teacher`.

**Login simplificado do aluno** (seção 37 do brief) ainda não está definido — por ora, mesmo fluxo de email/senha para todos os perfis. Ver pergunta em aberto no [01-arquitetura-e-plano.md](./01-arquitetura-e-plano.md#8-perguntas-em-aberto).

---

## Área do Aluno

### Campanhas
```
GET /api/campaigns
  query: ?subject_id=&grade_id=&difficulty=&status=in_progress|completed|available
```
```json
{
  "data": [
    {
      "id": 1,
      "title": "Conhecendo Caraguatatuba",
      "slug": "conhecendo-caraguatatuba",
      "cover_image_url": "https://.../capa.jpg",
      "primary_subject": { "id": 3, "name": "Geografia", "color": "#2E7D32" },
      "grade": { "id": 1, "name": "6º ano" },
      "difficulty": "easy",
      "missions_count": 10,
      "estimated_minutes": 120,
      "progress": { "percent": 40, "status": "in_progress" }
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 3 }
}
```

```
GET /api/campaigns/{id}
```
Retorna a campanha com a lista de `missions` (id, title, order, status de progresso do aluno, `locked: boolean` baseado em `unlock_rule`).

### Missões
```
GET /api/missions/{id}
```
```json
{
  "data": {
    "id": 4,
    "campaign_id": 1,
    "title": "O Mistério da Serra do Mar",
    "narrative": "Você está próximo ao Parque Estadual da Serra do Mar...",
    "difficulty": "medium",
    "max_score": 1000,
    "stages": [
      {
        "id": 9,
        "order": 1,
        "content": "Observe a paisagem ao seu redor.",
        "location": { "id": 6, "name": "Serra do Mar", "latitude": -23.62, "longitude": -45.41 },
        "media": [ { "id": 30, "type": "image", "file_url": "https://..." } ]
      }
    ]
  }
}
```

### Tentativas (gameplay)
```
POST /api/attempts
  body: { "mission_id": 4, "activity_id": null }
  -> 201 { "data": { "id": 501, "status": "in_progress", "started_at": "..." } }
```
```
GET /api/attempts/{id}/next-question
  -> 200 retorna a próxima questão não respondida da tentativa (statement, options SEM is_correct, hints disponíveis SEM content revelado)
  -> 404 { "message": "no_more_questions" } quando a tentativa não tem mais questões pendentes — é o sinal pro frontend chamar POST /api/attempts/{id}/complete.
     Esse 404 é o ÚNICO status que deve ser interpretado como "missão concluída". Qualquer outro erro (401, 419 sessão expirada, 500, falha de rede)
     é uma falha real e deve mostrar estado de erro, nunca a tela de conclusão — checar o status/message antes de decidir, não só "deu erro".
```
```
POST /api/attempts/{id}/answers
  body (single_choice / true_false):
    { "question_id": 12, "selected_option_id": 45, "time_spent_seconds": 22, "hints_used": 1 }
  body (multiple_choice — mais de uma opção correta):
    { "question_id": 12, "selected_option_ids": [45, 47], "time_spent_seconds": 22, "hints_used": 1 }
  body (map_location):
    { "question_id": 13, "latitude": -23.6201, "longitude": -45.4109, "time_spent_seconds": 40, "hints_used": 0 }
  body (short_answer):
    { "question_id": 14, "answer_text": "Mata Atlântica", "time_spent_seconds": 15, "hints_used": 0 }
  body (fill_blank — uma resposta por lacuna, na ordem das lacunas no statement):
    { "question_id": 15, "answer_text": ["1500", "Cabral"], "time_spent_seconds": 18, "hints_used": 0 }
  body (ordering — ids das question_options na ordem escolhida pelo aluno):
    { "question_id": 16, "ordered_option_ids": [3, 1, 2], "time_spent_seconds": 25, "hints_used": 0 }
  body (matching — pares { option_id do lado A: option_id do lado B correspondente }):
    { "question_id": 17, "matches": [{ "left_option_id": 5, "right_option_id": 9 }], "time_spent_seconds": 30, "hints_used": 0 }

  -> 200 {
    "data": {
      "is_correct": true,
      "score": 850,
      "distance_meters": 320,          // só em map_location
      "explanation": "A Serra do Mar é coberta por Mata Atlântica...",
      "correct_option_id": 45           // revelado só depois de responder
    }
  }
```
```
POST /api/attempts/{id}/complete
  -> 200 {
    "data": {
      "score": 3200,
      "experience_gained": 320,
      "level_up": false,
      "achievements_unlocked": [ { "id": 5, "title": "Explorador de Caraguatatuba", "icon": "..." } ],
      "collectibles_unlocked": [ { "id": 12, "name": "Brasão de Caraguatatuba", "category": "coat_of_arms", "icon": "shield", "image_url": null, "rarity": "common" } ]
    }
  }
```
`collectibles_unlocked` reúne tanto o colecionável de recompensa da própria missão (se tiver) quanto os concedidos por alguma conquista desbloqueada nesse mesmo `complete()` — ex.: completar uma missão sem pistas desbloqueia a conquista "Sem Pistas" **e** o acessório de avatar vinculado a ela, os dois no mesmo array.
```
POST /api/attempts/{id}/hints/{hintId}   -> revela conteúdo da pista e já aplica score_penalty
  -> 200 { "data": { "content": "A região fica na faixa litorânea..." } }
```

### Passaporte, conquistas e atividades
```
GET /api/passport            -> perfil gamificado completo do aluno logado (shape exato abaixo)
GET /api/achievements         -> todas as conquistas + quais o aluno já desbloqueou
GET /api/activities            -> atividades atribuídas às turmas do aluno logado (id, campaign, prazo, status, melhor tentativa)
```

`GET /api/passport` — resposta (chaves fixas; o frontend não precisa mais adivinhar entre nomes alternativos):
```json
{
  "data": {
    "name": "Maria Silva",
    "school": "EMEF João de Barro",
    "class": "6ºA",
    "level": { "id": 2, "name": "Aprendiz de Viajante", "order": 2 },
    "experience": 1340,
    "streak_days": 4,
    "avatar": {
      "base": "compass",
      "accessory": { "id": 12, "name": "Binóculo Dourado", "icon": "binoculars" }
    },
    "completed_campaigns": [
      { "id": 1, "title": "Conhecendo Caraguatatuba", "completed_at": "2026-05-10T14:00:00Z" }
    ],
    "locations_visited": [
      { "id": 6, "name": "Serra do Mar", "latitude": -23.62, "longitude": -45.41 }
    ],
    "achievements": [
      { "id": 5, "title": "Explorador de Caraguatatuba", "icon": "🏔️", "unlocked_at": "2026-05-10T14:00:00Z" }
    ],
    "collectibles_count": 3,
    "performance_by_subject": [
      { "subject": "Geografia", "accuracy_percent": 82 }
    ]
  }
}
```

`avatar.base` é sempre um dos códigos de `GET /api/avatar` (nunca foto real — ver seção "Avatar e Coleções" abaixo).

### Avatar e Coleções (brief seção 18 + extensão de avatar)

**Decisão de produto**: avatar do aluno nunca é foto real enviada — é um personagem ilustrado pré-definido (6 opções fixas) mais um acessório opcional desbloqueado por coleção. Evita todo o problema de moderação/privacidade de imagem de criança/adolescente (LGPD/ECA). O frontend renderiza cada `code` como um ícone estilizado (os códigos batem com nomes de ícone do lucide-react, que o frontend já usa) — não precisa de nenhum arquivo de imagem novo.

```
GET /api/collections   -> catálogo inteiro de colecionáveis + quais o aluno já desbloqueou
GET /api/avatar        -> avatar atual + opções disponíveis
PUT /api/avatar         { avatar_base, equipped_accessory_id? }
```

`GET /api/collections` — resposta:
```json
{
  "data": [
    {
      "id": 12, "name": "Brasão de Caraguatatuba", "description": "...",
      "category": "coat_of_arms", "icon": "shield", "image_url": null, "rarity": "common",
      "unlocked": true, "unlocked_at": "2026-05-10T14:00:00Z"
    },
    {
      "id": 15, "name": "Selo da Mata Atlântica", "description": "...",
      "category": "biome", "icon": "trees", "image_url": null, "rarity": "rare",
      "unlocked": false, "unlocked_at": null
    }
  ]
}
```
`category` é um de: `monument, animal, biome, map, historical_figure, coat_of_arms, flag, postcard, artifact, culture, avatar_accessory`. Só itens `avatar_accessory` podem ser equipados no avatar (`equipped_accessory_id` do `PUT /api/avatar`); os demais são só de exibição na tela de coleções.

`GET /api/avatar` — resposta:
```json
{
  "data": {
    "avatar_base": "compass",
    "equipped_accessory": { "id": 18, "name": "Binóculo Dourado", "icon": "binoculars" },
    "available_bases": [
      { "code": "compass", "label": "Bússola", "color": "#0EA5E9" },
      { "code": "map", "label": "Mapa", "color": "#16A34A" },
      { "code": "binoculars", "label": "Binóculo", "color": "#D97706" },
      { "code": "telescope", "label": "Luneta", "color": "#7C3AED" },
      { "code": "mountain", "label": "Montanha", "color": "#059669" },
      { "code": "backpack", "label": "Mochila", "color": "#DC2626" }
    ],
    "unlocked_accessories": [
      { "id": 18, "name": "Binóculo Dourado", "icon": "binoculars", "image_url": null }
    ]
  }
}
```
`PUT /api/avatar` com `avatar_base` fora da lista de `available_bases` → `422`. Com `equipped_accessory_id` que o aluno não tem em `unlocked_accessories` → `422` (`"Você ainda não desbloqueou esse acessório."`).

Como os itens são desbloqueados: cada **missão** pode ter um colecionável de recompensa (concedido uma vez, na primeira conclusão) e cada **conquista** também pode ter um colecionável vinculado — os dois aparecem em `collectibles_unlocked` na resposta de `POST /api/attempts/{id}/complete` (seção acima). Não existe compra com dinheiro real, conforme brief seção 18.

---

## Área do Professor
```
GET  /api/teacher/classes                       -> turmas do professor logado
GET  /api/teacher/classes/{id}/students          -> alunos da turma + resumo de desempenho
GET  /api/teacher/activities                     -> atividades criadas pelo professor
POST /api/teacher/activities                     { campaign_id, class_ids[], starts_at, ends_at, attempt_limit, ranking_enabled }
GET  /api/teacher/activities/{id}/results        -> desempenho agregado (médias, acertos/erros, tempo médio, habilidades críticas)
GET  /api/teacher/reports/class/{classId}
GET  /api/teacher/reports/class/{classId}/export?format=pdf|xlsx|csv
```

Os 3 formatos usam exatamente as mesmas linhas (`ReportController::classReportRows()`), então nunca divergem entre si. `pdf` via `barryvdh/laravel-dompdf` (`resources/views/reports/class-export.blade.php`, A4 paisagem); `xlsx` via `maatwebsite/excel` (`app/Exports/ClassReportExport.php`). Formato fora de `csv|pdf|xlsx` devolve `422`.

Todas as rotas de professor exigem que a turma pertença a ele (`teacher_classes`) — tentar acessar turma/atividade de outro professor devolve `403`.

---

## Painéis da Escola / Secretaria (`/api/reports/*`)

Autorização: `coordinator`/`director`/`school_admin` só a própria escola (`request.user.school_id`); `department_admin`/`super_admin` qualquer escola + rede toda. Ver tabela na seção "Autorização por perfil" abaixo.

```
GET /api/reports/network                 -> indicadores de toda a rede (só department_admin/super_admin)
GET /api/reports/school/{id}             -> indicadores da escola (seção 28 do brief)
GET /api/reports/class/{id}
GET /api/reports/student/{id}
```

Shape comum (`school`/`network` incluem também `total_students`, `total_teachers`, `classes_count`/`schools_count`, `activities_applied`/`campaigns_used`, `participation_rate`, `completion_rate`):
```json
{
  "data": {
    "attempts_count": 42,
    "average_score": 780,
    "accuracy_percent": 68,
    "average_time_seconds": 35,
    "critical_skills": [
      { "skill": "Reconhecer biomas...", "accuracy_percent": 41, "attempts": 12 }
    ]
  }
}
```

---

## Área Administrativa (Secretaria / Escola)

Todos os recursos abaixo seguem o **mesmo padrão REST**: `GET` (lista paginada + filtros), `GET /{id}`, `POST`, `PUT /{id}`, `DELETE /{id}` (soft delete). Detalhado uma vez aqui; não repetido por recurso.

**Escopo por perfil** (já implementado no backend): `school_admin` só lista/edita registros da própria escola (`schools`, `classes`, `users` — tentativa de acessar outra escola devolve `403`); cadastros de rede (`grades`, `subjects`, `skills`, `school-years`) são só-leitura pra `school_admin` e escrita é exclusiva de `department_admin`/`super_admin`. `school_admin` também não pode criar usuário com role `department_admin`/`super_admin` (bloqueio de escalada de privilégio).

```
/api/admin/schools
/api/admin/users               (body de criação inclui role; se role=student, exige registration_number + class_id — cria o Student junto)
/api/admin/classes
POST   /api/admin/classes/{id}/teachers            { teacher_id, subject_id } — vincula professor à turma nessa disciplina (idempotente)
DELETE /api/admin/classes/{id}/teachers/{teacherId} ?subject_id= opcional — sem ele, remove todas as disciplinas desse professor nessa turma
/api/admin/grades
/api/admin/school-years
/api/admin/subjects
/api/admin/skills
/api/admin/campaigns          + POST /api/admin/campaigns/{id}/publish
/api/admin/missions
/api/admin/missions/{id}/stages         (nested — etapa não existe fora do contexto de uma missão; body aceita media_ids[] pra anexar mídia à etapa)
/api/admin/questions           + POST /api/admin/questions/{id}/review   { status: school|network|official|archived, notes }
                                  (options[], hints[], location{} podem vir aninhados no mesmo body de create/update — ver shape abaixo)
/api/admin/locations
/api/admin/media
/api/admin/achievements        (cadastro de rede — escrita só department_admin/super_admin, como subjects/skills; rule_type é fechado, ver lista abaixo)
/api/admin/levels              (idem; sem paginação — GET retorna a lista inteira ordenada por `order`)
/api/admin/collectible-items   (idem; GET aceita ?category=&status=)
GET  /api/admin/settings         -> configurações da rede: nome da plataforma, cores do tema, regras de pontuação/faixas de distância
PUT  /api/admin/settings
POST /api/admin/students/{id}/reset-password   (role: school_admin | department_admin — ver seção Autenticação)
```

`rule_type` aceito em `achievements` (validado contra essa lista fechada — os valores espelham `ProgressionService::achievementSatisfied()`; um valor fora daqui salvaria mas nunca desbloquearia):
`first_mission_completed`, `missions_completed_count` (`rule_value.count`), `correct_answers_count` (`rule_value.count`), `mission_without_hints`, `streak_days` (`rule_value.days`), `campaign_completed` (`rule_value.campaign_id`).

Exclusão de `achievements`/`levels`/`collectible-items` é soft delete (2026-08-07 — antes só `collectible_items` tinha `deleted_at`; `achievements`/`levels` ganharam na mesma migration que trouxe o CRUD admin, pelo mesmo motivo do brief seção 48: histórico de conquista/colecionável já desbloqueado por aluno não pode ser apagado em cascata por um hard delete no catálogo).

Shape de `POST/PUT /api/admin/questions` (options/hints/location aninhados):
```json
{
  "subject_id": 3, "skill_id": 8, "grade_id": 1, "type": "single_choice",
  "statement": "...", "explanation": "...", "difficulty": "easy",
  "options": [
    { "text": "Litoral Norte", "is_correct": true, "order": 0 },
    { "text": "Litoral Sul", "is_correct": false, "order": 1 }
  ],
  "hints": [ { "type": "text", "content": "...", "score_penalty": 150, "order": 0 } ],
  "location": { "latitude": -23.62, "longitude": -45.41, "accepted_radius_meters": 3000 }
}
```
Para `type: "ordering"`, `options[].order` é a posição correta. Para `type: "matching"`, cada option leva `side: "left"|"right"` e o `order` compartilhado entre um item de cada lado define o par correto. Reenviar `options`/`hints`/`location` num `PUT` **substitui** o conjunto anterior inteiro (não faz merge parcial).

Exemplo de shape de erro de permissão (professor tentando acessar rota `/admin/*`):
```json
// 403
{ "message": "Você não tem permissão para acessar este recurso." }
```

### Relatórios da Secretaria
```
GET /api/reports/network                 -> indicadores gerais (todas as escolas)
GET /api/reports/school/{id}
GET /api/reports/class/{id}
GET /api/reports/student/{id}
  query comum: ?school_id=&grade_id=&subject_id=&period_start=&period_end=&campaign_id=&skill_id=
```

---

## Autorização por perfil (resumo para o frontend)

| Rota | student | teacher | coordinator | director | school_admin | department_admin |
|---|---|---|---|---|---|---|
| `/api/campaigns`, `/api/attempts/*`, `/api/passport` | ✅ | — | — | — | — | — |
| `/api/teacher/*` | — | ✅ | — | — | — | — |
| `/api/reports/school/{id}` | — | — | ✅ (própria) | ✅ (própria) | ✅ (própria) | ✅ (todas) |
| `/api/reports/network` | — | — | — | — | — | ✅ |
| `/api/admin/*` | — | — | — | — | ✅ (própria escola) | ✅ (rede) |

Implementado via Laravel Policies + Gate, checado tanto no backend (autoridade real) quanto no frontend (esconder UI que o usuário não pode usar — nunca a única camada de proteção).

---

## Fase 2/3 — esboço (não implementar ainda, só para não colidir nomes de rota depois)

```
POST /api/live-sessions                     (professor cria sala, gera access_code)
POST /api/live-sessions/{code}/join         (aluno entra)
WS   wss://api.SEUDOMINIO/app/live-session/{id}   (Laravel Reverb, Fase 2)

POST /api/campaigns/{id}/submit-for-review  (professor submete campanha própria)
POST /api/campaigns/{id}/review             (coordenador aprova/reprova)

POST /api/ai/generate-question
POST /api/ai/generate-mission
POST /api/ai/generate-explanation
POST /api/ai/evaluate-short-answer
```
