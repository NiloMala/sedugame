# GeoGame Educacional — Plataforma Gamificada Interdisciplinar

> Brief original enviado pelo cliente em 2026-08-05. Mantido aqui verbatim como fonte da verdade pedagógica/funcional. Decisões técnicas que divergem deste documento (banco de dados, separação front/back, infraestrutura) estão registradas e justificadas em [01-arquitetura-e-plano.md](./01-arquitetura-e-plano.md).

## 1. Visão Geral

Desenvolver uma plataforma educacional gamificada para a Secretaria Municipal de Educação, inspirada na dinâmica de exploração geográfica do GeoGuessr, porém com foco pedagógico e interdisciplinar.

A aplicação deverá utilizar mapas, imagens, localizações, textos, vídeos, desafios, perguntas e missões para trabalhar conteúdos de:

* Matemática;
* Língua Portuguesa;
* Geografia;
* História.

O sistema deverá permitir que alunos explorem locais reais, resolvam desafios contextualizados, acumulem pontos, evoluam de nível, desbloqueiem conquistas e acompanhem seu progresso.

Professores deverão conseguir aplicar atividades, acompanhar resultados, visualizar dificuldades por habilidade e criar campanhas educacionais.

A Secretaria deverá possuir uma visão consolidada por escola, turma, disciplina, ano escolar e período.

---

# 2. Objetivo do Projeto

Criar uma plataforma de aprendizagem baseada em exploração, mapas e missões, capaz de:

* aumentar o engajamento dos alunos;
* trabalhar conteúdos curriculares de forma contextualizada;
* utilizar locais reais como ponto de partida para o aprendizado;
* valorizar a história, geografia e cultura do município;
* fornecer indicadores pedagógicos;
* apoiar professores na criação de atividades;
* identificar dificuldades de aprendizagem;
* estimular raciocínio lógico, interpretação e pensamento crítico;
* permitir expansão futura para outras redes municipais de ensino.

---

# 3. Nome Provisório

## Expedição do Saber

Outras opções possíveis:

* GeoSaber;
* EduExpedição;
* Mapa do Conhecimento;
* Jornada do Saber;
* Missão Brasil;
* Trilhas do Conhecimento;
* GeoEduca;
* ExploraEdu.

O nome deverá ser configurável no painel administrativo.

---

# 4. Público-Alvo

## Alunos

Inicialmente:

* Ensino Fundamental II;
* 6º e 7º anos.

Expansão futura:

* Ensino Fundamental I;
* 8º e 9º anos;
* Educação de Jovens e Adultos;
* projetos de reforço e recuperação.

## Usuários do sistema

* aluno;
* professor;
* coordenador pedagógico;
* diretor;
* administrador escolar;
* administrador da Secretaria;
* equipe pedagógica;
* equipe técnica.

---

# 5. Perfis de Acesso

## 5.1 Aluno

O aluno poderá:

* acessar campanhas;
* iniciar missões;
* responder desafios;
* visualizar mapas;
* observar imagens e panoramas;
* utilizar pistas;
* acumular pontos;
* ganhar experiência;
* subir de nível;
* desbloquear conquistas;
* acompanhar seu desempenho;
* visualizar seu passaporte virtual;
* participar de desafios da turma;
* participar de competições entre equipes;
* rever questões respondidas;
* ler explicações das respostas;
* consultar conteúdos já desbloqueados.

## 5.2 Professor

O professor poderá:

* visualizar suas turmas;
* criar atividades;
* selecionar campanhas existentes;
* atribuir missões para turmas;
* definir prazo de realização;
* acompanhar o desempenho dos alunos;
* visualizar habilidades com maior dificuldade;
* acompanhar tentativas;
* consultar tempo médio de resposta;
* visualizar acertos e erros;
* criar missões próprias;
* criar questões;
* utilizar questões do banco;
* revisar sugestões geradas por IA;
* iniciar partidas ao vivo;
* organizar desafios entre equipes;
* exportar relatórios;
* acompanhar evolução individual e coletiva.

## 5.3 Coordenador Pedagógico

O coordenador poderá:

* acompanhar todas as turmas da escola;
* visualizar indicadores por ano;
* comparar turmas;
* identificar habilidades críticas;
* acompanhar utilização da plataforma;
* visualizar campanhas aplicadas;
* gerar relatórios pedagógicos;
* acompanhar professores;
* sugerir campanhas e atividades;
* revisar conteúdos da escola.

## 5.4 Diretor

O diretor poderá:

* visualizar indicadores gerais da unidade;
* acompanhar adesão de professores;
* acompanhar participação dos alunos;
* consultar relatórios por turma;
* verificar campanhas utilizadas;
* acompanhar resultados por disciplina;
* exportar relatórios da unidade.

## 5.5 Administrador da Escola

O administrador escolar poderá:

* gerenciar turmas;
* vincular professores;
* corrigir cadastros;
* acompanhar acessos;
* consultar relatórios administrativos;
* auxiliar na organização das atividades.

## 5.6 Administrador da Secretaria

O administrador da Secretaria terá acesso total e poderá:

