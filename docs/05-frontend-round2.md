# Revisão do frontend (rodada 1) e comando de correção para o GPT

> Revisão feita em 2026-08-06 sobre o código já entregue pelo GPT na raiz do projeto (`app/`, `components/`, `lib/`). Veredito geral: **estrutura e qualidade muito boas** — cobriu todas as páginas do MVP, seguiu a convenção de auth por cookie, não inventou endpoints fora do contrato (inclusive sinalizou no README os 2 pontos que faltavam no contrato, o que estava certo). Os problemas encontrados são pontuais e concentrados em alguns arquivos.

## O que está bom (não mexer)

- Todas as páginas da lista do prompt original existem, nenhuma de Fase 2 foi construída por engano (sem `/ranking`, sem modo ao vivo).
- `lib/api.ts`: client HTTP único, `credentials: 'include'`, `X-XSRF-TOKEN` em mutações, fluxo `GET /sanctum/csrf-cookie` antes do login — exatamente como pedido.
- `components/shell.tsx`: guarda de rota por role, navegação lateral, logout, acessibilidade — bem estruturado.
- `app/play/[missionId]/page.tsx`: tela de jogo (mapa/imagem + narrativa + questão + pista + cronômetro + tela de conclusão) implementada de forma sólida para os tipos objetiva/mapa/resposta curta.
- PWA básico (`manifest.json`, `sw.js`), alto contraste e ajuste de fonte via `AccessibilityControls`.

## Bugs críticos (quebram uso real)

1. **Login de aluno por RA não funciona.** `app/login/page.tsx` usa `<input type="email" required>` e `lib/api.ts#signIn` envia `{ email, password }`. Um RA (ex: `123456`) é bloqueado pela validação HTML5 do navegador antes mesmo de chegar na API — e mesmo que chegasse, o backend espera o campo `login`, não `email`. **Causa raiz era minha**: o `docs/03-api-contract.md` documentava `{ email, password }` e só corrigi depois que a regra de login por RA foi definida. Já corrigi o contrato (ver revisão 2026-08-06 no topo do arquivo).
2. **Coordenador e Diretor entram em loop de redirecionamento.** `lib/permissions.ts`: `homeForRole()` manda esses dois papéis para `/admin`, mas `canAccessPath()` só libera `/admin` para `school_admin | department_admin | super_admin`. Resultado: se o usuário clicar em "Abrir gestão", é redirecionado de volta pra `/admin` infinitamente. Além disso, não existe um painel dedicado pra esses papéis (brief seções 28-29 — "Painel da Escola" e "Painel da Secretaria" já têm dado pronto em `GET /api/reports/school/{id}` e `GET /api/reports/network`, só falta a tela).

## Gaps vs. especificação (não quebram, mas faltam)

3. **CRUDs administrativos são só leitura.** `app/admin/campaigns`, `/missions`, `/questions`, `/schools`, `/users`, `/skills` usam `ResourcePage` (tabela paginada, sem criar/editar/excluir/publicar). O brief (seção 57) pede editores de verdade. `teacher/activities` é o único admin-like que tem formulário de criação — pode servir de modelo de padrão pros outros.
4. **Tipos de questão incompletos na UI.** `lib/types.ts` e a tela de jogo só tratam `multiple_choice` (na prática single-choice), `map_location` e `short_answer`. Faltam `single_choice` (com semântica própria), `true_false`, `ordering`, `matching` e `fill_blank` — os payloads de cada um já estão no contrato atualizado.
5. **Mapa usa estilo de demonstração.** `components/map/mission-map.tsx` aponta pro `https://demotiles.maplibre.org/style.json`, que é um estilo genérico de exemplo, sem detalhe suficiente pra geografia real (bairros de Caraguatatuba, biomas). Precisa de um estilo OSM de verdade.

## Melhorias menores (nice-to-have)

6. Formulário "Nova atividade" (`app/teacher/activities/page.tsx`) pede ID numérico de campanha e turmas digitado à mão — trocar por `<select>` alimentado por `GET /api/campaigns` e `GET /api/teacher/classes`.
7. Botão "Esqueci minha senha" no login não tem `onClick` — ligar ao `POST /api/forgot-password` (só pra staff — aluno não recupera sozinho, ver contrato atualizado).
8. Falta toggle de "modo sem animação" (`prefers-reduced-motion`) nos controles de acessibilidade — brief seção 41 pede.

---

## Comando pronto para o GPT (colar como próxima mensagem na mesma conversa onde ele gerou o código)

