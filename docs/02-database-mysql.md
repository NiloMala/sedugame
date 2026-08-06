# Banco de Dados — MySQL/MariaDB

> Versão final do schema descrito na seção 46 do [brief original](./00-spec-original.md), adaptada para MySQL 8/MariaDB (sem PostGIS). Serve de referência direta para as migrations do Laravel. Tabelas do MVP em detalhe; tabelas de Fase 2/3 listadas ao final apenas como lembrete de modelagem futura.

## Convenções gerais

- PK: `id BIGINT UNSIGNED AUTO_INCREMENT`
- FKs: `BIGINT UNSIGNED`, `ON DELETE CASCADE` nas tabelas pivot/dependentes, `ON DELETE RESTRICT` em entidades cadastrais (não apagar escola com alunos vinculados, por exemplo)
- Toda entidade "de conteúdo" (`users`, `campaigns`, `missions`, `questions`) tem `deleted_at` (soft delete) — exclusões administrativas são lógicas, conforme regra de negócio do brief (seção 48)
- Timestamps padrão Laravel (`created_at`, `updated_at`) em todas as tabelas, omitidos abaixo por repetição
- Charset `utf8mb4` / collation `utf8mb4_unicode_ci` em todo o banco (acentuação, emojis em conquistas/ícones)

## Cálculo de distância geográfica (substitui PostGIS)

Sem PostGIS, coordenadas ficam em colunas simples `DECIMAL(10,7)` (`latitude`, `longitude`) e a distância é calculada com a fórmula de Haversine — exatamente a alternativa que o próprio brief já previa (seção 14).

**Em PHP**, usado no serviço de correção de `attempt_answers` do tipo `map_location` (compara só 2 pontos, não precisa de índice espacial):

```php
function haversineDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $earthRadius = 6371000; // metros

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) ** 2
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

    return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
}
```

**Em SQL**, se algum relatório precisar ordenar por proximidade (ex.: "locations mais próximas de X"):

```sql
SELECT id, name,
  (6371000 * ACOS(
      COS(RADIANS(:lat)) * COS(RADIANS(latitude)) * COS(RADIANS(longitude) - RADIANS(:lng))
      + SIN(RADIANS(:lat)) * SIN(RADIANS(latitude))
  )) AS distance_meters
FROM locations
ORDER BY distance_meters ASC
LIMIT 10;
```

Não vamos usar coluna `POINT`/`SPATIAL INDEX` do MySQL no MVP — não há ganho real comparando dois pontos isolados, e adiciona complexidade de migration sem necessidade agora. Fica como otimização futura se o "banco de questões" crescer muito e precisarmos de buscas geoespaciais em volume.

---

## Tabelas — MVP (Fase 1)

### users
```
id                 BIGINT UNSIGNED PK
name               VARCHAR(150)
email              VARCHAR(150)    UNIQUE
email_verified_at  TIMESTAMP       NULL
password           VARCHAR(255)
role_id            BIGINT UNSIGNED FK -> roles.id
school_id          BIGINT UNSIGNED NULL FK -> schools.id
status             ENUM('active','inactive','pending')  DEFAULT 'active'
avatar_url         VARCHAR(255)    NULL
last_login_at      TIMESTAMP       NULL
remember_token     VARCHAR(100)    NULL
deleted_at         TIMESTAMP       NULL
```

### roles
```
id           BIGINT UNSIGNED PK
name         VARCHAR(100)
slug         VARCHAR(100) UNIQUE   -- student, teacher, coordinator, director, school_admin, department_admin, super_admin
description  VARCHAR(255) NULL
```

### permissions
```
id           BIGINT UNSIGNED PK
name         VARCHAR(150)
slug         VARCHAR(150) UNIQUE
description  VARCHAR(255) NULL
```

### role_permissions (pivot)
```
role_id        BIGINT UNSIGNED FK -> roles.id        (PK composta)
permission_id  BIGINT UNSIGNED FK -> permissions.id   (PK composta)
```

### schools
```
id         BIGINT UNSIGNED PK
name       VARCHAR(200)
code       VARCHAR(50)  UNIQUE
address    VARCHAR(255) NULL
district   VARCHAR(120) NULL
city       VARCHAR(120)
state      CHAR(2)
latitude   DECIMAL(10,7) NULL
longitude  DECIMAL(10,7) NULL
status     ENUM('active','inactive') DEFAULT 'active'
```

### school_years
```
id         BIGINT UNSIGNED PK
year       SMALLINT UNSIGNED
starts_at  DATE
ends_at    DATE
status     ENUM('active','closed') DEFAULT 'active'
```