* gerenciar escolas;
* gerenciar usuários;
* gerenciar anos letivos;
* gerenciar disciplinas;
* gerenciar habilidades;
* gerenciar campanhas;
* gerenciar missões;
* gerenciar localizações;
* gerenciar imagens;
* gerenciar conteúdos;
* gerenciar permissões;
* moderar conteúdos criados;
* acompanhar todas as escolas;
* visualizar indicadores gerais;
* exportar relatórios;
* configurar regras de pontuação;
* configurar níveis;
* configurar conquistas;
* configurar identidade visual;
* configurar integrações;
* acompanhar logs e auditoria.

---

# 6. Estrutura Pedagógica

O sistema deverá organizar o conteúdo da seguinte forma:

```text
Disciplina
└── Ano escolar
    └── Unidade temática
        └── Habilidade
            └── Campanha
                └── Missão
                    └── Etapa
                        └── Questão
```

Cada questão deverá ser vinculada a:

* disciplina;
* ano escolar;
* habilidade;
* nível de dificuldade;
* assunto;
* campanha;
* missão;
* localização, quando aplicável.

---

# 7. Disciplinas

## 7.1 Geografia

A disciplina de Geografia será o núcleo principal de exploração por mapas.

Tipos de atividade:

* localizar cidades;
* localizar estados;
* localizar países;
* identificar continentes;
* reconhecer biomas;
* analisar relevo;
* interpretar mapas;
* identificar clima;
* observar vegetação;
* identificar áreas urbanas e rurais;
* analisar placas e paisagens;
* identificar problemas ambientais;
* calcular distância entre locais;
* reconhecer fronteiras;
* interpretar coordenadas;
* reconhecer rios e oceanos;
* identificar regiões brasileiras;
* analisar densidade populacional;
* comparar indicadores territoriais.

Exemplo:

```text
Observe a paisagem apresentada.

Qual bioma brasileiro está representado?

A) Caatinga
B) Mata Atlântica
C) Cerrado
D) Pantanal
```

## 7.2 Matemática

As questões deverão ser contextualizadas dentro da missão.

Tipos de atividade:

* distância;
* velocidade;
* tempo;
* escalas;
* porcentagem;
* frações;
* unidades de medida;
* área;
* perímetro;
* gráficos;
* tabelas;
* proporções;
* orçamento;
* temperatura;
* coordenadas;
* raciocínio lógico;
* probabilidade.

Exemplo:

```text
Uma trilha possui 3,6 km.

A turma já percorreu 2/3 do caminho.

Quantos metros ainda faltam?

A) 800 metros
B) 1.000 metros
C) 1.200 metros
D) 1.800 metros
```

## 7.3 Língua Portuguesa

Tipos de atividade:

* interpretação de texto;
* interpretação de placas;
* gêneros textuais;
* ortografia;
* pontuação;
* concordância;
* sinônimos;
* antônimos;
* coesão;
* coerência;
* identificação de informações;
* organização de fatos;
* leitura de notícias;
* leitura de lendas;
* produção de frases;
* respostas curtas;
* compreensão contextual.

Exemplo:

```text
Leia o trecho do diário do viajante.

"Chegamos à cidade durante a manhã. O céu estava encoberto, mas as ruas estavam movimentadas."

Qual informação está explícita no texto?

A) A viagem ocorreu à noite.
B) A cidade estava deserta.
C) O viajante chegou pela manhã.
D) Estava chovendo intensamente.
```

## 7.4 História

Tipos de atividade:

* linha do tempo;
* ordem cronológica;
* personagens históricos;
* acontecimentos;
* patrimônio histórico;
* história local;
* povos originários;
* Brasil Colonial;
* independência;
* escravidão;
* abolição;
* imigração;
* industrialização;
* movimentos sociais;
* mudanças territoriais;
* história mundial;
* mapas históricos;
* análise de fontes.

Exemplo:

```text
Organize os fatos em ordem cronológica:

1. Independência do Brasil
2. Abolição da escravidão
3. Proclamação da República
```

---

# 8. Campanhas Educacionais

Uma campanha deverá reunir diversas missões dentro de um tema.

## Campanhas iniciais sugeridas

### Conhecendo Caraguatatuba

Conteúdos:

* bairros;
* praias;
* rios;
* Serra do Mar;
* Mata Atlântica;
* economia;
* turismo;
* história municipal;
* patrimônio;
* enchente de 1967;
* povos originários;
* preservação ambiental.

### Biomas do Brasil

Conteúdos:

* Amazônia;
* Mata Atlântica;
* Cerrado;
* Caatinga;
* Pantanal;
* Pampa;
* clima;
* fauna;
* flora;
* desmatamento;
* conservação.

### Viagem pela História do Brasil

Conteúdos:

* povos originários;
* chegada dos portugueses;
* capitanias hereditárias;
* ciclo do açúcar;
* mineração;
* escravidão;
* independência;
* abolição;
* república;
* industrialização.

### Capitais do Brasil

Conteúdos:

* capitais;
* estados;
* regiões;
* cultura;
* economia;
* população;
* distância;
* localização.

### Expedição Matemática

Conteúdos:

* distâncias;
* escalas;
* velocidade;
* tempo;
* porcentagem;
* gráficos;
* medidas;
* coordenadas.

### Mistérios da Língua Portuguesa

Conteúdos:

* interpretação;
* leitura;
* ortografia;
* gramática;
* gêneros textuais;
* produção textual.

