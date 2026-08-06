# Rodada 4 — tirar fotografia da tela de login, ir pra linguagem de jogo (inspiração GeoGuessr)

> Pedido do cliente: a landing/login está com fotografias reais (`public/images/caraguatatuba-hero.png`, `mapa-expedicao.png`) com overlay de gradiente — visual de site institucional/turismo. Trocar por uma linguagem visual de jogo, inspirada no GeoGuessr (geoguessr.com/pt): fundo escuro/azul profundo, globo ou mapa estilizado (ilustração/vetor, não foto), pins de mapa flutuantes, formas geométricas com glow, tipografia bem forte, CTA de "jogar" em destaque — mais parecido com a tela de abertura de um jogo do que com um site institucional.

## Comando pronto para o GPT

```
Preciso redesenhar a tela de login (app/login/page.tsx) — hoje ela usa duas
fotografias reais como fundo (public/images/caraguatatuba-hero.png e
mapa-expedicao.png) com overlay de gradiente, e ficou com cara de site
institucional/turismo. Quero trocar pra uma linguagem visual de JOGO, inspirada
no GeoGuessr (https://www.geoguessr.com/pt) — dá uma olhada na estética deles:
fundo escuro/azul profundo, ilustração de globo ou mapa estilizado (vetor, não
foto), pins de localização flutuantes, formas geométricas com brilho/glow,
tipografia bem forte e um call-to-action de "jogar" em destaque. É uma sensação
de "abrir um jogo", não de "visitar um site institucional".

O que fazer:

1. REMOVER as duas fotografias (public/images/caraguatatuba-hero.png,
   mapa-expedicao.png) do login — pode deletar os arquivos ou só parar de
   referenciá-los, como preferir. Nenhuma foto realista na tela.

2. Substituir por elementos ilustrados/vetoriais construídos em CSS/SVG (vocês
   já usam lucide-react pros ícones — dá pra compor cenas simples combinando
   Compass, MapPin, Globe/Map, formas geométricas, gradientes e glow, sem
   precisar de nenhum arquivo de imagem novo). Sugestões de elementos:
   - Um globo ou mapa-múndi estilizado como peça central (contorno/silhueta
     de continentes em baixo contraste sobre o fundo escuro, tipo o que o
     GeoGuessr faz — não precisa ser geograficamente preciso, é decorativo).
   - Pins de localização (MapPin) espalhados/flutuando com leve animação de
     flutuação (respeitando prefers-reduced-motion), como se marcassem
     lugares no mapa.
   - Formas geométricas com blur/glow nas cores da marca (vocês já usam isso
     em outras telas — o círculo blur do dashboard, os stamps do passaporte).
   - Grid de coordenadas/latitude-longitude sutil no fundo (algo como o
     .login-map-grid que já existe em app/globals.css, pode reaproveitar ou
     evoluir).

3. Manter tudo que já funciona: o formulário de login (campo "E-mail ou RA",
   senha, botão entrar, modal de recuperação de senha só pra staff) não muda
   nada de funcionalidade — só o cenário visual ao redor dele.

4. Reforçar a sensação de jogo no texto/CTA: título e botão principal com tom
   de "começar a expedição/jogar" (vocês já têm algo nessa linha — só garantir
   que o visual novo reforce isso, não convide a "conhecer a empresa").

5. Se sobrar algum uso de <img> apontando pra arquivo de foto em outras telas
   além do login, pode deixar — o pedido aqui é só sobre a tela de login.

Mantenha responsivo, contraste acessível (o texto sobre fundo escuro precisa
passar em WCAG AA) e respeitando prefers-reduced-motion nas animações novas.
```
