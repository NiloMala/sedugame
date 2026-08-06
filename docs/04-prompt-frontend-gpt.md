# Comando para o GPT construir o Frontend

> Como usar: copie tudo a partir de **"INÍCIO DO PROMPT"** até o fim do arquivo e cole numa conversa nova do ChatGPT (de preferência com Canvas/Projeto, pra ele gerar múltiplos arquivos). Cole também o conteúdo de [03-api-contract.md](./03-api-contract.md) logo em seguida, como segunda mensagem — é o contrato que o backend vai implementar exatamente como está.
>
> Se o GPT gerar algo que diverge do contrato de API (nome de endpoint, formato de resposta), **não deixe ele inventar** — volte aqui comigo pra alinharmos os dois lados antes de mudar código.

---

## INÍCIO DO PROMPT

Você vai construir o **frontend** de uma plataforma web educacional gamificada chamada "**Expedição do Saber**", usada por uma Secretaria Municipal de Educação. O backend (Laravel/API REST) está sendo construído em paralelo por outro time — você só precisa consumir a API descrita no contrato que vou colar na próxima mensagem. Não invente endpoints nem mude o formato de resposta; se algo não estiver coberto, pergunte antes de assumir.

### Contexto do produto

A plataforma trabalha Matemática, Língua Portuguesa, Geografia e História através de mapas, imagens, localizações, missões e desafios — inspirada na exploração geográfica do GeoGuessr, mas com foco pedagógico. Alunos exploram "campanhas" (temas), completam "missões" dentro delas, respondem questões (múltipla escolha, marcar local no mapa, ordenar fatos, associar itens, resposta curta), ganham pontos e experiência, sobem de nível, desbloqueiam conquistas e mantêm um "passaporte virtual" (perfil gamificado). Professores atribuem campanhas a turmas e acompanham desempenho. Administradores da escola/secretaria têm painéis de indicadores.

### Stack obrigatória

- **Next.js 14+** (App Router), **TypeScript** estrito
- **Tailwind CSS** para estilo
- **MapLibre GL JS** + tiles OpenStreetMap para os mapas — **nunca** Google Maps (requisito do cliente, evitar lock-in/custo)
- **TanStack Query (React Query)** para todo data-fetching/cache do lado do cliente
- **Zustand** para estado global de UI (não para cache de servidor — isso é o React Query)
- Estruturar desde já pensando em **PWA** (manifest, ícones, service worker básico), mesmo que a ativação completa seja fase futura

### Autenticação (importante — não é Bearer token)

O backend usa **Laravel Sanctum em modo SPA**, autenticação por **cookie httpOnly**, não por token em `localStorage`/`Authorization` header. Fluxo obrigatório:

1. Antes do login, chamar `GET {API_URL}/sanctum/csrf-cookie` (sem body) — isso seta um cookie `XSRF-TOKEN`.
2. `POST {API_URL}/api/login` com `{ email, password }`.
3. Todas as chamadas subsequentes precisam ir com `credentials: 'include'` (fetch) ou `withCredentials: true` (axios), e o header `X-XSRF-TOKEN` lido do cookie em requisições não-GET.
4. `GET {API_URL}/api/me` retorna o usuário logado e o campo `role` (`student`, `teacher`, `coordinator`, `director`, `school_admin`, `department_admin`, `super_admin`) — é esse campo que define navegação e rotas protegidas.

Implemente isso como um client HTTP único (`lib/api.ts` ou similar) com um wrapper do React Query, não espalhe fetch cru pelos componentes.

### Variáveis de ambiente esperadas

```
NEXT_PUBLIC_API_URL=https://api.SEUDOMINIO
```
(o valor real de `SEUDOMINIO` será definido depois — use variável de ambiente, nunca hardcode)

### Design / identidade visual

