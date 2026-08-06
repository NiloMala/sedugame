<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Grade;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_staff_can_login_with_email(): void
    {
        $user = User::factory()->create([
            'email' => 'professora@escola.edu.br',
            'password' => 'senha-forte-123',
            'role_id' => Role::where('slug', 'teacher')->value('id'),
        ]);

        // Referer simula o frontend Next.js batendo de um domínio stateful configurado
        // (SANCTUM_STATEFUL_DOMAINS) — sem isso, o Sanctum trata como request sem sessão.
        $response = $this->postJson('/api/login', [
            'login' => 'professora@escola.edu.br',
            'password' => 'senha-forte-123',
        ], ['Referer' => 'http://localhost:3000']);

        $response->assertNoContent();
        $this->assertAuthenticatedAs($user);
    }

    public function test_student_can_login_with_registration_number(): void
    {
        $school = School::create([
            'name' => 'EMEF Teste', 'code' => 'T-001', 'city' => 'Caraguatatuba', 'state' => 'SP',
        ]);
        $grade = Grade::create(['name' => '6º ano', 'code' => '6EF2', 'education_level' => 'EF2', 'order' => 6]);
        $schoolYear = SchoolYear::create(['year' => 2026, 'starts_at' => '2026-02-02', 'ends_at' => '2026-12-18']);
        $class = SchoolClass::create([
            'school_id' => $school->id, 'name' => '6ºA', 'grade_id' => $grade->id,
            'school_year_id' => $schoolYear->id, 'shift' => 'morning',
        ]);

        $user = User::factory()->create([
            'email' => 'ra123456@ra.expedicaodosaber.local',
            'password' => 'caragua@2026',
            'role_id' => Role::where('slug', 'student')->value('id'),
            'school_id' => $school->id,
        ]);

        Student::create([
            'user_id' => $user->id,
            'registration_number' => '123456',
            'school_id' => $school->id,
            'class_id' => $class->id,
        ]);

        $response = $this->postJson('/api/login', [
            'login' => '123456',
            'password' => 'caragua@2026',
        ], ['Referer' => 'http://localhost:3000']);

        $response->assertNoContent();
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'aluno@escola.edu.br',
            'password' => 'senha-certa',
            'role_id' => Role::where('slug', 'student')->value('id'),
        ]);

        $response = $this->postJson('/api/login', [
            'login' => 'aluno@escola.edu.br',
            'password' => 'senha-errada',
        ]);

        $response->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_me_returns_role_and_school(): void
    {
        $school = School::create([
            'name' => 'EMEF Teste', 'code' => 'T-002', 'city' => 'Caraguatatuba', 'state' => 'SP',
        ]);

        $user = User::factory()->create([
            'role_id' => Role::where('slug', 'super_admin')->value('id'),
            'school_id' => $school->id,
        ]);

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertOk()
            ->assertJsonPath('data.role', 'super_admin')
            ->assertJsonPath('data.school.name', 'EMEF Teste');
    }
}
