<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Campaign;
use App\Models\Grade;
use App\Models\Mission;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\CaraguatatubaCampaignSeeder;
use Database\Seeders\GradeSeeder;
use Database\Seeders\LevelSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherAndReportsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private SchoolClass $class;

    private User $teacherUser;

    private Teacher $teacher;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(LevelSeeder::class);
        $this->seed(GradeSeeder::class);
        $this->seed(SubjectSeeder::class);

        $admin = User::factory()->create(['role_id' => Role::where('slug', 'super_admin')->value('id')]);
        $this->seed(CaraguatatubaCampaignSeeder::class);

        $this->school = School::create(['name' => 'EMEF Teste', 'code' => 'TR-001', 'city' => 'Caraguatatuba', 'state' => 'SP']);
        $grade = Grade::where('code', '6EF2')->firstOrFail();
        $year = SchoolYear::create(['year' => 2026, 'starts_at' => '2026-02-02', 'ends_at' => '2026-12-18']);
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => '6ºA', 'grade_id' => $grade->id, 'school_year_id' => $year->id, 'shift' => 'morning']);

        $this->teacherUser = User::factory()->create(['role_id' => Role::where('slug', 'teacher')->value('id'), 'school_id' => $this->school->id]);
        $this->teacher = Teacher::create(['user_id' => $this->teacherUser->id, 'school_id' => $this->school->id]);
        $geografia = Subject::where('slug', 'geografia')->firstOrFail();
        $this->teacher->classes()->attach($this->class->id, ['subject_id' => $geografia->id]);

        $studentUser = User::factory()->create(['role_id' => Role::where('slug', 'student')->value('id'), 'school_id' => $this->school->id]);
        $this->student = Student::create(['user_id' => $studentUser->id, 'registration_number' => 'TR-777', 'school_id' => $this->school->id, 'class_id' => $this->class->id]);

        // uma tentativa concluída pra dar dado real aos relatórios
        $mission = Mission::where('slug', 'chegando-a-caraguatatuba')->firstOrFail();
        Attempt::create([
            'student_id' => $this->student->id, 'campaign_id' => $mission->campaign_id, 'mission_id' => $mission->id,
            'started_at' => now(), 'completed_at' => now(), 'status' => 'completed', 'score' => 1800, 'experience' => 60, 'time_spent_seconds' => 120,
        ]);
    }

    public function test_teacher_sees_own_classes_with_subject(): void
    {
        $response = $this->actingAs($this->teacherUser)->getJson('/api/teacher/classes');

        $response->assertOk()
            ->assertJsonPath('data.0.name', '6ºA')
            ->assertJsonPath('data.0.subject.name', 'Geografia')
            ->assertJsonPath('data.0.students_count', 1);
    }

    public function test_teacher_cannot_see_students_of_class_not_theirs(): void
    {
        $otherClass = SchoolClass::create([
            'school_id' => $this->school->id, 'name' => '6ºB',
            'grade_id' => Grade::where('code', '6EF2')->value('id'),
            'school_year_id' => SchoolYear::first()->id, 'shift' => 'afternoon',
        ]);

        $this->actingAs($this->teacherUser)
            ->getJson("/api/teacher/classes/{$otherClass->id}/students")
            ->assertForbidden();
    }

    public function test_teacher_can_create_activity_for_own_class(): void
    {
        $campaign = Campaign::first();

        $response = $this->actingAs($this->teacherUser)->postJson('/api/teacher/activities', [
            'campaign_id' => $campaign->id,
            'class_ids' => [$this->class->id],
            'attempt_limit' => 2,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('activities', ['teacher_id' => $this->teacher->id, 'campaign_id' => $campaign->id]);
    }

    public function test_teacher_cannot_create_activity_for_foreign_class(): void
    {
        $otherClass = SchoolClass::create([
            'school_id' => $this->school->id, 'name' => '6ºC',
            'grade_id' => Grade::where('code', '6EF2')->value('id'),
            'school_year_id' => SchoolYear::first()->id, 'shift' => 'afternoon',
        ]);

        $response = $this->actingAs($this->teacherUser)->postJson('/api/teacher/activities', [
            'campaign_id' => Campaign::first()->id,
            'class_ids' => [$otherClass->id],
        ]);

        $response->assertForbidden();
    }

    public function test_teacher_class_report_reflects_attempt(): void
    {
        $response = $this->actingAs($this->teacherUser)->getJson("/api/teacher/reports/class/{$this->class->id}");

        $response->assertOk()
            ->assertJsonPath('data.attempts_count', 1)
            ->assertJsonPath('data.average_score', 1800);
    }

    public function test_teacher_can_export_class_report_as_csv(): void
    {
        $response = $this->actingAs($this->teacherUser)->get("/api/teacher/reports/class/{$this->class->id}/export?format=csv");

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('TR-777', $response->streamedContent());
    }

    public function test_teacher_can_export_class_report_as_pdf(): void
    {
        $response = $this->actingAs($this->teacherUser)->get("/api/teacher/reports/class/{$this->class->id}/export?format=pdf");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        // %PDF- é o magic number de todo arquivo PDF válido — confirma que o
        // dompdf realmente renderizou algo, não só devolveu o content-type certo.
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_teacher_can_export_class_report_as_xlsx(): void
    {
        $response = $this->actingAs($this->teacherUser)->get("/api/teacher/reports/class/{$this->class->id}/export?format=xlsx");

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml',
            $response->headers->get('Content-Type')
        );
    }

    public function test_class_export_rejects_unknown_format(): void
    {
        $response = $this->actingAs($this->teacherUser)->get("/api/teacher/reports/class/{$this->class->id}/export?format=doc");

        $response->assertStatus(422);
    }

    public function test_school_admin_sees_own_school_report(): void
    {
        $admin = User::factory()->create(['role_id' => Role::where('slug', 'school_admin')->value('id'), 'school_id' => $this->school->id]);

        $response = $this->actingAs($admin)->getJson("/api/reports/school/{$this->school->id}");

        $response->assertOk()
            ->assertJsonPath('data.total_students', 1)
            ->assertJsonPath('data.total_teachers', 1);
    }

    public function test_school_admin_cannot_see_other_school_report(): void
    {
        $otherSchool = School::create(['name' => 'Outra Escola', 'code' => 'TR-002', 'city' => 'Caraguatatuba', 'state' => 'SP']);
        $admin = User::factory()->create(['role_id' => Role::where('slug', 'school_admin')->value('id'), 'school_id' => $this->school->id]);

        $this->actingAs($admin)->getJson("/api/reports/school/{$otherSchool->id}")->assertForbidden();
    }

    public function test_only_network_admin_sees_network_report(): void
    {
        $schoolAdmin = User::factory()->create(['role_id' => Role::where('slug', 'school_admin')->value('id'), 'school_id' => $this->school->id]);
        $this->actingAs($schoolAdmin)->getJson('/api/reports/network')->assertForbidden();

        $deptAdmin = User::factory()->create(['role_id' => Role::where('slug', 'department_admin')->value('id')]);
        $this->actingAs($deptAdmin)->getJson('/api/reports/network')
            ->assertOk()
            ->assertJsonPath('data.total_students', 1);
    }
}