- Moderno, educacional, colorido, intuitivo, acessível, gamificado **sem parecer infantil demais** — o público inclui adolescentes de 11-13 anos (6º/7º ano), não crianças pequenas.
- Elementos visuais recorrentes: mapas, bússola, passaporte, medalhas, pins, trilhas, cartões.
- Cores devem ser **configuráveis** (tema via CSS variables / Tailwind config, não hardcoded nos componentes) — a Secretaria pode querer trocar a paleta.
- Acessibilidade obrigatória: alto contraste disponível, ajuste de fonte, navegação por teclado, textos alternativos em imagens, sem depender só de cor para transmitir informação (ex.: certo/errado não pode ser só verde/vermelho — usar ícone + cor).
- Responsivo: painéis (professor/admin) priorizam desktop; telas de jogo do aluno priorizam mobile/tablet.

### Páginas a construir (MVP)

```
/login
/dashboard                    (varia por role — aluno vê um dashboard, professor outro)
/campaigns
/campaigns/[id]
/missions/[id]
/play/[missionId]             (tela de jogo — ver layout abaixo)
/passport
/achievements
/activities                   (atividades atribuídas ao aluno pela turma)
/teacher/classes
/teacher/activities
/teacher/reports
/admin                        (dashboard geral)
/admin/schools
/admin/users
/admin/campaigns
/admin/missions
/admin/questions
/admin/skills
/admin/reports
/admin/settings
```

Não construa: modo ao vivo, competição entre equipes, criador de campanhas do professor, ranking público — são Fase 2, fora do escopo agora.

### Layout da tela `/play/[missionId]` (a mais importante do produto)

- **Área principal**: mapa (MapLibre) ou imagem/panorama em destaque, conforme o tipo de conteúdo da etapa atual.
- **Área lateral**: narrativa da missão, enunciado da questão, alternativas (ou input de mapa/texto conforme o tipo), botão "Responder", botão "Pedir pista" (mostra penalidade de pontos antes de confirmar), pontuação acumulada, cronômetro se a questão tiver `time_limit_seconds`, barra de progresso da missão (etapa atual / total).
- **Área inferior** (mobile): mapa minimizado, botão "Confirmar localização" quando a questão for do tipo `map_location`, acesso rápido às opções de acessibilidade.
- Ao responder, mostrar feedback imediato (correto/incorreto, distância em metros se for `map_location`, explicação pedagógica) antes de avançar pra próxima etapa.
- Ao completar a missão, mostrar tela de resumo: pontos, XP ganho, se subiu de nível, conquistas desbloqueadas.

### Componentes de gamificação a reutilizar em várias telas

- Barra/anel de XP com nível atual e progresso até o próximo
- Card de conquista (bloqueada = silhueta/cinza; desbloqueada = colorida com data)
- Passaporte: card estilo "carteirinha" com avatar, nome, escola, turma, nível, campanhas concluídas, medalhas
- Card de campanha (capa, título, disciplina com cor, dificuldade, progresso, botão iniciar/continuar)

### O que a API já entrega pronto (não recalcule no frontend)

Pontuação, distância em metros, XP, level-up e conquistas desbloqueadas **vêm prontos nas respostas da API** (ver `POST /api/attempts/{id}/answers` e `POST /api/attempts/{id}/complete` no contrato). O frontend só exibe — não reimplemente fórmula de pontuação nem cálculo de distância no cliente.

### Entregável esperado

Projeto Next.js completo e rodável (`npm run build` sem erros), organizado por feature (não por tipo de arquivo solto), com:
- Client HTTP + hooks do React Query por recurso (`useCampaigns`, `useMission`, `useAttempt`, etc.)
- Componentes de UI reutilizáveis isolados (`components/gamification/`, `components/map/`, etc.)
- Tipagem TypeScript dos payloads da API (pode gerar manualmente a partir do contrato, ou perguntar se eu tenho um OpenAPI/schema gerado)
- README explicando como rodar localmente (`npm install`, `.env.local`, `npm run dev`) e apontando pra uma API mockada ou pra `NEXT_PUBLIC_API_URL` real

Se qualquer requisito acima estiver ambíguo ou faltando informação pra decidir, **pergunte antes de assumir** — isso vai ser integrado com um backend real construído em paralelo, então divergência de contrato quebra a integração.

## FIM DO PROMPT