```
Preciso que você AJUSTE o projeto Next.js que já construiu (não recomece do zero) — encontramos alguns bugs e gaps numa revisão. O docs/03-api-contract.md foi atualizado (colo abaixo as partes que mudaram); alinhe o código a ele.

MUDANÇA NO CONTRATO — leia com atenção:
POST /api/login agora espera { login, password } — não { email, password }. O campo `login` aceita e-mail (staff) OU RA/matrícula (aluno, só dígitos). Isso significa:
1. Em app/login/page.tsx: troque o <input type="email"> por <input type="text"> com label "E-mail ou RA" e placeholder tipo "voce@escola.edu.br ou seu RA". Não valide formato de e-mail nesse campo.
2. Em lib/api.ts (signIn) e lib/hooks.ts (useLogin): troque o parâmetro/body de `email` para `login`.
3. POST /api/forgot-password e a recuperação por e-mail só existem para staff. Aluno esquecido de senha é resolvido pelo admin via POST /api/admin/students/{id}/reset-password (a tela de admin de usuários pode ter um botão "Resetar senha" que chama esse endpoint quando o usuário selecionado for aluno).

CORRIGIR — bug de navegação:
Em lib/permissions.ts, coordinator e director caem em loop de redirecionamento: homeForRole() manda pra /admin mas canAccessPath() bloqueia /admin pra esses papéis. Crie uma rota /reports (ou uma seção dentro de /dashboard) acessível para coordinator, director e as três roles admin, consumindo GET /api/reports/school/{id} (coordinator/director/school_admin — a própria escola do usuário) e GET /api/reports/network (department_admin/super_admin — rede toda). Ajuste homeForRole() para mandar coordinator/director pra essa nova rota, não pra /admin.

ADICIONAR — tipos de questão faltando:
O contrato define 8 tipos de questão, mas só 3 são tratados hoje (multiple_choice tratado como single-choice, map_location, short_answer). Adicione suporte em lib/types.ts e app/play/[missionId]/page.tsx para:
- single_choice e true_false: mesma UI de alternativas que já existe, só ajustar o type union.
- multiple_choice real (múltiplas corretas): permitir selecionar mais de uma opção, body { selected_option_ids: number[] }.
- fill_blank: um <input> por lacuna, body { answer_text: string[] } na ordem das lacunas.
- ordering: lista arrastável (ou botões pra cima/baixo) com as question_options, body { ordered_option_ids: number[] } na ordem final escolhida.
- matching: pareamento simples de duas colunas, body { matches: [{ left_option_id, right_option_id }] }.
Os exemplos completos de cada payload estão no contrato atualizado, seção "Tentativas (gameplay)".

ADICIONAR — endpoints que agora existem no contrato:
- GET /api/activities: use em app/activities/page.tsx (lista de atividades atribuídas ao aluno — hoje essa página provavelmente está vazia/mockada por falta desse endpoint antes).
- GET e PUT /api/admin/settings: use em app/admin/settings/page.tsx (nome da plataforma, cores do tema, regras de pontuação).

MELHORAR — quando der tempo, nessa ordem de prioridade:
1. Trocar o estilo do mapa em components/map/mission-map.tsx: hoje usa https://demotiles.maplibre.org/style.json (estilo de demonstração, sem detalhe geográfico real). Troque por um estilo OSM real — pode usar tiles raster do OpenStreetMap (https://tile.openstreetmap.org/{z}/{x}/{y}.png, sem precisar de chave de API, respeitando a política de uso deles) num style spec simples do MapLibre, OU perguntar se eu quero contratar um provedor (MapTiler/Stadia) com chave de API antes de decidir.
2. Transformar os CRUDs admin de somente-leitura (app/admin/campaigns, missions, questions, schools, users, skills) em telas com criar/editar/excluir de verdade, seguindo o padrão de formulário que vocês já usaram em app/teacher/activities/page.tsx.
3. No formulário de nova atividade (app/teacher/activities/page.tsx), trocar os campos de texto de campaign_id e class_ids por <select> populados via GET /api/campaigns e GET /api/teacher/classes.
4. Ligar o botão "Esqueci minha senha" do login a POST /api/forgot-password.
5. Adicionar toggle de "reduzir animações" (respeitando prefers-reduced-motion) nos controles de acessibilidade.

Não precisa mexer no que já está funcionando (auth por cookie, shell/navegação, tela de jogo pros tipos já tratados, PWA, acessibilidade existente) — só nos itens acima.
```
