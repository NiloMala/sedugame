<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Campaign;
use App\Models\Level;
use App\Models\Location;
use App\Models\Mission;
use App\Models\MissionStage;
use App\Models\Question;
use App\Models\Role;
use App\Models\Skill;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seed de DEMONSTRAÇÃO — prova que o pipeline campanha → missão → etapa →
 * questão funciona ponta a ponta, com os 8 tipos de questão representados.
 *
 * NÃO é o conteúdo final de produção: são ~8 questões, não as ~120 previstas
 * no MVP (seção 50 do brief). Fatos gerais (litoral norte de SP, Mata
 * Atlântica, Serra do Mar) são de conhecimento público consolidado; datas e
 * coordenadas específicas são aproximadas para fins de demonstração e devem
 * ser revisadas/precisas por quem tem histórico/GPS da Secretaria antes de ir
 * pra produção — sinalizado explicitamente pra não passar despercebido.
 */
class CaraguatatubaCampaignSeeder extends Seeder
{
    public function run(): void
    {
        $geografia = Subject::where('slug', 'geografia')->firstOrFail();
        $historia = Subject::where('slug', 'historia')->firstOrFail();
        $grade6 = \App\Models\Grade::where('code', '6EF2')->firstOrFail();
        $author = User::whereHas('role', fn ($q) => $q->where('slug', 'super_admin'))->first();

        $skillLocalizacao = Skill::firstOrCreate(
            ['subject_id' => $geografia->id, 'grade_id' => $grade6->id, 'title' => 'Localizar e caracterizar o território do município'],
            ['status' => 'active']
        );
        $skillBiomas = Skill::firstOrCreate(
            ['subject_id' => $geografia->id, 'grade_id' => $grade6->id, 'title' => 'Reconhecer biomas e paisagens naturais brasileiras'],
            ['status' => 'active']
        );
        $skillHistoriaLocal = Skill::firstOrCreate(
            ['subject_id' => $historia->id, 'grade_id' => $grade6->id, 'title' => 'Compreender a história e a economia local'],
            ['status' => 'active']
        );

        // Coordenadas aproximadas (uso demonstrativo — revisar com dado GPS real antes de produção).
        $centro = Location::firstOrCreate(
            ['name' => 'Centro de Caraguatatuba'],
            ['latitude' => -23.6203, 'longitude' => -45.4131, 'city' => 'Caraguatatuba', 'state' => 'SP', 'source_type' => 'aproximado']
        );
        $serraDoMar = Location::firstOrCreate(
            ['name' => 'Parque Estadual da Serra do Mar — Núcleo Caraguatatuba'],
            ['latitude' => -23.6520, 'longitude' => -45.4550, 'city' => 'Caraguatatuba', 'state' => 'SP', 'biome' => 'Mata Atlântica', 'source_type' => 'aproximado']
        );
        $praia = Location::firstOrCreate(
            ['name' => 'Praia Martim de Sá'],
            ['latitude' => -23.6145, 'longitude' => -45.4010, 'city' => 'Caraguatatuba', 'state' => 'SP', 'source_type' => 'aproximado']
        );

        $campaign = Campaign::updateOrCreate(
            ['slug' => 'conhecendo-caraguatatuba'],
            [
                'title' => 'Conhecendo Caraguatatuba',
                'description' => 'Explore o território, a natureza e a história do seu município.',
                'primary_subject_id' => $geografia->id,
                'grade_id' => $grade6->id,
                'difficulty' => 'easy',
                'status' => 'published',
                'visibility' => 'network',
                'author_id' => $author?->id,
                'published_at' => now(),
                'estimated_minutes' => 30,
                'max_score' => 3000,
            ]
        );
        $campaign->subjects()->syncWithoutDetaching([$geografia->id, $historia->id]);
        $campaign->skills()->syncWithoutDetaching([$skillLocalizacao->id, $skillBiomas->id, $skillHistoriaLocal->id]);

        // ── Missão 1: Chegando a Caraguatatuba ────────────────────────────
        $mission1 = Mission::updateOrCreate(
            ['campaign_id' => $campaign->id, 'slug' => 'chegando-a-caraguatatuba'],
            [
                'title' => 'Chegando a Caraguatatuba',
                'narrative' => 'Sua expedição começa no centro da cidade. Observe o mapa e responda aos primeiros desafios.',
                'objective' => 'Localizar o município e reconhecer sua região geográfica.',
                'order' => 1,
                'difficulty' => 'easy',
                'estimated_minutes' => 10,
                'max_score' => 2000,
                'status' => 'published',
            ]
        );
        $stage1 = MissionStage::updateOrCreate(
            ['mission_id' => $mission1->id, 'order' => 1],
            ['content' => 'Você está no centro de Caraguatatuba. Marque no mapa a localização aproximada da cidade.', 'location_id' => $centro->id]
        );

        $q1 = Question::updateOrCreate(
            ['mission_stage_id' => $stage1->id, 'type' => 'map_location'],
            [
                'subject_id' => $geografia->id, 'skill_id' => $skillLocalizacao->id, 'grade_id' => $grade6->id,
                'statement' => 'Marque no mapa onde fica o centro de Caraguatatuba.',
                'explanation' => 'Caraguatatuba fica no litoral norte do estado de São Paulo, entre a Serra do Mar e o Oceano Atlântico.',
                'difficulty' => 'easy', 'max_score' => 1000, 'status' => 'official', 'author_id' => $author?->id,
            ]
        );
        $q1->location()->updateOrCreate([], ['latitude' => $centro->latitude, 'longitude' => $centro->longitude, 'accepted_radius_meters' => 3000]);
        $q1->hints()->firstOrCreate(['order' => 0], ['type' => 'text', 'content' => 'Fica na região conhecida como Litoral Norte de São Paulo.', 'score_penalty' => 150]);

        $q2 = Question::updateOrCreate(
            ['mission_stage_id' => $stage1->id, 'type' => 'single_choice', 'statement' => 'Caraguatatuba fica em qual região do estado de São Paulo?'],
            [
                'subject_id' => $geografia->id, 'skill_id' => $skillLocalizacao->id, 'grade_id' => $grade6->id,
                'explanation' => 'Caraguatatuba integra o Litoral Norte paulista, junto com Ubatuba, São Sebastião e Ilhabela.',
                'difficulty' => 'easy', 'max_score' => 1000, 'status' => 'official', 'author_id' => $author?->id,
            ]
        );
        $this->syncOptions($q2, [
            ['text' => 'Litoral Norte', 'is_correct' => true],
            ['text' => 'Litoral Sul', 'is_correct' => false],
            ['text' => 'Vale do Paraíba (interior)', 'is_correct' => false],
            ['text' => 'Grande São Paulo', 'is_correct' => false],
        ]);

        // ── Missão 2: A Serra do Mar ───────────────────────────────────────
        $mission2 = Mission::updateOrCreate(
            ['campaign_id' => $campaign->id, 'slug' => 'a-serra-do-mar'],
            [
                'title' => 'A Serra do Mar',
                'narrative' => 'A poucos quilômetros da praia, a Serra do Mar guarda uma das maiores áreas preservadas de Mata Atlântica do Brasil.',
                'objective' => 'Reconhecer o bioma e a importância da preservação da Serra do Mar.',
                'order' => 2,
                'difficulty' => 'medium',
                'estimated_minutes' => 10,
                'max_score' => 2000,
                'status' => 'published',
                'unlock_rule' => ['requires_mission_id' => $mission1->id],
            ]
        );
        $stage2 = MissionStage::updateOrCreate(
            ['mission_id' => $mission2->id, 'order' => 1],
            ['content' => 'Observe a paisagem coberta de vegetação densa ao redor de Caraguatatuba.', 'location_id' => $serraDoMar->id]
        );

        $q3 = Question::updateOrCreate(
            ['mission_stage_id' => $stage2->id, 'type' => 'short_answer'],
            [
                'subject_id' => $geografia->id, 'skill_id' => $skillBiomas->id, 'grade_id' => $grade6->id,
                'statement' => 'Qual bioma brasileiro cobre a Serra do Mar em Caraguatatuba?',
                'explanation' => 'A Mata Atlântica cobre a Serra do Mar — um dos biomas mais ameaçados do Brasil, com grande parte da vegetação original já desmatada em outras regiões do país.',
                'difficulty' => 'medium', 'max_score' => 1000, 'status' => 'official', 'author_id' => $author?->id,
            ]
        );
        $this->syncOptions($q3, [
            ['text' => 'Mata Atlântica', 'is_correct' => true, 'order' => 0],
            ['text' => 'mata atlantica', 'is_correct' => true, 'order' => 0],
        ]);

        $q4 = Question::updateOrCreate(
            ['mission_stage_id' => $stage2->id, 'type' => 'true_false', 'statement' => 'A Mata Atlântica é um bioma que já perdeu grande parte de sua área original no Brasil.'],
            [
                'subject_id' => $geografia->id, 'skill_id' => $skillBiomas->id, 'grade_id' => $grade6->id,
                'explanation' => 'Correto — restam poucos remanescentes preservados, e áreas como a Serra do Mar são importantes justamente por isso.',
                'difficulty' => 'easy', 'max_score' => 1000, 'status' => 'official', 'author_id' => $author?->id,
            ]
        );
        $this->syncOptions($q4, [
            ['text' => 'Verdadeiro', 'is_correct' => true],
            ['text' => 'Falso', 'is_correct' => false],
        ]);

        // ── Missão 3: Vida na cidade ────────────────────────────────────────
        $mission3 = Mission::updateOrCreate(
            ['campaign_id' => $campaign->id, 'slug' => 'vida-na-cidade'],
            [
                'title' => 'Vida na cidade',
                'narrative' => 'Caraguatatuba combina praia, serra e vida urbana. Vamos explorar como as pessoas vivem e trabalham por aqui.',
                'objective' => 'Reconhecer atividades econômicas e elementos da paisagem urbana e litorânea.',
                'order' => 3,
                'difficulty' => 'medium',
                'estimated_minutes' => 10,
                'max_score' => 2000,
                'status' => 'published',
                'unlock_rule' => ['requires_mission_id' => $mission2->id],
            ]
        );
        $stage3 = MissionStage::updateOrCreate(
            ['mission_id' => $mission3->id, 'order' => 1],
            ['content' => 'Você chegou à orla. Observe o vaivém de moradores, pescadores e turistas.', 'location_id' => $praia->id]
        );

        $q5 = Question::updateOrCreate(
            ['mission_stage_id' => $stage3->id, 'type' => 'ordering', 'statement' => 'Organize o percurso de uma expedição típica pela cidade, da serra até o mar.'],
            [
                'subject_id' => $geografia->id, 'skill_id' => $skillLocalizacao->id, 'grade_id' => $grade6->id,
                'explanation' => 'A cidade se organiza entre a Serra do Mar, a área urbana no sopé da serra e, por fim, a orla e as praias.',
                'difficulty' => 'medium', 'max_score' => 1000, 'status' => 'official', 'author_id' => $author?->id,
            ]
        );
        $this->syncOptions($q5, [
            ['text' => 'Serra do Mar', 'order' => 0],
            ['text' => 'Bairros e centro urbano', 'order' => 1],
            ['text' => 'Orla e praias', 'order' => 2],
        ]);

        $q6 = Question::updateOrCreate(
            ['mission_stage_id' => $stage3->id, 'type' => 'matching', 'statement' => 'Associe cada atividade econômica à sua principal característica em uma cidade litorânea como Caraguatatuba.'],
            [
                'subject_id' => $historia->id, 'skill_id' => $skillHistoriaLocal->id, 'grade_id' => $grade6->id,
                'explanation' => 'Turismo, pesca e comércio são atividades historicamente importantes em cidades do litoral norte paulista.',
                'difficulty' => 'medium', 'max_score' => 1000, 'status' => 'official', 'author_id' => $author?->id,
            ]
        );
        $q6->options()->delete();
        $q6->options()->createMany([
            ['text' => 'Turismo', 'side' => 'left', 'order' => 0],
            ['text' => 'Pesca', 'side' => 'left', 'order' => 1],
            ['text' => 'Cresce nas férias e feriados de verão', 'side' => 'right', 'order' => 0],
            ['text' => 'Depende diretamente do mar e das marés', 'side' => 'right', 'order' => 1],
        ]);

        $this->seedLevelsAchievements($campaign);
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

    private function seedLevelsAchievements(Campaign $campaign): void
    {
        Achievement::firstOrCreate(
            ['title' => 'Primeira Expedição'],
            ['description' => 'Concluiu sua primeira missão.', 'icon' => '🧭', 'rule_type' => 'first_mission_completed', 'experience_reward' => 50, 'status' => 'active']
        );
        Achievement::firstOrCreate(
            ['title' => 'Explorador de Caraguatatuba'],
            ['description' => 'Concluiu toda a campanha Conhecendo Caraguatatuba.', 'icon' => '🏔️', 'rule_type' => 'campaign_completed', 'rule_value' => ['campaign_id' => $campaign->id], 'experience_reward' => 200, 'status' => 'active']
        );
        Achievement::firstOrCreate(
            ['title' => 'Sem Pistas'],
            ['description' => 'Concluiu uma missão sem usar nenhuma pista.', 'icon' => '🔍', 'rule_type' => 'mission_without_hints', 'experience_reward' => 80, 'status' => 'active']
        );
        Achievement::firstOrCreate(
            ['title' => '10 Acertos'],
            ['description' => 'Acertou 10 questões.', 'icon' => '⭐', 'rule_type' => 'correct_answers_count', 'rule_value' => ['count' => 10], 'experience_reward' => 100, 'status' => 'active']
        );
    }
}
