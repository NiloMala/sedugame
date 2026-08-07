<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\CollectibleItem;
use App\Models\Level;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CRUD admin de achievements/levels/collectible_items (Sprint 4 do roadmap —
 * antes só existiam via seed/tinker, ver docs/03-api-contract.md).
 */
class GamificationCatalogAdminTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->school = School::create(['name' => 'Escola A', 'code' => 'A-001', 'city' => 'Caraguatatuba', 'state' => 'SP']);
    }

    private function userWithRole(string $slug, ?School $school = null): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', $slug)->value('id'),
            'school_id' => $school?->id,
        ]);
    }

    // ---- achievements ----------------------------------------------------

    public function test_department_admin_can_create_achievement(): void
    {
        $admin = $this->userWithRole('department_admin');

        $response = $this->actingAs($admin)->postJson('/api/admin/achievements', [
            'title' => 'Primeira Missão',
            'rule_type' => 'first_mission_completed',
            'experience_reward' => 50,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('achievements', ['title' => 'Primeira Missão']);
    }

    public function test_achievement_rejects_unknown_rule_type(): void
    {
        $admin = $this->userWithRole('department_admin');

        $response = $this->actingAs($admin)->postJson('/api/admin/achievements', [
            'title' => 'Regra Inventada',
            'rule_type' => 'algo_que_nao_existe',
        ]);

        $response->assertStatus(422);
    }

    public function test_school_admin_cannot_write_achievement(): void
    {
        $admin = $this->userWithRole('school_admin', $this->school);

        $response = $this->actingAs($admin)->postJson('/api/admin/achievements', [
            'title' => 'Tentativa', 'rule_type' => 'first_mission_completed',
        ]);

        $response->assertForbidden();
    }

    public function test_school_admin_can_read_achievements(): void
    {
        Achievement::create(['title' => 'Existente', 'rule_type' => 'streak_days']);
        $admin = $this->userWithRole('school_admin', $this->school);

        $this->actingAs($admin)->getJson('/api/admin/achievements')->assertOk();
    }

    public function test_deleting_achievement_is_soft_delete_and_preserves_student_history(): void
    {
        $admin = $this->userWithRole('department_admin');
        $achievement = Achievement::create(['title' => 'Sequência', 'rule_type' => 'streak_days']);

        $this->actingAs($admin)->deleteJson("/api/admin/achievements/{$achievement->id}")->assertNoContent();

        $this->assertSoftDeleted('achievements', ['id' => $achievement->id]);
    }

    // ---- levels ------------------------------------------------------------

    public function test_department_admin_can_create_and_update_level(): void
    {
        $admin = $this->userWithRole('department_admin');

        $create = $this->actingAs($admin)->postJson('/api/admin/levels', [
            'name' => 'Explorador Iniciante', 'minimum_experience' => 0, 'maximum_experience' => 999, 'order' => 1,
        ]);
        $create->assertCreated();
        $levelId = $create->json('data.id');

        $update = $this->actingAs($admin)->putJson("/api/admin/levels/{$levelId}", ['name' => 'Explorador']);
        $update->assertOk()->assertJsonPath('data.name', 'Explorador');
    }

    public function test_level_rejects_maximum_below_minimum_experience(): void
    {
        $admin = $this->userWithRole('department_admin');

        $response = $this->actingAs($admin)->postJson('/api/admin/levels', [
            'name' => 'Inválido', 'minimum_experience' => 1000, 'maximum_experience' => 500, 'order' => 1,
        ]);

        $response->assertStatus(422);
    }

    public function test_school_admin_cannot_write_level(): void
    {
        $admin = $this->userWithRole('school_admin', $this->school);

        $response = $this->actingAs($admin)->postJson('/api/admin/levels', [
            'name' => 'Tentativa', 'minimum_experience' => 0, 'order' => 1,
        ]);

        $response->assertForbidden();
    }

    // ---- collectible items ---------------------------------------------

    public function test_department_admin_can_create_collectible_item(): void
    {
        $admin = $this->userWithRole('department_admin');

        $response = $this->actingAs($admin)->postJson('/api/admin/collectible-items', [
            'name' => 'Brasão de Teste', 'category' => 'coat_of_arms',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('collectible_items', ['name' => 'Brasão de Teste', 'rarity' => 'common', 'status' => 'active']);
    }

    public function test_collectible_item_rejects_unknown_category(): void
    {
        $admin = $this->userWithRole('department_admin');

        $response = $this->actingAs($admin)->postJson('/api/admin/collectible-items', [
            'name' => 'Item', 'category' => 'categoria_inventada',
        ]);

        $response->assertStatus(422);
    }

    public function test_collectible_item_index_filters_by_category(): void
    {
        CollectibleItem::create(['name' => 'Binóculo', 'category' => 'avatar_accessory']);
        CollectibleItem::create(['name' => 'Brasão', 'category' => 'coat_of_arms']);
        $admin = $this->userWithRole('department_admin');

        $response = $this->actingAs($admin)->getJson('/api/admin/collectible-items?category=avatar_accessory');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Binóculo'));
        $this->assertFalse($names->contains('Brasão'));
    }

    public function test_deleting_collectible_item_is_soft_delete(): void
    {
        $admin = $this->userWithRole('department_admin');
        $item = CollectibleItem::create(['name' => 'Selo', 'category' => 'map']);

        $this->actingAs($admin)->deleteJson("/api/admin/collectible-items/{$item->id}")->assertNoContent();

        $this->assertSoftDeleted('collectible_items', ['id' => $item->id]);
    }

    public function test_school_admin_cannot_write_collectible_item(): void
    {
        $admin = $this->userWithRole('school_admin', $this->school);

        $response = $this->actingAs($admin)->postJson('/api/admin/collectible-items', [
            'name' => 'Tentativa', 'category' => 'map',
        ]);

        $response->assertForbidden();
    }
}
