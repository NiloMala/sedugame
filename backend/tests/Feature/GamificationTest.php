<?php

namespace Tests\Feature;

use App\Models\CollectibleItem;
use App\Models\Grade;
use App\Models\Mission;
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

class GamificationTest extends TestCase
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

        $school = School::create(['name' => 'EMEF Teste', 'code' => 'GM-001', 'city' => 'Caraguatatuba', 'state' => 'SP']);
        $grade = Grade::where('code', '6EF2')->firstOrFail();
        $year = SchoolYear::create(['year' => 2026, 'starts_at' => '2026-02-02', 'ends_at' => '2026-12-18']);
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => '6ºA', 'grade_id' => $grade->id, 'school_year_id' => $year->id, 'shift' => 'morning']);

        $this->studentUser = User::factory()->create(['role_id' => Role::where('slug', 'student')->value('id'), 'school_id' => $school->id]);
        $this->student = Student::create(['user_id' => $this->studentUser->id, 'registration_number' => 'GM-999', 'school_id' => $school->id, 'class_id' => $class->id]);
    }

    public function test_completing_mission_grants_its_reward_collectible(): void
    {
        $complete = $this->completeAndGetResponse('chegando-a-caraguatatuba');

        $names = collect($complete->json('data.collectibles_unlocked'))->pluck('name');
        $this->assertTrue($names->contains('Brasão de Caraguatatuba'));
        $this->assertDatabaseHas('student_collectibles', [
            'student_id' => $this->student->id,
            'collectible_item_id' => CollectibleItem::where('name', 'Brasão de Caraguatatuba')->value('id'),
        ]);
    }

    public function test_mission_without_hints_grants_achievement_and_linked_accessory(): void
    {
        $this->completeAndGetResponse('chegando-a-caraguatatuba');

        $binoculo = CollectibleItem::where('name', 'Binóculo Dourado')->firstOrFail();
        $this->assertDatabaseHas('student_collectibles', [
            'student_id' => $this->student->id,
            'collectible_item_id' => $binoculo->id,
        ]);
    }

    public function test_collections_endpoint_reflects_unlocked_state(): void
    {
        $this->completeAndGetResponse('chegando-a-caraguatatuba');

        $response = $this->actingAs($this->studentUser)->getJson('/api/collections');
        $response->assertOk();

        $items = collect($response->json('data'));
        $brasao = $items->firstWhere('name', 'Brasão de Caraguatatuba');
        $selo = $items->firstWhere('name', 'Selo da Mata Atlântica');

        $this->assertTrue($brasao['unlocked']);
        $this->assertFalse($selo['unlocked']); // missão 2 ainda não foi jogada
    }

    public function test_avatar_defaults_and_can_be_updated(): void
    {
        $show = $this->actingAs($this->studentUser)->getJson('/api/avatar');
        $show->assertOk()->assertJsonPath('data.avatar_base', 'compass');
        $this->assertCount(6, $show->json('data.available_bases'));

        $update = $this->actingAs($this->studentUser)->putJson('/api/avatar', ['avatar_base' => 'mountain']);
        $update->assertOk()->assertJsonPath('data.avatar_base', 'mountain');
    }

    public function test_avatar_rejects_invalid_preset(): void
    {
        $this->actingAs($this->studentUser)->putJson('/api/avatar', ['avatar_base' => 'not-a-real-preset'])
            ->assertStatus(422);
    }

    public function test_avatar_cannot_equip_accessory_not_owned(): void
    {
        $binoculo = CollectibleItem::where('name', 'Binóculo Dourado')->firstOrFail();

        $this->actingAs($this->studentUser)->putJson('/api/avatar', [
            'avatar_base' => 'compass', 'equipped_accessory_id' => $binoculo->id,
        ])->assertStatus(422);
    }

    public function test_avatar_can_equip_accessory_once_unlocked(): void
    {
        $this->completeAndGetResponse('chegando-a-caraguatatuba');
        $binoculo = CollectibleItem::where('name', 'Binóculo Dourado')->firstOrFail();

        $response = $this->actingAs($this->studentUser)->putJson('/api/avatar', [
            'avatar_base' => 'compass', 'equipped_accessory_id' => $binoculo->id,
        ]);

        $response->assertOk()->assertJsonPath('data.equipped_accessory.name', 'Binóculo Dourado');
    }

    public function test_completing_attempt_registers_activity_and_streak(): void
    {
        $this->student->update(['last_activity_date' => now()->subDay()->toDateString(), 'streak_days' => 3]);

        $this->completeAndGetResponse('chegando-a-caraguatatuba');

        $this->student->refresh();
        $this->assertSame(4, $this->student->streak_days);
        $this->assertSame(now()->toDateString(), $this->student->last_activity_date->toDateString());
    }

    public function test_streak_achievement_unlocks_at_seven_days(): void
    {
        $this->student->update(['last_activity_date' => now()->subDay()->toDateString(), 'streak_days' => 6]);

        $complete = $this->completeAndGetResponse('chegando-a-caraguatatuba');

        $titles = collect($complete->json('data.achievements_unlocked'))->pluck('title');
        $this->assertTrue($titles->contains('Sequência de 7 Dias'));
    }

    private function completeAndGetResponse(string $missionSlug)
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
                'short_answer' => ['answer_text' => 'Mata Atlântica'],
                'ordering' => ['ordered_option_ids' => array_column($question['options'], 'id')],
                'matching' => ['matches' => []],
                default => ['selected_option_id' => $question['options'][0]['id']],
            };
            $this->actingAs($this->studentUser)->postJson("/api/attempts/{$attemptId}/answers", $payload);
        }

        return $this->actingAs($this->studentUser)->postJson("/api/attempts/{$attemptId}/complete");
    }
}
