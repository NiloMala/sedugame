# Rodada 7 — Mapa visual em qualquer etapa com localização

> Achado testando as campanhas novas: o mapa interativo (MapLibre, aquele que o aluno clica pra responder) só aparece em questões do tipo `map_location`. Em qualquer outro tipo de questão, mesmo quando a etapa tem uma localização anexada (`stage.location`), a tela mostra só um painel decorativo com o nome do lugar — nunca um mapa de verdade. Como as 2 campanhas de demonstração novas não usam nenhuma questão `map_location`, elas nunca mostram mapa nenhum. Objetivo: reaproveitar o mapa (em modo só-visual, sem clique) sempre que a etapa tiver coordenadas, independente do tipo de questão.

## Comando pronto para o GPT

```
Achado testando: em app/play/[missionId]/page.tsx, o mapa interativo
(MissionMap) só renderiza quando q.type === 'map_location'. Qualquer outra
questão, mesmo com stage.location preenchido, cai no painel decorativo
genérico (gradiente + ícone de bússola + nome do lugar). Quero o mapa
aparecendo (só como visual, sem interação de clique) sempre que a etapa
tiver localização, não só nas questões do tipo mapa.

1. components/map/mission-map.tsx: adicione uma prop `interactive` (default
   true) no MissionMap. Quando `interactive=false`:
   - NÃO registre o listener de clique (`map.on('click', ...)`) — hoje ele
     roda mesmo sem `onPick`, e colocaria um marcador em qualquer lugar que
     o aluno clicasse, o que confundiria (pareceria que dá pra responder
     ali, mas não dá).
   - Em vez disso, ao carregar o mapa, coloque um marcador FIXO em `center`
     (a coordenada da etapa) — pra mostrar visualmente onde é o lugar,
     sem esperar clique nenhum.
   - Pode também desabilitar o NavigationControl e o drag/zoom se achar
     que fica mais limpo como elemento só-ilustrativo (sua escolha), mas
     não é obrigatório.

2. app/play/[missionId]/page.tsx, no bloco que decide o que mostrar no
   painel esquerdo (perto da linha 166, onde já existe a checagem
   `q.type === 'map_location' ? <MissionMap .../> : hasMedia ? <img.../> :
   <div>painel decorativo</div>`):
   Mude a ordem de prioridade pra:
   - `map_location` → MissionMap interativo (como já é, sem mudar nada aqui).
   - senão, se `hasMedia` → imagem da etapa (como já é).
   - senão, se `stage?.location` existir → MissionMap com
     `interactive={false}` e `center={mapCenter}` (a variável já existe no
     arquivo, é só reusar).
   - senão → o painel decorativo genérico (como já é, agora só aparece
     quando a etapa realmente não tem nem mídia nem localização).

3. NÃO MEXA em mais nada desse arquivo — só nesse bloco condicional. Fluxo
   de resposta, pontuação, pistas etc. continuam iguais.

Roda `npm run build` no final e confirma que passou.
```
