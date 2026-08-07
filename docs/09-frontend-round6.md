# Rodada 6 — Polimento visual (avatar, cards, coleção)

> Feedback do cliente depois de testar como aluno em produção: fluxo funciona, mas o visual do avatar e dos cards está genérico/fraco. Pediu inspiração no GeoGuessr (grid de cards ricos, badges de raridade, avatar em destaque) — mas **sem** copiar a arquitetura deles (personagem 3D, upload de foto): aqui é só polimento visual dentro do que já existe. Nenhum endpoint muda nesta rodada — é 100% CSS/composição de componentes.

## Comando pronto para o GPT

```
Feedback do cliente depois de testar em produção: o app funciona bem, mas o
visual do avatar e dos cards de coleção/campanha está fraco — pediu pra
melhorar bastante, com o GeoGuessr como referência de polimento (grid de
cards ricos, badge de raridade tipo RARE/EPIC, avatar em destaque).

RESTRIÇÃO QUE NÃO MUDA (já é decisão de produto, LGPD/ECA): avatar continua
sendo só os 6 personagens ilustrados fixos + acessório, nunca foto real,
nunca um personagem 3D tipo GeoGuessr. A melhoria é só de POLIMENTO VISUAL
em cima do que já existe — nenhum endpoint novo, nenhum dado novo.

IMPORTANTE — antes de mexer, leia o diff que você vai gerar com atenção:
da última vez, ao adicionar uma feature pequena em dashboard/page.tsx e
celebration-screen.tsx, o arquivo inteiro foi reescrito e isso apagou
TeacherDashboard/ManagementDashboard (só sobrou tela genérica pra quem não
é aluno) e a checagem de prefers-reduced-motion (acessibilidade). Os dois
foram corrigidos, mas não pode acontecer de novo: mexa só no que precisa
mudar, não reescreva o arquivo do zero. Rode `npm run build` no final e
confirme que passa.

1. AVATAR (components/avatar-editor.tsx — AvatarVisual e o modal do editor):
   - Aumente o destaque do avatar: hoje é um ícone pequeno num círculo. Dê
     mais presença visual — círculo maior, gradiente de fundo (pode variar
     por `avatar_base`, cada base já tem uma `color` vinda da API), sombra/
     glow sutil, talvez uma "plataforma" ou moldura decorativa embaixo
     (nada de modelo 3D — só CSS/SVG).
   - No modal de edição, cada opção de base (os 6 botões) merece cartão
     maior, com o gradiente da cor própria em vez de borda fina cinza.
   - Acessório equipado: hoje é um badge pequeno no canto. Pode ganhar mais
     destaque (ex.: brilho, anel colorido por raridade do item).

2. CARDS DE COLECIONÁVEL/CONQUISTA (app/collections/page.tsx,
   app/achievements/page.tsx): adicione um badge de raridade visível no
   canto do card (texto curto: "COMUM" / "RARO" / "ÉPICO"), estilo pill,
   cor batendo com a borda que já existe (cinza/azul/dourado). Cards
   desbloqueados podem ganhar leve efeito de hover (elevação/brilho).
   Cards bloqueados continuam com a silhueta pontilhada atual — não mude
   essa parte, já está boa.

3. CARDS DE CAMPANHA (components/gamification.tsx — CampaignCard, usado no
   dashboard e em /campaigns): hoje é um bloco de gradiente verde com um
   ícone de bússola centralizado — melhore a composição (ex.: padrão
   decorativo de fundo variando por `primary_subject`, badge de dificuldade
   mais visível, barra de progresso mais robusta). Não precisa de imagem de
   capa real (não temos asset pra isso) — o objetivo é o card parecer menos
   genérico usando só gradiente/padrão/tipografia.

4. NÃO MEXA: fluxo de gameplay (/play/[missionId]), lógica de dados/hooks,
   e nenhum endpoint da API. É só composição visual dos componentes acima.

Ao terminar, roda `npm run build` e confirma que passou antes de avisar.
```
