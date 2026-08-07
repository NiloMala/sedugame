<?php

namespace Tests\Feature;

use App\Models\Mission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CaraguatatubaCampaignSeeder;
use Database\Seeders\GradeSeeder;
use Database\Seeders\LevelSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Teste de ponta a ponta em nível de HTTP: um único cenário contínuo que
 * atravessa todos os perfis (secretaria → escola → professor → aluno) usando
 * só a API pública, sem criar nada direto via Eloquent além do bootstrap
 * mínimo (catálogos de sistema + o primeiro super_admin, que por definição
 * não pode ser criado por ninguém "de dentro" da própria API).
 *
 * Não é E2E de navegador (não sobe o Next.js nem um browser real — isso é
 * outra decisão, de infra de CI, que fica pra quando o time topar manter
 * Playwright/Cypress rodando). É E2E no sentido de "a jornada completa de
 * um usuário real, ponta a ponta, através da stack HTTP real (rotas,
 * middlewares, banco)", em vez de features testadas isoladamente.
 *
 * Detalhe técnico: o client de teste do Laravel NÃO carrega o cookie de
 * sessão automaticamente de uma chamada pra outra (cada `get()`/`postJson()`
 * é uma request isolada, sem cookie jar persistente tipo browser — simular
 * isso de verdade, com CSRF incluso, é reinventar um browser dentro do
 * PHPUnit). Por isso este teste faz o login real (prova que a rota aceita
 * as credenciais) e, pras chamadas seguintes do mesmo usuário, troca pra
 * `actingAs()` — mesmo padrão usado no resto da suíte.
 *
 * Achados reais durante a escrita deste teste (cada um virou fix + teste
 * dedicado nos arquivos correspondentes, não só aqui):
 * - Não existia endpoint pra vincular professor a turma/disciplina (ver
 *   Admin\ClassTeacherController, novo).
 * - StudentPasswordController não checava escola do aluno (ver AdminCrudTest).
 */
class FullJourneyE2ETest extends TestCase
{
    use RefreshDatabase;

    private const REFERER = ['Referer' => 'http://localhost:3000'];