### grades (ano escolar)
```
id                BIGINT UNSIGNED PK
name              VARCHAR(60)   -- "6º ano"
code              VARCHAR(20)
education_level   ENUM('EF1','EF2','EM','EJA')
order             TINYINT UNSIGNED
```

### classes (turmas)
```
id              BIGINT UNSIGNED PK
school_id       BIGINT UNSIGNED FK -> schools.id
name            VARCHAR(60)   -- "6ºA"
grade_id        BIGINT UNSIGNED FK -> grades.id
school_year_id  BIGINT UNSIGNED FK -> school_years.id
shift           ENUM('morning','afternoon','night','full')
status          ENUM('active','inactive') DEFAULT 'active'
```

### students
```
id                    BIGINT UNSIGNED PK
user_id               BIGINT UNSIGNED FK -> users.id  UNIQUE
registration_number   VARCHAR(50) NULL
school_id             BIGINT UNSIGNED FK -> schools.id
class_id              BIGINT UNSIGNED FK -> classes.id
birth_date            DATE NULL
status                ENUM('active','inactive') DEFAULT 'active'
```

### teachers
```
id                    BIGINT UNSIGNED PK
user_id               BIGINT UNSIGNED FK -> users.id  UNIQUE
school_id             BIGINT UNSIGNED FK -> schools.id
registration_number   VARCHAR(50) NULL
status                ENUM('active','inactive') DEFAULT 'active'
```

### teacher_classes (pivot)
```
teacher_id  BIGINT UNSIGNED FK -> teachers.id  (PK composta)
class_id    BIGINT UNSIGNED FK -> classes.id   (PK composta)
subject_id  BIGINT UNSIGNED FK -> subjects.id  (PK composta)
```

### subjects
```
id      BIGINT UNSIGNED PK
name    VARCHAR(80)          -- Matemática, Português, Geografia, História
slug    VARCHAR(80) UNIQUE
icon    VARCHAR(60)  NULL
color   CHAR(7)      NULL    -- hex
status  ENUM('active','inactive') DEFAULT 'active'
```

### skills (habilidades)
```
id            BIGINT UNSIGNED PK
subject_id    BIGINT UNSIGNED FK -> subjects.id
grade_id      BIGINT UNSIGNED FK -> grades.id
code          VARCHAR(30)  NULL   -- código BNCC, se aplicável
title         VARCHAR(200)
description   TEXT NULL
status        ENUM('active','inactive') DEFAULT 'active'
```

### campaigns
```
id                   BIGINT UNSIGNED PK
title                VARCHAR(150)
slug                 VARCHAR(160) UNIQUE
description          TEXT NULL
cover_image_url      VARCHAR(255) NULL
primary_subject_id   BIGINT UNSIGNED FK -> subjects.id
grade_id             BIGINT UNSIGNED FK -> grades.id
difficulty           ENUM('easy','medium','hard')
status               ENUM('draft','in_review','approved','published','archived') DEFAULT 'draft'
visibility           ENUM('private','school','network') DEFAULT 'private'
author_id            BIGINT UNSIGNED FK -> users.id
published_at         TIMESTAMP NULL
estimated_minutes    SMALLINT UNSIGNED NULL
max_score            INT UNSIGNED DEFAULT 0
deleted_at           TIMESTAMP NULL
```

### campaign_subjects / campaign_skills (pivots)
```
campaign_id  BIGINT UNSIGNED FK -> campaigns.id
subject_id   BIGINT UNSIGNED FK -> subjects.id     -- campaign_subjects
skill_id     BIGINT UNSIGNED FK -> skills.id        -- campaign_skills
```

### missions
```
id                  BIGINT UNSIGNED PK
campaign_id         BIGINT UNSIGNED FK -> campaigns.id
title               VARCHAR(150)
slug                VARCHAR(160)
description         TEXT NULL
narrative           TEXT NULL
objective           VARCHAR(255) NULL
order               SMALLINT UNSIGNED DEFAULT 0
difficulty          ENUM('easy','medium','hard')
estimated_minutes   SMALLINT UNSIGNED NULL
max_score           INT UNSIGNED DEFAULT 0
status              ENUM('draft','published','archived') DEFAULT 'draft'
unlock_rule         JSON NULL   -- ex: {"requires_mission_id": 3, "min_score": 500}
```

### mission_stages (etapas)
```
id            BIGINT UNSIGNED PK
mission_id    BIGINT UNSIGNED FK -> missions.id
title         VARCHAR(150) NULL
description   TEXT NULL
content       TEXT NULL       -- texto de apoio / narrativa da etapa
order         SMALLINT UNSIGNED DEFAULT 0
location_id   BIGINT UNSIGNED NULL FK -> locations.id
```