---

# 9. Estrutura de uma Campanha

Cada campanha deverá possuir:

* título;
* descrição;
* imagem de capa;
* disciplina principal;
* disciplinas complementares;
* ano escolar;
* nível de dificuldade;
* quantidade de missões;
* localização geográfica;
* período histórico, quando aplicável;
* habilidades trabalhadas;
* status;
* data de publicação;
* autor;
* revisores;
* pontuação máxima;
* tempo estimado;
* requisitos de desbloqueio.

---

# 10. Estrutura de uma Missão

Cada missão deverá conter:

* título;
* descrição;
* narrativa;
* objetivo;
* localização;
* coordenadas;
* imagem;
* imagem panorâmica;
* vídeo opcional;
* áudio opcional;
* texto de apoio;
* perguntas;
* pistas;
* tempo estimado;
* pontuação máxima;
* dificuldade;
* habilidade;
* disciplina;
* requisitos;
* explicação final;
* curiosidades;
* recompensa.

Exemplo:

```text
Missão: O Mistério da Serra do Mar

Você está próximo ao Parque Estadual da Serra do Mar.

Observe a paisagem, analise as pistas e responda aos desafios para descobrir onde está e completar a missão.
```

---

# 11. Tipos de Questão

O sistema deverá suportar:

## Objetiva

* uma alternativa correta;
* múltiplas alternativas corretas;
* verdadeiro ou falso.

## Localização no mapa

O aluno deverá clicar em um ponto do mapa.

A pontuação poderá considerar:

* distância da resposta correta;
* tempo;
* quantidade de pistas utilizadas;
* nível da missão.

## Ordenação

O aluno deverá organizar:

* fatos históricos;
* frases;
* etapas;
* acontecimentos;
* números;
* períodos.

## Associação

O aluno deverá ligar:

* estados e capitais;
* fatos e datas;
* palavras e significados;
* biomas e características;
* personagens e acontecimentos.

## Preenchimento

O aluno deverá completar:

* frases;
* palavras;
* números;
* coordenadas;
* cálculos.

## Resposta curta

O aluno deverá digitar uma resposta curta.

A correção poderá utilizar:

* resposta exata;
* palavras-chave;
* comparação textual;
* avaliação assistida por IA.

## Questão com imagem

A resposta será baseada em:

* fotografia;
* mapa;
* gráfico;
* documento;
* obra;
* ilustração.

## Questão com áudio

A atividade poderá utilizar:

* narração;
* depoimento;
* leitura;
* som ambiente;
* pronúncia.

## Questão com vídeo

A atividade poderá utilizar um vídeo curto como contexto.

---

# 12. Mecânica Principal

Fluxo de uma rodada:

```text
1. Aluno escolhe uma campanha.
2. Aluno inicia uma missão.
3. Sistema apresenta narrativa e localização.
4. Aluno observa o mapa, imagem ou panorama.
5. Sistema apresenta o desafio.
6. Aluno responde.
7. Sistema calcula a pontuação.
8. Sistema apresenta correção e explicação.
9. Aluno avança para a próxima etapa.
10. Ao finalizar, recebe experiência e recompensa.
```

---

# 13. Sistema de Pontuação

A pontuação poderá considerar:

* resposta correta;
* distância até a localização correta;
* tempo de resposta;
* quantidade de tentativas;
* quantidade de pistas utilizadas;
* dificuldade;
* sequência de acertos;
* conclusão sem ajuda.

Exemplo de cálculo:

```text
Pontuação base: 1.000 pontos

Desconto por pista: -100 pontos
Desconto por tentativa adicional: -150 pontos
Bônus por resposta rápida: +100 pontos
Bônus por sequência de acertos: +200 pontos
```

A fórmula deverá ser configurável pelo administrador.

---

# 14. Sistema de Distância Geográfica

Nas questões de localização, calcular a distância entre:

* coordenada escolhida pelo aluno;
* coordenada correta.

Utilizar fórmula Haversine ou recursos geoespaciais do PostgreSQL com PostGIS.

Exemplo de pontuação:

```text
Até 1 km: 1.000 pontos
Até 5 km: 900 pontos
Até 20 km: 700 pontos
Até 50 km: 500 pontos
Até 100 km: 300 pontos
Acima de 100 km: 100 pontos
```

As faixas deverão ser configuráveis.

---

# 15. Sistema de Pistas

O aluno poderá utilizar pistas durante uma missão.

Tipos de pista:

* eliminar alternativa;
* mostrar região aproximada;
* revelar primeira letra;
* destacar área do mapa;
* mostrar palavra-chave;
* fornecer explicação parcial;
* mostrar imagem adicional;
* apresentar dado histórico;
* apresentar dica matemática.

Cada pista deverá reduzir a pontuação.

---

# 16. Progressão do Aluno

## Experiência

O aluno ganha experiência ao:

* concluir missões;
* acertar questões;
* concluir campanhas;
* manter sequência;
* participar de desafios;
* melhorar desempenho;
* completar missões especiais.

## Níveis

Exemplo:

```text
Nível 1 — Explorador Iniciante
Nível 2 — Aprendiz de Viajante
Nível 3 — Investigador
Nível 4 — Cartógrafo
Nível 5 — Historiador
Nível 6 — Mestre das Expedições
```