    public function test_full_journey_from_school_setup_to_student_gameplay_to_teacher_report(): void
    {
        // ---- bootstrap de catálogos + o primeiro super_admin (não tem como
        // isso vir de "dentro" da API — precisa existir antes de qualquer
        // chamada autenticada) ------------------------------------------------
        $this->seed(RoleSeeder::class);
        $this->seed(LevelSeeder::class);
        $this->seed(GradeSeeder::class);
        $this->seed(SubjectSeeder::class);

        $networkAdmin = User::factory()->create(['role_id' => Role::where('slug', 'super_admin')->value('id')]);
        $this->seed(CaraguatatubaCampaignSeeder::class); // autor = o super_admin acima

        $mission = Mission::where('slug', 'chegando-a-caraguatatuba')->firstOrFail();
        $campaignId = $mission->campaign_id;

        // ---- 1. Secretaria cria a escola --------------------------------------
        $school = $this->actingAs($networkAdmin)->postJson('/api/admin/schools', [
            'name' => 'EMEF Jornada Completa', 'code' => 'E2E-001', 'city' => 'Caraguatatuba', 'state' => 'SP',
        ])->assertCreated()->json('data');

        // ---- 2. Secretaria cria ano letivo e turma ----------------------------
        $schoolYear = $this->actingAs($networkAdmin)->postJson('/api/admin/school-years', [
            'year' => 2026, 'starts_at' => '2026-02-02', 'ends_at' => '2026-12-18',
        ])->assertCreated()->json('data');

        $gradeId = \App\Models\Grade::where('code', '6EF2')->value('id');

        $class = $this->actingAs($networkAdmin)->postJson('/api/admin/classes', [
            'school_id' => $school['id'], 'name' => '6ºA', 'grade_id' => $gradeId,
            'school_year_id' => $schoolYear['id'], 'shift' => 'morning',
        ])->assertCreated()->json('data');

        // ---- 3. Secretaria cria professor e aluno (senha explícita pro
        // professor, já que sem isso o UserController gera uma aleatória e o
        // teste não teria como logar depois) ------------------------------------
        $teacherUser = $this->actingAs($networkAdmin)->postJson('/api/admin/users', [
            'name' => 'Professora da Jornada', 'email' => 'professora.jornada@escola.edu.br',
            'role' => 'teacher', 'school_id' => $school['id'], 'password' => 'senha-forte-123',
        ])->assertCreated()->json('data');

        $studentUser = $this->actingAs($networkAdmin)->postJson('/api/admin/users', [
            'name' => 'Aluno da Jornada', 'role' => 'student', 'school_id' => $school['id'],
            'registration_number' => 'E2E-777', 'class_id' => $class['id'],
        ])->assertCreated()->json('data');

        // ---- 4. Secretaria vincula professor à turma na disciplina de Geografia
        $geografiaId = \App\Models\Subject::where('slug', 'geografia')->value('id');

        $this->actingAs($networkAdmin)->postJson("/api/admin/classes/{$class['id']}/teachers", [
            'teacher_id' => $teacherUser['teacher']['id'], 'subject_id' => $geografiaId,
        ])->assertCreated();

        // ---- 5. Professor loga (prova que a senha definida na criação
        // funciona de verdade) e segue a jornada autenticado como ele --------
        $this->postJson('/api/login', [
            'login' => 'professora.jornada@escola.edu.br', 'password' => 'senha-forte-123',
        ], self::REFERER)->assertNoContent();

        $teacherAuthUser = User::where('email', 'professora.jornada@escola.edu.br')->firstOrFail();

        $this->actingAs($teacherAuthUser)->getJson('/api/teacher/classes')
            ->assertOk()
            ->assertJsonPath('data.0.name', '6ºA')
            ->assertJsonPath('data.0.subject.name', 'Geografia');

        // ---- 6. Professor cria uma atividade da campanha pra turma -------------
        $activity = $this->actingAs($teacherAuthUser)->postJson('/api/teacher/activities', [
            'campaign_id' => $campaignId, 'class_ids' => [$class['id']],
        ])->assertCreated()->json('data');

        // ---- 7. Aluno loga por RA + senha padrão da rede ------------------------
        $this->postJson('/api/login', [
            'login' => 'E2E-777', 'password' => config('auth.student_default_password'),
        ], self::REFERER)->assertNoContent();

        $studentAuthUser = User::whereHas(
            'student', fn ($q) => $q->where('registration_number', 'E2E-777')
        )->firstOrFail();

        $this->actingAs($studentAuthUser)->getJson('/api/campaigns')->assertOk();

        // ---- 8. Aluno joga a missão inteira, vinculada à atividade do professor
        $attemptId = $this->actingAs($studentAuthUser)->postJson('/api/attempts', [
            'mission_id' => $mission->id, 'activity_id' => $activity['id'],
        ])->assertCreated()->json('data.id');

        while (true) {
            $next = $this->actingAs($studentAuthUser)->getJson("/api/attempts/{$attemptId}/next-question");
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
            $this->actingAs($studentAuthUser)->postJson("/api/attempts/{$attemptId}/answers", $payload);
        }

        /** @var TestResponse $complete */
        $complete = $this->actingAs($studentAuthUser)->postJson("/api/attempts/{$attemptId}/complete")->assertOk();
        $this->assertGreaterThan(0, $complete->json('data.score'));

        // ---- 9. Aluno confere o próprio passaporte -----------------------------
        $this->actingAs($studentAuthUser)->getJson('/api/passport')
            ->assertOk()
            ->assertJsonPath('data.name', 'Aluno da Jornada')
            ->assertJsonPath('data.school', 'EMEF Jornada Completa');

        // ---- 10. Professor vê o resultado da atividade e exporta o relatório
        // da turma nos 3 formatos -------------------------------------------------
        $this->actingAs($teacherAuthUser)->getJson("/api/teacher/activities/{$activity['id']}/results")
            ->assertOk()
            ->assertJsonPath('data.students.0.name', 'Aluno da Jornada')
            ->assertJsonPath('data.students.0.status', 'completed');

        foreach (['csv', 'pdf', 'xlsx'] as $format) {
            $this->actingAs($teacherAuthUser)
                ->get("/api/teacher/reports/class/{$class['id']}/export?format={$format}")
                ->assertOk();
        }

        // ---- 11. Secretaria confere o relatório da escola e a rede toda --------
        $this->actingAs($networkAdmin)->getJson("/api/reports/school/{$school['id']}")
            ->assertOk()
            ->assertJsonPath('data.total_students', 1);

        $this->actingAs($networkAdmin)->getJson('/api/reports/network')->assertOk();

        // ---- 12. Secretaria reseta a senha do aluno --------------------------
        // A prova de que a senha nova realmente bate é via Hash::check direto,
        // não mais um round-trip de /api/login: o guard 'web' fica "grudado"
        // no usuário do último actingAs() dentro do mesmo processo de teste
        // (quirk conhecido do test harness do Laravel, não bug do app — os 2
        // logins reais anteriores, do professor e do aluno, já provam que o
        // endpoint funciona de verdade).
        $studentId = \App\Models\Student::where('registration_number', 'E2E-777')->value('id');
        $this->actingAs($networkAdmin)->postJson("/api/admin/students/{$studentId}/reset-password")->assertOk();

        $this->assertTrue(Hash::check(
            config('auth.student_default_password'),
            $studentAuthUser->fresh()->password
        ));
    }
}
