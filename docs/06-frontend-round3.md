# Rodada 3 — corrigir regressão da tela de jogo + deixar visualmente mais forte

> Verificação em 2026-08-06 sobre as correções da rodada 2 (`docs/05-frontend-round2.md`). A maior parte foi corrigida certinho (login por RA, redirect de coordenador/diretor, mapa OSM, tipos de questão). Mas a reescrita de `app/play/[missionId]/page.tsx` pra suportar os novos tipos de questão **descartou toda a funcionalidade e o visual que já existiam** nesse arquivo — é a tela mais importante do produto (é o jogo em si) e hoje ela nem carrega uma questão. Prioridade 1 abaixo é isso; prioridade 2 é o pedido de deixar o jogo mais atraente visualmente pros alunos.

## O que foi verificado como corrigido (não mexer)

- `lib/api.ts` / `lib/hooks.ts`: `signIn`/`useLogin` já usam `{ login, password }`.
- `app/login/page.tsx`: campo texto "E-mail ou RA", recuperação de senha só pra staff, modal funcionando.
- `lib/permissions.ts` + `app/reports/page.tsx`: coordenador/diretor não entram mais em loop, painel novo consumindo `/api/reports/school/{id}` ou `/network`.
- `components/map/mission-map.tsx`: tiles reais do OpenStreetMap.
- `lib/types.ts`: os 8 tipos de questão tipados.

## Prioridade 1 — corrigir a regressão (bloqueia o produto inteiro)

```
app/play/[missionId]/page.tsx foi reescrito pra suportar os novos tipos de questão
(single_choice, multiple_choice, true_false, fill_blank, ordering, matching) e no
processo perdeu tudo que já funcionava nessa tela. Preciso que você RECONSTRUA essa
tela juntando as duas coisas — a versão anterior tinha toda a estrutura certa, só
faltava os tipos de questão novos. Os hooks de lib/hooks.ts continuam todos lá
(useMission, useStartAttempt, useAttempt, useAnswer, useHint, useCompleteAttempt) —
o problema é só que a página parou de usá-los.

Preciso que a tela volte a ter:
1. Fluxo real de tentativa: useStartAttempt() disparado quando a missão carrega,
   guardando o attempt.id num estado (o bug atual: existe `const [attemptId] =
   useState<number>()` SEM setter — o attemptId nunca é definido e a tela fica
   travada em "Carregando questão…" pra sempre. Precisa de
   `const [attemptId, setAttemptId] = useState<number>()` e chamar
   `setAttemptId(attempt.id)` no onSuccess do useStartAttempt).
2. Layout dividido: área principal com o MissionMap (pra map_location) ou a
   imagem da etapa (stage.media), área lateral com narrativa da missão, o
   enunciado, cronômetro (quando a questão tiver time_limit_seconds) e
   progresso (etapa atual / total de stages).
3. Sistema de pista: botão "Pedir pista" com modal de confirmação (a API aplica
   a penalidade de pontos), usando useHint.
4. Tela de conclusão ao terminar a missão (useCompleteAttempt): pontuação, XP
   ganho, "subiu de nível" quando level_up=true, lista de conquistas
   desbloqueadas — com uma comemoração visual de verdade (ver prioridade 2).
5. Handlers de resposta pra TODOS os 8 tipos de questão que vocês já modelaram
   em lib/types.ts — mantenha o que vocês fizeram pra ordering/matching/
   fill_blank/multiple_choice, só reintegre com o fluxo de attempt/hint/timer
   acima em vez da versão simplificada atual.

Não precisa mexer em mais nada fora desse arquivo.
```

## Prioridade 2 — deixar o jogo visualmente mais forte pros alunos

```
O público são alunos de 6º/7º ano (11-13 anos). A estrutura e a UX já estão
boas, mas as telas de jogo (dashboard do aluno, campanhas, tela de missão,
passaporte, conquistas) ainda estão com visual muito "painel administrativo" —
cards planos, barra de progresso reta, sem nenhuma celebração ou textura de
jogo. A tela de login já tem a linguagem visual certa (fotografia real,
gradiente, tipografia forte) — quero essa mesma energia nas telas de jogo.

Prioridades, nessa ordem:

1. TELA DE CONCLUSÃO DE MISSÃO (maior impacto): quando o aluno termina uma
   missão, isso precisa parecer uma conquista de verdade — animação de
   confete ou partículas ao aparecer, contador de pontos/XP que sobe
   visualmente até o valor final (não aparece já pronto), destaque grande e
   comemorativo quando sobe de nível, e as conquistas desbloqueadas
   aparecendo uma a uma com uma pequena animação de "revelar", não uma lista
   estática.

2. PASSAPORTE (app/passport/page.tsx): hoje é uma tela de dados. Faça parecer
   um passaporte de verdade — visual de "carteirinha de viajante" com
   textura/cor de página de passaporte, "carimbos" (stamps) pras campanhas
   concluídas em vez de uma lista simples, com um efeito visual quando um
   carimbo novo é adicionado.

3. CONQUISTAS (components/gamification.tsx#AchievementCard): a versão
   bloqueada hoje é só grayscale + ícone de cadeado, o que é funcional mas
   sem graça. Dê a sensação de "silhueta misteriosa esperando ser
   desbloqueada" — pode ser um contorno pontilhado ou um brilho/pulso sutil
   nas que estão prestes a desbloquear. Nas desbloqueadas, adicione hover com
   leve elevação/brilho.

4. BARRA DE XP (components/gamification.tsx#XpBar) e barra de progresso dos
   CampaignCard: hoje são retângulos retos. Anime o preenchimento (transição
   suave quando o valor muda, não só CSS width estático) e considere um
   indicador circular/anel pro XP em vez de barra reta — combina mais com a
   estética de "bússola/expedição" do brief.

5. TELA DE MISSÃO (depois de corrigida na prioridade 1): dê uma sensação de
   progresso físico — algo como uma trilha/caminho com marcadores pra cada
   etapa (stage) da missão, não só um texto "Etapa 2 de 5". Feedback de
   resposta correta/incorreta com uma micro-animação (não só troca de cor).

6. CARDS DE CAMPANHA: hoje o fundo é só um gradiente teal→cyan quando não há
   cover_image_url. Considere ilustrações/padrões diferentes por disciplina
   (usando a cor da disciplina que já vem da API) em vez do mesmo gradiente
   pra tudo, e uma leve interação de hover (elevação, zoom sutil na imagem).

7. IMAGENS: as duas imagens novas em public/images/ (caraguatatuba-hero.png,
   mapa-expedicao.png) estão em ~2-3MB cada, isso é pesado pra celular/rede
   de escola. Troque a tag <img> por next/image (otimização automática,
   lazy loading, tamanhos responsivos) nessas duas ocorrências, e se possível
   comprima os arquivos originais também.

8. Respeite `prefers-reduced-motion` em todas as animações novas acima
   (o brief pede "modo sem animação" na seção de acessibilidade) — animações
   decorativas devem ser puladas/reduzidas quando o usuário tiver essa
   preferência do sistema ativada.

Pode usar bibliotecas leves se ajudar (ex: canvas-confetti pro efeito de
confete, framer-motion se preferir animações mais ricas em vez de só CSS
transitions) — só mantenha o bundle consciente, isso vai rodar em celular de
escola pública.
```