Os níveis deverão ser configuráveis.

## Conquistas

Exemplos:

* primeira missão concluída;
* 10 respostas corretas;
* 5 missões sem pistas;
* especialista em mapas;
* mestre da matemática;
* leitor atento;
* historiador iniciante;
* explorador de Caraguatatuba;
* campeão da turma;
* sequência de sete dias.

---

# 17. Passaporte Virtual

Cada aluno possuirá um passaporte virtual contendo:

* nome;
* avatar;
* escola;
* turma;
* nível;
* experiência;
* campanhas concluídas;
* locais visitados;
* medalhas;
* conquistas;
* coleção;
* desempenho por disciplina.

O passaporte deverá funcionar como perfil gamificado.

---

# 18. Coleções

O aluno poderá desbloquear itens digitais.

Exemplos:

* monumentos;
* animais;
* biomas;
* mapas;
* personagens históricos;
* brasões;
* bandeiras;
* cartões postais;
* artefatos históricos;
* elementos culturais.

Os itens não poderão ser comprados com dinheiro real.

---

# 19. Modo Individual

No modo individual:

* o aluno joga no próprio ritmo;
* o progresso é salvo automaticamente;
* o aluno pode pausar;
* o aluno pode continuar depois;
* o sistema registra tentativas;
* o sistema mostra feedback ao final.

---

# 20. Desafio da Turma

O professor poderá:

* escolher uma campanha;
* selecionar uma ou mais turmas;
* definir data inicial;
* definir prazo;
* limitar tentativas;
* ativar ou desativar ranking;
* acompanhar participação;
* acompanhar resultados.

---

# 21. Competição entre Equipes

O professor poderá dividir a turma em equipes.

Regras:

* pontuação baseada na média;
* não utilizar apenas soma total;
* evitar vantagem para equipes maiores;
* permitir tempo limitado;
* permitir rodadas;
* exibir placar;
* ocultar nomes individuais;
* destacar evolução.

---

# 22. Modo Professor ao Vivo

O professor poderá iniciar uma sessão em sala.

Fluxo:

```text
1. Professor cria uma sala.
2. Sistema gera código.
3. Alunos entram com o código.
4. Professor inicia a rodada.
5. Questão aparece nos dispositivos.
6. Alunos respondem.
7. Sistema apresenta resultado.
8. Professor avança para a próxima questão.
```

Recursos:

* cronômetro;
* placar;
* controle do professor;
* respostas em tempo real;
* apresentação em tela;
* código de acesso;
* bloqueio após início;
* relatório ao final.

---

# 23. Criador de Campanhas

O professor poderá criar campanhas próprias.

Campos:

* título;
* descrição;
* imagem;
* disciplina;
* ano;
* habilidades;
* localizações;
* missões;
* perguntas;
* respostas;
* pistas;
* pontuação;
* explicações;
* dificuldade;
* período de disponibilidade.

Fluxo de publicação:

```text
Rascunho
→ Enviado para revisão
→ Em análise
→ Aprovado
→ Publicado
→ Arquivado
```

Campanhas criadas por professores não deverão ser publicadas para toda a rede automaticamente.

---

# 24. Banco de Questões

O sistema deverá possuir banco centralizado de questões.

Filtros:

* disciplina;
* ano;
* habilidade;
* assunto;
* dificuldade;
* autor;
* campanha;
* tipo;
* status;
* data;
* quantidade de usos.

A questão poderá ser:

* privada;
* disponível para escola;
* disponível para rede;
* oficial;
* arquivada.

---

# 25. Inteligência Artificial

A IA deverá funcionar como assistente, não como publicadora automática.

## Funções da IA

* sugerir questões;
* gerar alternativas;
* criar variações;
* criar explicações;
* adaptar dificuldade;
* sugerir pistas;
* resumir conteúdo;
* transformar texto em missão;
* gerar narrativa;
* identificar habilidades relacionadas;
* avaliar respostas curtas;
* fornecer feedback personalizado;
* recomendar atividades;
* identificar padrões de dificuldade.

## Fluxo obrigatório

```text
Professor informa tema
→ IA gera sugestão
→ Professor revisa
→ Professor altera
→ Professor aprova
→ Conteúdo é publicado
```

Nunca publicar conteúdo automaticamente sem revisão humana.

---

# 26. Painel do Aluno

O dashboard do aluno deverá exibir:

* saudação;
* avatar;
* nível;
* experiência;
* progresso até o próximo nível;
* missão recomendada;
* campanhas em andamento;
* campanhas concluídas;
* desafios da turma;
* conquistas recentes;
* desempenho por disciplina;
* sequência de participação;
* passaporte;
* ranking opcional.

---

# 27. Painel do Professor

O dashboard deverá exibir:

* turmas;
* alunos ativos;
* atividades abertas;
* atividades pendentes;
* média por turma;
* habilidades críticas;
* alunos com dificuldade;
* campanhas mais utilizadas;
* últimas atividades;
* desempenho por disciplina;
* atalhos para criar atividade;
* atalhos para iniciar modo ao vivo.

---

# 28. Painel da Escola

Indicadores:

* total de alunos;
* total de professores;
* alunos ativos;
* professores ativos;
* campanhas aplicadas;
* missões concluídas;
* média por disciplina;
* evolução mensal;
* habilidades críticas;
* comparação entre turmas;
* taxa de participação;
* taxa de conclusão.

---

# 29. Painel da Secretaria

Indicadores gerais:

* escolas participantes;
* alunos cadastrados;
* alunos ativos;
* professores cadastrados;
* professores ativos;
* missões realizadas;
* campanhas utilizadas;
* taxa de participação;
* taxa de conclusão;
* desempenho por disciplina;
* desempenho por ano;
* desempenho por escola;
* habilidades críticas;
* evolução mensal;
* distribuição territorial;
* campanhas mais utilizadas.

Filtros:

* escola;
* turma;
* ano;
* disciplina;
* período;
* campanha;
* habilidade.

---

# 30. Relatórios

O sistema deverá gerar:

* relatório individual do aluno;
* relatório da turma;
* relatório por disciplina;
* relatório por habilidade;
* relatório por campanha;
* relatório por missão;
* relatório por escola;
* relatório consolidado da rede;
* relatório de participação;
* relatório de evolução;
* relatório de tentativas;
* relatório de uso da plataforma.

Formatos:

* visualização online;
* PDF;
* Excel;
* CSV.

---

# 31. Página de Campanhas

A página deverá apresentar cards com:

* imagem;
* título;
* descrição;
* disciplina;
* ano;
* dificuldade;
* quantidade de missões;
* progresso;
* status;
* botão iniciar;
* botão continuar.

Filtros:

* disciplina;
* ano;
* dificuldade;
* tema;
* concluída;
* em andamento;
* recomendada.

---

# 32. Tela de Missão

Layout sugerido:

## Área principal

* mapa;
* imagem;
* panorama;
* conteúdo da missão.

## Área lateral

* narrativa;
* questão;
* alternativas;
* botão responder;
* botão solicitar pista;
* pontuação;
* cronômetro;
* progresso da missão.

## Área inferior

* mapa minimizado;
* botão confirmar localização;
* dicas;
* acessibilidade.

---

# 33. Mapa

Utilizar:

* MapLibre GL;
* OpenStreetMap;
* mapas próprios;
* camadas geográficas;
* marcadores;
* polígonos;
* regiões;
* rotas;
* coordenadas;
* zoom;
* tela cheia.

Evitar dependência total do Google Maps.

O sistema deverá permitir diferentes provedores de mapas.

---

# 34. Imagens Panorâmicas

Suportar:

* imagens 360° próprias;
* fotografias comuns;
* imagens licenciadas;
* acervo da Secretaria;
* conteúdo produzido pelas escolas;
* integrações externas futuras.

Cada imagem deverá registrar:

* autor;
* origem;
* licença;
* autorização;
* data;
* local;
* coordenadas;
* atribuição.

---

# 35. Conteúdo Local

O sistema deverá valorizar o município.

Possibilidades:

* escolas;
* bairros;
* praias;
* rios;
* trilhas;
* Serra do Mar;
* Mata Atlântica;
* patrimônio;
* história local;
* personagens;
* cultura;
* economia;
* turismo;
* meio ambiente.

Professores poderão sugerir locais e conteúdos.

---

# 36. Integração com Sistema Escolar

O sistema deverá permitir integração com o sistema escolar existente.

Dados possíveis:

* aluno;
* matrícula;
* escola;
* turma;
* ano;
* professor;
* disciplina;
* unidade escolar;
* situação da matrícula.

Evitar cadastro manual duplicado.

Possibilidades:

* API REST;
* sincronização programada;
* importação CSV;
* autenticação compartilhada;
* login único.

---

# 37. Autenticação

Utilizar:

* login e senha;
* Laravel Sanctum;
* recuperação de senha;
* controle por perfil;
* sessão segura;
* limitação de tentativas;
* auditoria;
* autenticação integrada futura.

Para alunos, permitir login simplificado definido pela Secretaria.

---

# 38. Permissões

Utilizar controle de acesso por papéis e permissões.

Exemplo:

```text
student
teacher
coordinator
director
school_admin
department_admin
super_admin
```

Permissões devem ser independentes do perfil.

---

# 39. Auditoria

Registrar:

* login;
* logout;
* criação;
* edição;
* exclusão;
* publicação;
* aprovação;
* reprovação;
* alteração de permissão;
* geração por IA;
* exportação;
* acesso administrativo.

Campos:

* usuário;
* ação;
* entidade;
* identificador;
* data;
* IP;
* dispositivo;
* valores anteriores;
* valores posteriores.

---

# 40. Notificações

O sistema poderá notificar:

* nova atividade;
* prazo próximo;
* atividade concluída;
* campanha liberada;
* conquista desbloqueada;
* resultado disponível;
* conteúdo aprovado;
* conteúdo recusado.

Canais:

* dentro do sistema;
* e-mail;
* WhatsApp, quando autorizado;
* push PWA futuramente.

---

# 41. Acessibilidade

A aplicação deverá possuir:

* alto contraste;
* ajuste de fonte;
* suporte a teclado;
* textos alternativos;
* legendas;
* leitura facilitada;
* modo sem animação;
* descrição de imagens;
* compatibilidade com leitor de tela;
* linguagem simples;
* não depender apenas de cores.

