<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Grade;
use App\Models\Mission;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\CaraguatatubaCampaignSeeder;
use Database\Seeders\DemoCampaignsExpansionSeeder;
use Database\Seeders\GradeSeeder;
use Database\Seeders\LevelSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Não testa se o CONTEÚDO está certo (isso é revisão pedagógica, fora do
 * escopo de um teste automatizado) — testa que o seeder produz uma estrutura
 * válida e que as 2 campanhas novas são jogáveis ponta a ponta sem erro,
 * do mesmo jeito que GamificationTest já faz pra campanha original.
 */
class DemoCampaignsExpansionSeederTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;

    private User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(LevelSeeder::class);
        $this->seed(GradeSeeder::class);
        $this->seed(SubjectSeeder::class);

        User::factory()->create(['role_id' => Role::where('slug', 'super_admin')->value('id')]);
        $this->seed(CaraguatatubaCampaignSeeder::class);
        $this->seed(DemoCampaignsExpansionSeeder::class);

        $school = School::create(['name' => 'EMEF Teste', 'code' => 'DCE-001', 'city' => 'Caraguatatuba', 'state' => 'SP']);
        $grade = Grade::where('code', '7EF2')->firstOrFail();
        $year = SchoolYear::create(['year' => 2026, 'starts_at' => '2026-02-02', 'ends_at' => '2026-12-18']);
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => '7ºA', 'grade_id' => $grade->id, 'school_year_id' => $year->id, 'shift' => 'morning']);

        $this->studentUser = User::factory()->create(['role_id' => Role::where('slug', 'student')->value('id'), 'school_id' => $school->id]);
        $this->student = Student::create(['user_id' => $this->studentUser->id, 'registration_number' => 'DCE-999', 'school_id' => $school->id, 'class_id' => $class->id]);
    }

    public function test_seeds_three_campaigns_total(): void
    {
        $this->assertSame(3, Campaign::where('status', 'published')->count());
        $this->assertDatabaseHas('campaigns', ['slug' => 'vizinhos-do-litoral-norte']);
        $this->assertDatabaseHas('campaigns', ['slug' => 'mata-atlantica-em-foco']);
    }

    public function test_litoral_norte_first_mission_is_fully_playable(): void
    {
        $response = $this->completeAndGetResponse('os-quatro-municipios');

        $response->assertOk();
        $this->assertGreaterThan(0, $response->json('data.score'));
    }

    public function test_litoral_norte_second_mission_unlocks_and_is_playable(): void
    {
        $this->completeAndGetResponse('os-quatro-municipios');

        $response = $this->completeAndGetResponse('relevo-e-clima-da-regiao');

        $response->assertOk();
        $this->assertGreaterThan(0, $response->json('data.score'));
    }

    public function test_mata_atlantica_mission_is_fully_playable(): void
    {
        $response = $this->completeAndGetResponse('um-bioma-ameacado');

        $response->assertOk();
        $this->assertGreaterThan(0, $response->json('data.score'));
    }

    public function test_matching_question_pairs_are_internally_consistent(): void
    {
        // As duas campanhas novas têm 1 questão "matching" cada — confere que
        // sempre têm o mesmo número de itens dos dois lados (senão não dá
        // pra formar pares, e o gameplay quebra silenciosamente).
        $matchingQuestions = \App\Models\Question::whereIn(
            'mission_stage_id',
            \App\Models\MissionStage::whereIn(
                'mission_id',
                Mission::whereIn('slug', ['relevo-e-clima-da-regiao'])->pluck('id')
            )->pluck('id')
        )->where('type', 'matching')->get();

        $this->assertNotEmpty($matchingQuestions);

        foreach ($matchingQuestions as $question) {
            $left = $question->options()->where('side', 'left')->count();
            $right = $question->options()->where('side', 'right')->count();
            $this->assertSame($left, $right, "Questão matching #{$question->id} tem lados desbalanceados.");
        }
    }

    private function completeAndGetResponse(string $missionSlug): TestResponse
    {
        $mission = Mission::where('slug', $missionSlug)->firstOrFail();
        $attemptId = $this->actingAs($this->studentUser)->postJson('/api/attempts', ['mission_id' => $mission->id])->json('data.id');

        while (true) {
            $next = $this->actingAs($this->studentUser)->getJson("/api/attempts/{$attemptId}/next-question");
            if ($next->status() === 404) {
                break;
            }
            $question = $next->json('data');
            $payload = ['question_id' => $question['id'], 'time_spent_seconds' => 5];
            $payload += match ($question['type']) {
                'map_location' => ['latitude' => -23.6203, 'longitude' => -45.4131],
                // Resposta correta não vem exposta em next-question (nem deveria —
                // seria vazar o gabarito pro aluno); busca direto no banco.
                'short_answer' => ['answer_text' => \App\Models\Question::find($question['id'])
                    ->options()->where('is_correct', true)->first()->text],
                'ordering' => ['ordered_option_ids' => array_column($question['options'], 'id')],
                'matching' => ['matches' => []],
                default => ['selected_option_id' => $question['options'][0]['id']],
            };
            $this->actingAs($this->studentUser)->postJson("/api/attempts/{$attemptId}/answers", $payload);
        }

        return $this->actingAs($this->studentUser)->postJson("/api/attempts/{$attemptId}/complete");
    }
}