### locations
```
id                  BIGINT UNSIGNED PK
name                VARCHAR(150)
description         TEXT NULL
latitude            DECIMAL(10,7)
longitude           DECIMAL(10,7)
city                VARCHAR(120) NULL
state               CHAR(2) NULL
country             VARCHAR(80)  DEFAULT 'Brasil'
biome               VARCHAR(80)  NULL
historical_period   VARCHAR(80)  NULL
source_type         VARCHAR(50)  NULL   -- own, licensed, public_domain
source_url          VARCHAR(255) NULL
license             VARCHAR(120) NULL
attribution         VARCHAR(255) NULL
```

### media
```
id             BIGINT UNSIGNED PK
type           ENUM('image','panorama_360','video','audio')
title          VARCHAR(150) NULL
file_url       VARCHAR(255)
thumbnail_url  VARCHAR(255) NULL
source         VARCHAR(120) NULL
license        VARCHAR(120) NULL
author         VARCHAR(120) NULL
attribution    VARCHAR(255) NULL
latitude       DECIMAL(10,7) NULL
longitude      DECIMAL(10,7) NULL
```

### mission_media (pivot)
```
mission_id  BIGINT UNSIGNED FK -> missions.id  (PK composta)
media_id    BIGINT UNSIGNED FK -> media.id     (PK composta)
order       SMALLINT UNSIGNED DEFAULT 0
```

### questions
```
id                   BIGINT UNSIGNED PK
mission_stage_id     BIGINT UNSIGNED NULL FK -> mission_stages.id   -- NULL = questão solta no banco, ainda não vinculada
subject_id           BIGINT UNSIGNED FK -> subjects.id
skill_id             BIGINT UNSIGNED FK -> skills.id
grade_id             BIGINT UNSIGNED FK -> grades.id
type                 ENUM('single_choice','multiple_choice','true_false','map_location','ordering','matching','fill_blank','short_answer')
statement            TEXT
explanation          TEXT NULL
difficulty           ENUM('easy','medium','hard')
max_score            INT UNSIGNED DEFAULT 1000
time_limit_seconds   SMALLINT UNSIGNED NULL
status               ENUM('private','school','network','official','archived') DEFAULT 'private'
author_id            BIGINT UNSIGNED FK -> users.id
deleted_at           TIMESTAMP NULL
```

### question_options
```
id           BIGINT UNSIGNED PK
question_id  BIGINT UNSIGNED FK -> questions.id
text         VARCHAR(255) NULL
image_url    VARCHAR(255) NULL
is_correct   BOOLEAN DEFAULT FALSE
order        SMALLINT UNSIGNED DEFAULT 0
```

### question_locations (gabarito de questões `map_location`)
```
id                       BIGINT UNSIGNED PK
question_id              BIGINT UNSIGNED FK -> questions.id  UNIQUE
latitude                 DECIMAL(10,7)
longitude                DECIMAL(10,7)
accepted_radius_meters   INT UNSIGNED DEFAULT 0   -- 0 = usa faixas de pontuação por distância (seção 14 do brief)
```

### hints
```
id             BIGINT UNSIGNED PK
question_id    BIGINT UNSIGNED FK -> questions.id
type           VARCHAR(50)   -- eliminate_option, approximate_region, first_letter, ...
content        TEXT
score_penalty  INT UNSIGNED DEFAULT 100
order          SMALLINT UNSIGNED DEFAULT 0
```

### activities (atribuição de campanha para turma)
```
id               BIGINT UNSIGNED PK
teacher_id       BIGINT UNSIGNED FK -> teachers.id
campaign_id      BIGINT UNSIGNED FK -> campaigns.id
title            VARCHAR(150) NULL
description      VARCHAR(255) NULL
starts_at        TIMESTAMP NULL
ends_at          TIMESTAMP NULL
attempt_limit    TINYINT UNSIGNED NULL   -- NULL = ilimitado
ranking_enabled  BOOLEAN DEFAULT FALSE
status           ENUM('draft','active','closed') DEFAULT 'draft'
```

### activity_classes (pivot)
```
activity_id  BIGINT UNSIGNED FK -> activities.id  (PK composta)
class_id     BIGINT UNSIGNED FK -> classes.id      (PK composta)
```