---

# 42. Responsividade

A aplicação deverá funcionar em:

* computadores;
* notebooks;
* tablets;
* celulares;
* projetores;
* lousas digitais.

Priorizar:

* desktop para painéis;
* celular e tablet para alunos;
* PWA instalável.

---

# 43. Identidade Visual

Criar layout:

* moderno;
* educacional;
* colorido;
* intuitivo;
* acessível;
* responsivo;
* gamificado sem aparência infantil excessiva.

Cores poderão ser configuradas pelo administrador.

Elementos visuais:

* mapas;
* bússola;
* passaporte;
* medalhas;
* pins;
* trilhas;
* cartões;
* mascote opcional;
* ilustrações geográficas.

---

# 44. Stack Recomendada (brief original — revisada em 01-arquitetura-e-plano.md)

## Front-end

```text
React
Next.js
TypeScript
Tailwind CSS
MapLibre GL
React Query
Zustand
PWA
```

## Back-end

```text
Laravel
PHP
Laravel Sanctum
Laravel Queues
Laravel Scheduler
API REST
WebSockets para partidas ao vivo
```

## Banco

```text
PostgreSQL
PostGIS
```

PostgreSQL com PostGIS deverá ser utilizado por causa das operações geográficas.

## Infraestrutura

```text
Docker
Redis
Nginx
Cloudflare
Cloudflare R2 ou storage S3
VPS
GitHub Actions
```

## Integrações

```text
OpenAI API
n8n
Evolution API
Sistema escolar
Serviço de e-mail
```

---

# 45. Arquitetura (brief original — revisada em 01-arquitetura-e-plano.md)

Estrutura recomendada:

```text
Frontend Next.js
        ↓
API Laravel
        ↓
PostgreSQL + PostGIS
        ↓
Redis
        ↓
Storage S3/R2
```

Serviços adicionais:

```text
OpenAI API
n8n
Evolution API
WebSocket Server
```

---

# 46. Estrutura do Banco de Dados (brief original — versão MySQL final em 02-database-mysql.md)

## users

```text
id
name
email
password
role_id
school_id
status
avatar_url
last_login_at
created_at
updated_at
```

## roles

```text
id
name
slug
description
created_at
updated_at
```

## permissions

```text
id
name
slug
description
created_at
updated_at
```

## role_permissions

```text
role_id
permission_id
```

## schools

```text
id
name
code
address
district
city
state
latitude
longitude
status
created_at
updated_at
```

## classes

```text
id
school_id
name
grade_id
school_year_id
shift
status
created_at
updated_at
```

## students

```text
id
user_id
registration_number
school_id
class_id
birth_date
status
created_at
updated_at
```

## teachers

```text
id
user_id
school_id
registration_number
status
created_at
updated_at
```

## teacher_classes

```text
teacher_id
class_id
subject_id
```

## school_years

```text
id
year
starts_at
ends_at
status
created_at
updated_at
```

## grades

```text
id
name
code
education_level
order
created_at
updated_at
```

## subjects

```text
id
name
slug
icon
color
status
created_at
updated_at
```

## skills

```text
id
subject_id
grade_id
code
title
description
status
created_at
updated_at
```

## campaigns

```text
id
title
slug
description
cover_image_url
primary_subject_id
grade_id
difficulty
status
visibility
author_id
published_at
estimated_minutes
max_score
created_at
updated_at
```

## campaign_subjects

```text
campaign_id
subject_id
```

## campaign_skills

```text
campaign_id
skill_id
```

## missions

```text
id
campaign_id
title
slug
description
narrative
objective
order
difficulty
estimated_minutes
max_score
status
unlock_rule
created_at
updated_at
```

## mission_stages

```text
id
mission_id
title
description
content
order
location_id
created_at
updated_at
```

## locations

```text
id
name
description
latitude
longitude
geography
city
state
country
biome
historical_period
source_type
source_url
license
attribution
created_at
updated_at
```

## media

```text
id
type
title
file_url
thumbnail_url
source
license
author
attribution
latitude
longitude
created_at
updated_at
```

## mission_media

```text
mission_id
media_id
order
```

## questions

```text
id
mission_stage_id
subject_id
skill_id
grade_id
type
statement
explanation
difficulty
max_score
time_limit_seconds
status
author_id
created_at
updated_at
```

## question_options

```text
id
question_id
text
image_url
is_correct
order
created_at
updated_at
```

## question_locations

```text
id
question_id
latitude
longitude
accepted_radius_meters
created_at
updated_at
```

## hints

```text
id
question_id
type
content
score_penalty
order
created_at
updated_at
```

## activities

```text
id
teacher_id
campaign_id
title
description
starts_at
ends_at
attempt_limit
ranking_enabled
status
created_at
updated_at
```

## activity_classes

```text
activity_id
class_id
```

## attempts

```text
id
student_id
activity_id
campaign_id
mission_id
started_at
completed_at
score
experience
status
time_spent_seconds
created_at
updated_at
```

## attempt_answers

```text
id
attempt_id
question_id
answer_text
selected_option_id
latitude
longitude
distance_meters
is_correct
score
time_spent_seconds
hints_used
created_at
updated_at
```

## student_progress

```text
id
student_id
campaign_id
mission_id
progress_percent
best_score
attempts_count
completed_at
created_at
updated_at
```

