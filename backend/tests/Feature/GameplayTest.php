<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\Mission;
use App\Models\Question;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\CaraguatatubaCampaignSeeder;
use Database\Seeders\GradeSeeder;
use Database\Seeders\LevelSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameplayTest extends TestCase
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

        $author = User::factory()->create(['role_id' => Role::where('slug', 'super_admin')->value('id')]);
        $this->seed(CaraguatatubaCampaignSeeder::class);

        $school = School::create(['name' => 'EMEF Teste', 'code' => 'GP-001', 'city' => 'Caraguatatuba', 'state' => 'SP']);
        $grade = Grade::where('code', '6EF2')->firstOrFail();
        $year = SchoolYear::create(['year' => 2026, 'starts_at' => '2026-02-02', 'ends_at' => '2026-12-18']);
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => '6ºA', 'grade_id' => $grade->id, 'school_year_id' => $year->id, 'shift' => 'morning']);

        $this->studentUser = User::factory()->create([
            'role_id' => Role::where('slug', 'student')->value('id'),
            'school_id' => $school->id,
        ]);
        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'registration_number' => 'GP-999',
            'school_id' => $school->id,
            'class_id' => $class->id,
        ]);
    }

    public function test_full_mission_playthrough_awards_score_xp_and_first_mission_achievement(): void
    {
        $mission = Mission::where('slug', 'chegando-a-caraguatatuba')->firstOrFail();

        // 1. lista campanhas
        $this->actingAs($this->studentUser)->getJson('/api/campaigns')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Conhecendo Caraguatatuba');

        // 2. inicia tentativa
        $start = $this->actingAs($this->studentUser)->postJson('/api/attempts', ['mission_id' => $mission->id]);
        $start->assertCreated();
        $attemptId = $start->json('data.id');

        $answeredTypes = [];

        // 3. responde todas as questões da missão
        while (true) {
            $next = $this->actingAs($this->studentUser)->getJson("/api/attempts/{$attemptId}/next-question");

            if ($next->status() === 404) {
                $this->assertSame('no_more_questions', $next->json('message'));
                break;
            }

            $next->assertOk();
            $question = $next->json('data');
            $answeredTypes[] = $question['type'];

            $payload = $this->buildAnswerPayload($question);
            $answer = $this->actingAs($this->studentUser)->postJson("/api/attempts/{$attemptId}/answers", $payload);
            $answer->assertOk();
            $this->assertIsBool($answer->json('data.is_correct'));
        }

        $this->assertContains('map_location', $answeredTypes);
        $this->assertContains('single_choice', $answeredTypes);

        // 4. já respondida não pode responder de novo
        $firstQuestion = Question::whereHas('stage', fn ($q) => $q->where('mission_id', $mission->id))->first();
        $this->actingAs($this->studentUser)->postJson("/api/attempts/{$attemptId}/answers", [
            'question_id' => $firstQuestion->id, 'time_spent_seconds' => 5, 'selected_option_id' => 1,
        ])->assertStatus(422);

        // 5. conclui
        $complete = $this->actingAs($this->studentUser)->postJson("/api/attempts/{$attemptId}/complete");
        $complete->assertOk()
            ->assertJsonStructure(['data' => ['score', 'experience_gained', 'level_up', 'achievements_unlocked']]);

        $this->assertGreaterThan(0, $complete->json('data.experience_gained'));
        $unlocked = collect($complete->json('data.achievements_unlocked'))->pluck('title');
        $this->assertTrue($unlocked->contains('Primeira Expedição'));

        // 6. não pode concluir de novo
        $this->actingAs($this->studentUser)->postJson("/api/attempts/{$attemptId}/complete")->assertStatus(422);

        // 7. passaporte reflete o progresso
        $passport = $this->actingAs($this->studentUser)->getJson('/api/passport');
        $passport->assertOk();
        $this->assertGreaterThan(0, $passport->json('data.experience'));
    }

    public function test_hint_applies_penalty_to_score(): void
    {
        $mission = Mission::where('slug', 'chegando-a-caraguatatuba')->firstOrFail();
        $attemptId = $this->actingAs($this->studentUser)
            ->postJson('/api/attempts', ['mission_id' => $mission->id])
            ->json('data.id');

        $question = $this->actingAs($this->studentUser)
            ->getJson("/api/attempts/{$attemptId}/next-question")->json('data');

        $hintId = $question['hints'][0]['id'] ?? null;
        $this->assertNotNull($hintId, 'Primeira questão do seed precisa ter uma pista cadastrada.');

        $hintResponse = $this->actingAs($this->studentUser)
            ->postJson("/api/attempts/{$attemptId}/hints/{$hintId}");
        $hintResponse->assertOk()->assertJsonStructure(['data' => ['content']]);

        $answer = $this->actingAs($this->studentUser)->postJson("/api/attempts/{$attemptId}/answers", $this->buildAnswerPayload($question) + ['hints_used' => 1]);

        $answer->assertOk();
        if ($answer->json('data.is_correct')) {
            $this->assertLessThan(1000, $answer->json('data.score'));
        }
    }

    public function test_map_location_far_answer_scores_low_and_is_not_correct(): void
    {
        $mission = Mission::where('slug', 'chegando-a-caraguatatuba')->firstOrFail();
        $attemptId = $this->actingAs($this->studentUser)
            ->postJson('/api/attempts', ['mission_id' => $mission->id])
            ->json('data.id');

        $question = $this->actingAs($this->studentUser)
            ->getJson("/api/attempts/{$attemptId}/next-question")->json('data');

        $this->assertSame('map_location', $question['type']);

        // Resposta bem longe (outro estado) — deve pontuar baixo e não contar como correta.
        $answer = $this->actingAs($this->studentUser)->postJson("/api/attempts/{$attemptId}/answers", [
            'question_id' => $question['id'],
            'time_spent_seconds' => 20,
            'latitude' => -3.7327,
            'longitude' => -38.5267,
        ]);

        $answer->assertOk();
        $this->assertFalse($answer->json('data.is_correct'));
        $this->assertGreaterThan(100000, $answer->json('data.distance_meters'));
    }

    /**
     * @param array<string, mixed> $question
     * @return array<string, mixed>
     */
    private function buildAnswerPayload(array $question): array
    {
        $base = ['question_id' => $question['id'], 'time_spent_seconds' => 10];

        return match ($question['type']) {
            'map_location' => $base + ['latitude' => -23.6203, 'longitude' => -45.4131],
            'short_answer' => $base + ['answer_text' => 'Mata Atlântica'],
            'true_false', 'single_choice' => $base + ['selected_option_id' => $question['options'][0]['id']],
            'ordering' => $base + ['ordered_option_ids' => array_column($question['options'], 'id')],
            'matching' => $base + ['matches' => $this->naiveMatches($question['options'])],
            default => $base + ['selected_option_id' => $question['options'][0]['id'] ?? null],
        };
    }

    private function naiveMatches(array $options): array
    {
        $left = array_values(array_filter($options, fn ($o) => $o['pair_side'] === 'left'));
        $right = array_values(array_filter($options, fn ($o) => $o['pair_side'] === 'right'));

        $matches = [];
        foreach ($left as $index => $option) {
            $matches[] = ['left_option_id' => $option['id'], 'right_option_id' => $right[$index]['id'] ?? null];
        }

        return $matches;
    }
}