### attempts (tentativas)
```
id                  BIGINT UNSIGNED PK
student_id          BIGINT UNSIGNED FK -> students.id
activity_id         BIGINT UNSIGNED NULL FK -> activities.id   -- NULL = modo individual/livre
campaign_id         BIGINT UNSIGNED FK -> campaigns.id
mission_id          BIGINT UNSIGNED FK -> missions.id
started_at          TIMESTAMP
completed_at        TIMESTAMP NULL
score               INT UNSIGNED DEFAULT 0
experience          INT UNSIGNED DEFAULT 0
status              ENUM('in_progress','completed','abandoned') DEFAULT 'in_progress'
time_spent_seconds  INT UNSIGNED DEFAULT 0
```

### attempt_answers
```
id                   BIGINT UNSIGNED PK
attempt_id           BIGINT UNSIGNED FK -> attempts.id
question_id          BIGINT UNSIGNED FK -> questions.id
answer_text          TEXT NULL
selected_option_id   BIGINT UNSIGNED NULL FK -> question_options.id
latitude             DECIMAL(10,7) NULL   -- resposta do aluno em questões map_location
longitude            DECIMAL(10,7) NULL
distance_meters      DECIMAL(10,2) NULL   -- calculado via Haversine no momento da correção
is_correct           BOOLEAN NULL
score                INT UNSIGNED DEFAULT 0
time_spent_seconds   INT UNSIGNED DEFAULT 0
hints_used           TINYINT UNSIGNED DEFAULT 0
```

### student_progress
```
id                 BIGINT UNSIGNED PK
student_id         BIGINT UNSIGNED FK -> students.id
campaign_id        BIGINT UNSIGNED FK -> campaigns.id
mission_id         BIGINT UNSIGNED NULL FK -> missions.id
progress_percent   TINYINT UNSIGNED DEFAULT 0
best_score         INT UNSIGNED DEFAULT 0
attempts_count     SMALLINT UNSIGNED DEFAULT 0
completed_at       TIMESTAMP NULL
```
Índice único: `(student_id, mission_id)`.

### levels
```
id                   BIGINT UNSIGNED PK
name                 VARCHAR(100)   -- "Explorador Iniciante"
minimum_experience   INT UNSIGNED
maximum_experience   INT UNSIGNED NULL
icon                 VARCHAR(60) NULL
order                TINYINT UNSIGNED
```

### achievements
```
id                  BIGINT UNSIGNED PK
title                VARCHAR(150)
description          VARCHAR(255) NULL
icon                 VARCHAR(60) NULL
rule_type            VARCHAR(60)   -- first_mission_completed, correct_answers_count, missions_without_hints, streak_days, ...
rule_value           JSON NULL     -- ex: {"count": 10}
experience_reward    INT UNSIGNED DEFAULT 0
status               ENUM('active','inactive') DEFAULT 'active'
```

### student_achievements
```
id              BIGINT UNSIGNED PK
student_id      BIGINT UNSIGNED FK -> students.id
achievement_id  BIGINT UNSIGNED FK -> achievements.id
unlocked_at     TIMESTAMP
```
Índice único: `(student_id, achievement_id)`.

### audit_logs
> Implementado desde o Sprint 1 (requisito de segurança, ver [01-arquitetura-e-plano.md](./01-arquitetura-e-plano.md#7-segurança-e-lgpd)), mesmo não estando listado explicitamente no MVP funcional do brief.
```
id            BIGINT UNSIGNED PK
user_id       BIGINT UNSIGNED NULL FK -> users.id
action        VARCHAR(100)     -- login, logout, create, update, delete, publish, approve, reject, permission_change, export
entity_type   VARCHAR(100) NULL
entity_id     BIGINT UNSIGNED NULL
old_values    JSON NULL
new_values    JSON NULL
ip_address    VARCHAR(45) NULL
user_agent    VARCHAR(255) NULL
created_at    TIMESTAMP
```

---

## Tabelas — Fase 2 / Fase 3 (modelar quando chegarmos nesses sprints)

Já estão especificadas na íntegra na seção 46 do brief original, sem mudança de estrutura — só citadas aqui para lembrete de que existem e quando entram:

| Tabela | Entra em |
|---|---|
| `collectible_items`, `student_collectibles` | Fase 2 (Coleções, seção 18) |
| `teams`, `team_members` | Fase 2 (Competição entre equipes, seção 21) |
| `live_sessions`, `live_session_participants` | Fase 2 (Modo professor ao vivo, seção 22) |
| `content_reviews` | Fase 2 (Criador de campanhas por professor + fluxo de aprovação, seção 23) |
| `notifications` | Fase 2 (seção 40) |

Não criar migrations dessas tabelas no MVP — evita schema morto e overengineering (diretriz seção 55 do brief).