## levels

```text
id
name
minimum_experience
maximum_experience
icon
order
created_at
updated_at
```

## achievements

```text
id
title
description
icon
rule_type
rule_value
experience_reward
status
created_at
updated_at
```

## student_achievements

```text
id
student_id
achievement_id
unlocked_at
created_at
updated_at
```

## collectible_items

```text
id
name
description
category
image_url
rarity
status
created_at
updated_at
```

## student_collectibles

```text
id
student_id
collectible_item_id
unlocked_at
created_at
updated_at
```

## teams

```text
id
activity_id
name
created_at
updated_at
```

## team_members

```text
team_id
student_id
```

## live_sessions

```text
id
teacher_id
campaign_id
access_code
status
started_at
ended_at
created_at
updated_at
```

## live_session_participants

```text
id
live_session_id
student_id
score
joined_at
created_at
updated_at
```

## content_reviews

```text
id
content_type
content_id
reviewer_id
status
notes
reviewed_at
created_at
updated_at
```

## notifications

```text
id
user_id
title
message
type
read_at
created_at
updated_at
```

## audit_logs

```text
id
user_id
action
entity_type
entity_id
old_values
new_values
ip_address
user_agent
created_at
```

---

# 47. APIs Principais (brief original — contrato detalhado em 03-api-contract.md)

## Autenticação

```text
POST /api/login
POST /api/logout
POST /api/forgot-password
POST /api/reset-password
GET /api/me
```

## Campanhas

```text
GET /api/campaigns
GET /api/campaigns/{id}
POST /api/campaigns
PUT /api/campaigns/{id}
DELETE /api/campaigns/{id}
POST /api/campaigns/{id}/publish
```

## Missões

```text
GET /api/campaigns/{id}/missions
GET /api/missions/{id}
POST /api/missions
PUT /api/missions/{id}
DELETE /api/missions/{id}
```

## Questões

```text
GET /api/questions
POST /api/questions
PUT /api/questions/{id}
DELETE /api/questions/{id}
POST /api/questions/{id}/review
```

## Tentativas

```text
POST /api/attempts
GET /api/attempts/{id}
POST /api/attempts/{id}/answers
POST /api/attempts/{id}/complete
```

## Relatórios

```text
GET /api/reports/student/{id}
GET /api/reports/class/{id}
GET /api/reports/school/{id}
GET /api/reports/network
```

## IA

```text
POST /api/ai/generate-question
POST /api/ai/generate-mission
POST /api/ai/generate-explanation
POST /api/ai/evaluate-short-answer
```

---

# 48. Regras de Negócio

* o aluno só acessa campanhas liberadas;
* o aluno só acessa atividades vinculadas à sua turma;
* tentativas devem respeitar o limite definido;
* toda resposta deve ser registrada;
* a melhor pontuação deve ser mantida;
* o professor visualiza apenas suas turmas;
* o diretor visualiza apenas sua escola;
* a Secretaria visualiza toda a rede;
* conteúdos gerados por IA exigem revisão;
* conteúdos criados por professores exigem aprovação para publicação na rede;
* toda imagem deve possuir origem e licença;
* ranking nominal deverá ser opcional;
* dados sensíveis não deverão ser exibidos publicamente;
* exclusões administrativas deverão preferencialmente ser lógicas;
* alterações críticas deverão gerar auditoria.

---

# 49. LGPD e Segurança

A aplicação deverá seguir princípios de:

* finalidade;
* necessidade;
* minimização;
* segurança;
* controle de acesso;
* rastreabilidade;
* privacidade.

Requisitos:

* não expor dados de alunos;
* não permitir ranking público nominal por padrão;
* armazenar apenas dados necessários;
* registrar acessos administrativos;
* aplicar política de retenção;
* usar HTTPS;
* criptografar credenciais;
* utilizar senhas com hash seguro;
* controlar permissões;
* limitar requisições;
* validar uploads;
* bloquear arquivos perigosos;
* realizar backups;
* manter logs.

---

# 50. MVP

A primeira versão deverá conter:

* autenticação;
* integração básica com alunos e turmas;
* perfis aluno, professor e administrador;
* cadastro de escolas;
* cadastro de turmas;
* cadastro de disciplinas;
* cadastro de habilidades;
* cadastro de campanhas;
* cadastro de missões;
* questões objetivas;
* questões de localização;
* mapa 2D;
* imagens comuns;
* algumas imagens 360°;
* sistema de pistas;
* pontuação;
* experiência;
* níveis;
* conquistas;
* passaporte do aluno;
* atividades por turma;
* relatório do professor;
* painel administrativo;
* três campanhas iniciais.

Campanhas do MVP:

```text
1. Conhecendo Caraguatatuba
2. Biomas do Brasil
3. Viagem pela História do Brasil
```

Público inicial:

```text
6º e 7º anos
```

Quantidade recomendada:

```text
3 campanhas
10 missões por campanha
4 questões por missão
Total aproximado: 120 questões
```

---

# 51. Funcionalidades Fora do MVP

Não desenvolver inicialmente:

