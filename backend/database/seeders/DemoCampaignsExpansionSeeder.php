<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Grade;
use App\Models\Location;
use App\Models\Mission;
use App\Models\MissionStage;
use App\Models\Question;
use App\Models\Skill;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seed de DEMONSTRAÇÃO — 2 campanhas extras (além de "Conhecendo
 * Caraguatatuba", já semeada por CaraguatatubaCampaignSeeder), pra dar
 * volume de apresentação sem inventar fato específico arriscado.
 *
 * NÃO é o conteúdo final de produção (segue não sendo as ~120 questões da
 * seção 50 do brief) e NÃO deve ser usado com alunos reais sem revisão
 * pedagógica — ver docs/01-arquitetura-e-plano.md seção 7 (Sprint 4). Regra
 * seguida ao escrever: só fato de conhecimento público bem estabelecido e
 * fácil de verificar (agrupamento oficial dos 4 municípios do Litoral Norte
 * de SP, Ilhabela ser município insular, bioma/relevo em termos gerais).
 * Nenhuma data específica, nome de rua, ou estatística de população — exatamente
 * o tipo de dado que exigiria fonte oficial da Secretaria pra afirmar com
 * segurança, e que não faz falta pro objetivo de demonstração.
 */
class DemoCampaignsExpansionSeeder extends Seeder
{
    public function run(): void
    {
        $geografia = Subject::where('slug', 'geografia')->firstOrFail();
        $grade7 = Grade::where('code', '7EF2')->firstOrFail();
        $author = User::whereHas('role', fn ($q) => $q->where('slug', 'super_admin'))->first();

        $skillRegiao = Skill::firstOrCreate(
            ['subject_id' => $geografia->id, 'grade_id' => $grade7->id, 'title' => 'Reconhecer a organização regional do território (municípios vizinhos)'],
            ['status' => 'active']
        );
        $skillRelevoClima = Skill::firstOrCreate(
            ['subject_id' => $geografia->id, 'grade_id' => $grade7->id, 'title' => 'Relacionar relevo, clima e vegetação de uma região'],
            ['status' => 'active']
        );
        $skillBiomas = Skill::firstOrCreate(
            ['subject_id' => $geografia->id, 'grade_id' => $grade7->id, 'title' => 'Reconhecer biomas e paisagens naturais brasileiras'],
            ['status' => 'active']
        );

        $this->seedLitoralNorteCampaign($geografia, $grade7, $author, $skillRegiao, $skillRelevoClima);
        $this->seedMataAtlanticaCampaign($geografia, $grade7, $author, $skillBiomas);
    }

    private function seedLitoralNorteCampaign(Subject $geografia, Grade $grade7, ?User $author, Skill $skillRegiao, Skill $skillRelevoClima): void
    {
        // Ponto de referência aproximado pra região inteira, não um endereço
        // exato — por isso o raio aceito é bem mais largo que o da campanha 1.
        $regiao = Location::firstOrCreate(
            ['name' => 'Região do Litoral Norte de SP (referência)'],
            ['latitude' => -23.6203, 'longitude' => -45.4131, 'city' => 'Caraguatatuba', 'state' => 'SP', 'source_type' => 'aproximado']
        );

        $campaign = Campaign::updateOrCreate(
            ['slug' => 'vizinhos-do-litoral-norte'],
            [
                'title' => 'Vizinhos do Litoral Norte',
                'description' => 'Descubra quais municípios formam a região do Litoral Norte de São Paulo e o que eles têm em comum.',
                'primary_subject_id' => $geografia->id,
                'grade_id' => $grade7->id,
                'difficulty' => 'medium',
                'status' => 'published',
                'visibility' => 'network',
                'author_id' => $author?->id,
                'published_at' => now(),
                'estimated_minutes' => 20,
                'max_score' => 4000,
            ]
        );
        $campaign->subjects()->syncWithoutDetaching([$geografia->id]);
        $campaign->skills()->syncWithoutDetaching([$skillRegiao->id, $skillRelevoClima->id]);

        $mission1 = Mission::updateOrCreate(
            ['campaign_id' => $campaign->id, 'slug' => 'os-quatro-municipios'],
            [
                'title' => 'Os quatro municípios do litoral',
                'narrative' => 'Caraguatatuba não está sozinha no mapa — ela faz parte de uma região com outros três municípios vizinhos.',
                'objective' => 'Identificar os municípios que formam o Litoral Norte de São Paulo.',
                'order' => 1, 'difficulty' => 'easy', 'estimated_minutes' => 10, 'max_score' => 2000, 'status' => 'published',
            ]
        );
        $stage1 = MissionStage::updateOrCreate(
            ['mission_id' => $mission1->id, 'order' => 1],
            ['content' => 'Olhando o mapa da costa paulista, vamos identificar a região em que Caraguatatuba fica.', 'location_id' => $regiao->id]
        );

        $q1 = Question::updateOrCreate(
            ['mission_stage_id' => $stage1->id, 'type' => 'single_choice', 'statement' => 'Além de Caraguatatuba, quais são os outros três municípios que formam a região do Litoral Norte de São Paulo?'],
            [
                'subject_id' => $geografia->id, 'skill_id' => $skillRegiao->id, 'grade_id' => $grade7->id,
                'explanation' => 'O Litoral Norte paulista é formado por Caraguatatuba, Ubatuba, São Sebastião e Ilhabela. Santos, Guarujá e Praia Grande ficam na Baixada Santista — outra região do litoral de SP.',
                'difficulty' => 'easy', 'max_score' => 1000, 'status' => 'official', 'author_id' => $author?->id,
            ]
        );
        $this->syncOptions($q1, [
            ['text' => 'Ubatuba, São Sebastião e Ilhabela', 'is_correct' => true],
            ['text' => 'Santos, Guarujá e Praia Grande', 'is_correct' => false],
            ['text' => 'Bertioga, Peruíbe e Itanhaém', 'is_correct' => false],
            ['text' => 'Angra dos Reis, Paraty e Ilha Grande', 'is_correct' => false],
        ]);

        $q2 = Question::updateOrCreate(
            ['mission_stage_id' => $stage1->id, 'type' => 'true_false', 'statement' => 'Ilhabela é o único município do Litoral Norte de São Paulo que fica totalmente em uma ilha.'],
            [
                'subject_id' => $geografia->id, 'skill_id' => $skillRegiao->id, 'grade_id' => $grade7->id,
                'explanation' => 'Verdadeiro — Ilhabela ocupa a Ilha de São Sebastião, sendo o único dos quatro municípios totalmente insular.',
                'difficulty' => 'easy', 'max_score' => 1000, 'status' => 'official', 'author_id' => $author?->id,
            ]
        );
        $this->syncOptions($q2, [
            ['text' => 'Verdadeiro', 'is_correct' => true],
            ['text' => 'Falso', 'is_correct' => false],
        ]);

        $q3 = Question::updateOrCreate(
            ['mission_stage_id' => $stage1->id, 'type' => 'short_answer'],
            [
                'subject_id' => $geografia->id, 'skill_id' => $skillRegiao->id, 'grade_id' => $grade7->id,
                'statement' => 'Qual oceano banha o litoral de Caraguatatuba e dos municípios vizinhos?',
                'explanation' => 'O oceano Atlântico banha toda a costa paulista, incluindo o Litoral Norte.',
                'difficulty' => 'easy', 'max_score' => 1000, 'status' => 'official', 'author_id' => $author?->id,
            ]
        );
        $this->syncOptions($q3, [
            ['text' => 'Atlântico', 'is_correct' => true, 'order' => 0],
            ['text' => 'oceano atlântico', 'is_correct' => true, 'order' => 0],
        ]);

        $mission2 = Mission::updateOrCreate(
            ['campaign_id' => $campaign->id, 'slug' => 'relevo-e-clima-da-regiao'],
            [
                'title' => 'Relevo e clima da região',
                'narrative' => 'A Serra do Mar não fica só em Caraguatatuba — ela molda a paisagem e o clima de todo o Litoral Norte.',
                'objective' => 'Relacionar o relevo da Serra do Mar ao clima da região.',
                'order' => 2, 'difficulty' => 'medium', 'estimated_minutes' => 10, 'max_score' => 2000, 'status' => 'published',
                'unlock_rule' => ['requires_mission_id' => $mission1->id],
            ]
        );
        $stage2 = MissionStage::updateOrCreate(
            ['mission_id' => $mission2->id, 'order' => 1],
            ['content' => 'A serra que você viu na primeira expedição acompanha toda a costa da região.', 'location_id' => $regiao->id]
        );

        $q4 = Question::updateOrCreate(
            ['mission_stage_id' => $stage2->id, 'type' => 'single_choice', 'statement' => 'A Serra do Mar, presente em toda a região do Litoral Norte, é um exemplo de que tipo de relevo?'],
            [
                'subject_id' => $geografia->id, 'skill_id' => $skillRelevoClima->id, 'grade_id' => $grade7->id,
                'explanation' => 'A Serra do Mar é uma cadeia de montanhas (serra) que separa o litoral do planalto brasileiro.',
                'difficulty' => 'medium', 'max_score' => 1000, 'status' => 'official', 'author_id' => $author?->id,
            ]
        );
        $this->syncOptions($q4, [
            ['text' => 'Planalto/serra (montanhas)', 'is_correct' => true],
            ['text' => 'Planície', 'is_correct' => false],
            ['text' => 'Depressão', 'is_correct' => false],
            ['text' => 'Chapada', 'is_correct' => false],
        ]);

        $q5 = Question::updateOrCreate(
            ['mission_stage_id' => $stage2->id, 'type' => 'true_false', 'statement' => 'Por causa da Serra do Mar, o Litoral Norte de São Paulo tem clima predominantemente quente e úmido, com chuvas frequentes ao longo do ano.'],
            [
                'subject_id' => $geografia->id, 'skill_id' => $skillRelevoClima->id, 'grade_id' => $grade7->id,
                'explanation' => 'Verdadeiro — o encontro do ar úmido do oceano com a Serra do Mar provoca chuvas frequentes na região (efeito orográfico).',
                'difficulty' => 'medium', 'max_score' => 1000, 'status' => 'official', 'author_id' => $author?->id,
            ]
        );
        $this->syncOptions($q5, [
            ['text' => 'Verdadeiro', 'is_correct' => true],
            ['text' => 'Falso', 'is_correct' => false],
        ]);

        $q6 = Question::updateOrCreate(
            ['mission_stage_id' => $stage2->id, 'type' => 'matching', 'statement' => 'Associe cada elemento à sua característica correta.'],
            [
                'subject_id' => $geografia->id, 'skill_id' => $skillRegiao->id, 'grade_id' => $grade7->id,
                'explanation' => 'Ilhabela é o único município insular da região; a Serra do Mar é a cadeia de montanhas que separa o litoral do planalto.',
                'difficulty' => 'medium', 'max_score' => 1000, 'status' => 'official', 'author_id' => $author?->id,
            ]
        );
        $q6->options()->delete();
        $q6->options()->createMany([
            ['text' => 'Ilhabela', 'side' => 'left', 'order' => 0],
            ['text' => 'Serra do Mar', 'side' => 'left', 'order' => 1],
            ['text' => 'Único município totalmente insular (ilha) da região', 'side' => 'right', 'order' => 0],
            ['text' => 'Cadeia de montanhas que separa o litoral do planalto', 'side' => 'right', 'order' => 1],
        ]);
    }

    private function seedMataAtlanticaCampaign(Subject $geografia, Grade $grade7, ?User $author, Skill $skillBiomas): void
    {
        $campaign = Campaign::updateOrCreate(
            ['slug' => 'mata-atlantica-em-foco'],
            [
                'title' => 'Mata Atlântica em Foco',
                'description' => 'Entenda por que a Mata Atlântica, o bioma que cobre a Serra do Mar, é tão importante e tão ameaçada.',
                'primary_subject_id' => $geografia->id,
                'grade_id' => $grade7->id,
                'difficulty' => 'medium',
                'status' => 'published',
                'visibility' => 'network',
                'author_id' => $author?->id,
                'published_at' => now(),
                'estimated_minutes' => 12,
                'max_score' => 2000,
            ]
        );
        $campaign->subjects()->syncWithoutDetaching([$geografia->id]);
        $campaign->skills()->syncWithoutDetaching([$skillBiomas->id]);

        $serraDoMar = Location::firstOrCreate(
            ['name' => 'Parque Estadual da Serra do Mar — Núcleo Caraguatatuba'],
            ['latitude' => -23.6520, 'longitude' => -45.4550, 'city' => 'Caraguatatuba', 'state' => 'SP', 'biome' => 'Mata Atlântica', 'source_type' => 'aproximado']
        );

        $mission = Mission::updateOrCreate(
            ['campaign_id' => $campaign->id, 'slug' => 'um-bioma-ameacado'],
            [
                'title' => 'Um bioma ameaçado',
                'narrative' => 'A mesma mata que você viu na Serra do Mar é parte de um dos biomas mais ricos — e mais ameaçados — do Brasil.',
                'objective' => 'Reconhecer a importância e a situação de ameaça da Mata Atlântica.',
                'order' => 1, 'difficulty' => 'medium', 'estimated_minutes' => 12, 'max_score' => 2000, 'status' => 'published',
            ]
        );
        $stage = MissionStage::updateOrCreate(
            ['mission_id' => $mission->id, 'order' => 1],
            ['content' => 'De volta à Serra do Mar, agora olhando com mais atenção para a mata ao redor.', 'location_id' => $serraDoMar->id]
        );

        $q1 = Question::updateOrCreate(
            ['mission_stage_id' => $stage->id, 'type' => 'true_false', 'statement' => 'A Mata Atlântica é um dos biomas com maior biodiversidade do planeta, apesar de ser um dos mais desmatados do Brasil.'],
            [
                'subject_id' => $geografia->id, 'skill_id' => $skillBiomas->id, 'grade_id' => $grade7->id,
                'explanation' => 'Verdadeiro — restam poucos remanescentes preservados de Mata Atlântica, e mesmo assim ela concentra grande diversidade de espécies.',
                'difficulty' => 'easy', 'max_score' => 500, 'status' => 'official', 'author_id' => $author?->id,
            ]
        );
        $this->syncOptions($q1, [
            ['text' => 'Verdadeiro', 'is_correct' => true],
            ['text' => 'Falso', 'is_correct' => false],
        ]);

        $q2 = Question::updateOrCreate(
            ['mission_stage_id' => $stage->id, 'type' => 'single_choice', 'statement' => 'Qual é o principal motivo de a Mata Atlântica ser hoje um bioma tão reduzido em relação à sua área original?'],
            [
                'subject_id' => $geografia->id, 'skill_id' => $skillBiomas->id, 'grade_id' => $grade7->id,
                'explanation' => 'O desmatamento histórico para agricultura, pecuária e crescimento das cidades é a principal causa da redução da Mata Atlântica desde o período colonial.',
                'difficulty' => 'medium', 'max_score' => 500, 'status' => 'official', 'author_id' => $author?->id,
            ]
        );
        $this->syncOptions($q2, [
            ['text' => 'Desmatamento para agricultura, pecuária e crescimento das cidades', 'is_correct' => true],
            ['text' => 'Queimadas naturais frequentes', 'is_correct' => false],
            ['text' => 'Clima cada vez mais seco', 'is_correct' => false],
            ['text' => 'Avanço do deserto', 'is_correct' => false],
        ]);

        $q3 = Question::updateOrCreate(
            ['mission_stage_id' => $stage->id, 'type' => 'short_answer'],
            [
                'subject_id' => $geografia->id, 'skill_id' => $skillBiomas->id, 'grade_id' => $grade7->id,
                'statement' => 'Como se chama a cadeia de montanhas que acompanha grande parte do litoral brasileiro, incluindo a região de Caraguatatuba, e que ainda preserva remanescentes de Mata Atlântica?',
                'explanation' => 'A Serra do Mar preserva alguns dos remanescentes mais importantes de Mata Atlântica do país.',
                'difficulty' => 'easy', 'max_score' => 500, 'status' => 'official', 'author_id' => $author?->id,
            ]
        );
        $this->syncOptions($q3, [
            ['text' => 'Serra do Mar', 'is_correct' => true, 'order' => 0],
            ['text' => 'serra do mar', 'is_correct' => true, 'order' => 0],
        ]);

        $q4 = Question::updateOrCreate(
            ['mission_stage_id' => $stage->id, 'type' => 'single_choice', 'statement' => 'Qual destes animais é frequentemente citado como símbolo da fauna da Mata Atlântica brasileira?'],
            [
                'subject_id' => $geografia->id, 'skill_id' => $skillBiomas->id, 'grade_id' => $grade7->id,
                'explanation' => 'O mico-leão-dourado é um dos símbolos mais conhecidos da fauna da Mata Atlântica e de sua conservação.',
                'difficulty' => 'medium', 'max_score' => 500, 'status' => 'official', 'author_id' => $author?->id,
            ]
        );
        $this->syncOptions($q4, [
            ['text' => 'Mico-leão-dourado', 'is_correct' => true],
            ['text' => 'Arara-azul', 'is_correct' => false],
            ['text' => 'Onça-pintada', 'is_correct' => false],
            ['text' => 'Boto-cor-de-rosa', 'is_correct' => false],
        ]);
    }

    private function syncOptions(Question $question, array $options): void
    {
        $question->options()->delete();
        foreach ($options as $index => $option) {
            $question->options()->create([
                'text' => $option['text'],
                'is_correct' => $option['is_correct'] ?? false,
                'order' => $option['order'] ?? $index,
            ]);
        }
    }
}
