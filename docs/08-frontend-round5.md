# Rodada 5 — Avatar, Coleções e Sequência de Dias (streak)

> Backend pronto e testado (38 testes de feature passando): avatar do aluno (personagem ilustrado + acessório, nunca foto real), sistema de colecionáveis (brief seção 18) e sequência de dias jogando. Contrato atualizado em `docs/03-api-contract.md` — leia a seção "Avatar e Coleções" antes de implementar. Este comando é só a parte visual, o backend já responde tudo.

## Comando pronto para o GPT

```
Backend novo pronto — 3 endpoints novos e um campo a mais em dois que já existem.
Leia a seção "Avatar e Coleções" de docs/03-api-contract.md antes de começar, tem
os shapes exatos de resposta.

DECISÃO DE PRODUTO IMPORTANTE: avatar do aluno NUNCA é foto real enviada por ele
— é um personagem ilustrado escolhido entre 6 opções fixas (bússola, mapa,
binóculo, luneta, montanha, mochila), mais um acessório opcional desbloqueado
por coleção. Não construa nenhum upload de imagem pra avatar de aluno.

1. NAVEGAÇÃO: adicione um item "Coleção" no menu do aluno em components/shell.tsx
   (studentLinks), do lado de "Conquistas". Ícone sugerido: Package ou Gem do
   lucide-react.

2. NOVA PÁGINA /collections (app/collections/page.tsx): mesmo padrão visual de
   app/achievements/page.tsx (grid de cards, GET /api/collections), mas:
   - Agrupe por `category` (o contrato lista as 11 categorias possíveis) com um
     título de seção por grupo, ex.: "Brasões", "Biomas", "Acessórios de Avatar".
   - Card bloqueado = mesma linguagem visual que já existe em AchievementCard
     (silhueta pontilhada). Desbloqueado = ícone (lucide-react, o campo `icon`
     do item já é um nome de ícone) ou `image_url` se vier preenchido, com a cor
     variando por `rarity` (common/rare/epic — sugestão: cinza/azul/dourado).

3. EDITOR DE AVATAR: crie um componente reutilizável (ex:
   components/avatar-editor.tsx) que:
   - Busca GET /api/avatar (avatar_base atual, accessory equipado,
     available_bases com code/label/color, unlocked_accessories).
   - Mostra os 6 `available_bases` como botões selecionáveis — renderize cada
     `code` como o ícone lucide-react de mesmo nome (Compass, Map, Binoculars,
     Telescope, Mountain, Backpack), na cor vinda do próprio item.
   - Mostra os `unlocked_accessories` como opções extras de acessório (+ opção
     "nenhum"). Acessórios NÃO desbloqueados não aparecem como opção (o aluno só
     vê o que já tem — não mostre bloqueado aqui, isso já está na tela de Coleção).
   - Ao confirmar, chama PUT /api/avatar { avatar_base, equipped_accessory_id }.
   - Use esse componente dentro de um modal/drawer acessível a partir do
     passaporte (um botão tipo "Editar avatar" perto do nome do aluno).

4. PASSAPORTE (app/passport/page.tsx): a resposta de GET /api/passport agora
   traz `streak_days` (número) e `avatar: { base, accessory }`. Mostre:
   - O avatar atual (ícone da base + ícone do acessório sobreposto/ao lado, se
     tiver) com o botão "Editar avatar" que abre o componente do item 3.
   - Um indicador de sequência de dias (ex.: ícone de chama + "4 dias seguidos"),
     na mesma área dos outros números do passaporte (XP, nível).

5. DASHBOARD DO ALUNO (app/dashboard/page.tsx, dentro de StudentDashboard): o
   brief pede um indicador de "sequência de participação" no dashboard — pegue
   `streak_days` de useMe() (já vem em data.student.streak_days) e mostre um
   badge pequeno perto da barra de XP, mesmo estilo do resto da tela.

6. TELA DE CONCLUSÃO (components/celebration-screen.tsx): a resposta de
   POST /api/attempts/{id}/complete agora também traz `collectibles_unlocked`
   (mesmo shape de `achievements_unlocked`: id, name, category, icon, image_url,
   rarity). Reaproveite a mesma animação em cascata que já existe pras
   conquistas (`.achievement-reveal`) pra revelar os colecionáveis novos também,
   numa seção separada tipo "Novos itens da coleção".

Não precisa mexer em mais nada — o resto do fluxo de jogo já está pronto e não
muda.
```