* mundo 3D;
* avatares 3D complexos;
* marketplace;
* compra de moedas;
* aplicativo nativo;
* multiplayer massivo;
* geração automática sem revisão;
* todas as séries;
* todos os conteúdos curriculares;
* integração completa com Google Street View;
* realidade aumentada;
* realidade virtual.

Essas funções aumentariam o custo e atrasariam a validação.

---

# 52. Fase 2

Após validação:

* modo professor ao vivo;
* competições entre equipes;
* criador de campanhas;
* banco compartilhado de questões;
* respostas abertas;
* IA assistiva;
* dificuldade adaptativa;
* notificações;
* PWA;
* panoramas próprios;
* relatórios avançados;
* integração completa com sistema escolar.

---

# 53. Fase 3

Possibilidades futuras:

* versão para outros municípios;
* white label;
* personalização por rede;
* acervo territorial municipal;
* aplicativo móvel;
* realidade aumentada;
* personagens históricos com IA;
* campanhas colaborativas;
* biblioteca pública de conteúdos;
* integração com avaliações diagnósticas;
* trilhas personalizadas.

---

# 54. Critérios de Aceite do MVP

O MVP será considerado funcional quando:

* aluno conseguir entrar;
* aluno visualizar campanhas;
* aluno iniciar missão;
* aluno responder perguntas;
* aluno marcar localização no mapa;
* sistema calcular pontuação;
* sistema registrar respostas;
* aluno ganhar experiência;
* aluno desbloquear conquistas;
* professor atribuir campanha;
* professor acompanhar turma;
* professor visualizar dificuldades;
* administrador cadastrar conteúdo;
* Secretaria visualizar indicadores gerais;
* relatórios puderem ser exportados.

---

# 55. Diretrizes de Desenvolvimento

* utilizar código modular;
* evitar overengineering;
* separar conteúdo pedagógico da lógica da aplicação;
* utilizar componentes reutilizáveis;
* manter API documentada;
* implementar testes nas regras críticas;
* utilizar migrations;
* utilizar seeders;
* utilizar filas para processos pesados;
* aplicar cache em relatórios;
* utilizar storage externo;
* implementar logs;
* manter ambiente Docker;
* separar desenvolvimento, homologação e produção.

---

# 56. Diretrizes para a Interface

Criar inicialmente as seguintes páginas:

```text
/login
/dashboard
/campaigns
/campaigns/{id}
/missions/{id}
/play/{missionId}
/passport
/achievements
/activities
/ranking
/reports
/teacher/classes
/teacher/activities
/teacher/content
/admin
/admin/schools
/admin/users
/admin/campaigns
/admin/missions
/admin/questions
/admin/skills
/admin/reports
/admin/settings
```

---

# 57. Design das Páginas

## Login

* logo;
* nome da plataforma;
* campo de usuário;
* campo de senha;
* botão entrar;
* recuperação de senha;
* fundo com elementos cartográficos.

## Dashboard do aluno

* cabeçalho;
* avatar;
* nível;
* barra de experiência;
* missão recomendada;
* campanhas;
* conquistas;
* desempenho;
* passaporte.

## Tela de jogo

* mapa ou imagem em destaque;
* narrativa;
* questão;
* alternativas;
* botão de pista;
* cronômetro;
* pontuação;
* progresso;
* botão confirmar.

## Dashboard do professor

* cards de indicadores;
* gráfico de desempenho;
* tabela de atividades;
* habilidades críticas;
* lista de alunos;
* atalhos.

## Painel administrativo

* menu lateral;
* indicadores;
* tabelas;
* filtros;
* formulários;
* editor de campanhas;
* editor de missões;
* editor de questões.

---

# 58. Prompt Resumido para Desenvolvimento (brief original)

Desenvolva uma plataforma web educacional gamificada chamada provisoriamente "Expedição do Saber".

A plataforma será utilizada por uma Secretaria Municipal de Educação e deverá trabalhar Matemática, Língua Portuguesa, Geografia e História por meio de mapas, imagens, localizações, missões e desafios.

O sistema deverá possuir perfis de aluno, professor, coordenador, diretor, administrador escolar e administrador da Secretaria.

O aluno deverá explorar campanhas, iniciar missões, responder questões, marcar localizações no mapa, utilizar pistas, acumular pontos, ganhar experiência, evoluir de nível, desbloquear conquistas e acompanhar seu passaporte virtual.

O professor deverá atribuir campanhas para turmas, criar atividades, acompanhar resultados, visualizar dificuldades por habilidade, consultar tentativas e gerar relatórios.

O administrador deverá gerenciar escolas, usuários, disciplinas, habilidades, campanhas, missões, questões, imagens, localizações, conquistas, níveis e configurações.

Utilizar:

* Next.js;
* React;
* TypeScript;
* Tailwind CSS;
* MapLibre GL;
* Laravel;
* Laravel Sanctum;
* PostgreSQL;
* PostGIS;
* Redis;
* Docker;
* Cloudflare R2.

Criar uma interface moderna, responsiva, acessível e gamificada.

Priorizar inicialmente um MVP com três campanhas:

* Conhecendo Caraguatatuba;
* Biomas do Brasil;
* Viagem pela História do Brasil.

O MVP deverá atender inicialmente alunos do 6º e 7º anos.

Não criar inicialmente mundo 3D, moedas pagas, marketplace, realidade virtual ou multiplayer complexo.
