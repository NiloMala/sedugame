<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->schoolA = School::create(['name' => 'Escola A', 'code' => 'A-001', 'city' => 'Caraguatatuba', 'state' => 'SP']);
        $this->schoolB = School::create(['name' => 'Escola B', 'code' => 'B-001', 'city' => 'Caraguatatuba', 'state' => 'SP']);
    }

    private function userWithRole(string $slug, ?School $school = null): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', $slug)->value('id'),
            'school_id' => $school?->id,
        ]);
    }

    public function test_school_admin_cannot_create_new_school(): void
    {
        $admin = $this->userWithRole('school_admin', $this->schoolA);

        $response = $this->actingAs($admin)->postJson('/api/admin/schools', [
            'name' => 'Escola C', 'code' => 'C-001', 'city' => 'Caraguatatuba', 'state' => 'SP',
        ]);

        $response->assertForbidden();
    }

    public function test_department_admin_can_create_school(): void
    {
        $admin = $this->userWithRole('department_admin');

        $response = $this->actingAs($admin)->postJson('/api/admin/schools', [
            'name' => 'Escola C', 'code' => 'C-001', 'city' => 'Caraguatatuba', 'state' => 'SP',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('schools', ['code' => 'C-001']);
    }

    public function test_school_admin_cannot_view_another_schools_class(): void
    {
        $grade = Grade::create(['name' => '6º ano', 'code' => '6EF2', 'education_level' => 'EF2', 'order' => 6]);
        $year = SchoolYear::create(['year' => 2026, 'starts_at' => '2026-02-02', 'ends_at' => '2026-12-18']);
        $classB = SchoolClass::create([
            'school_id' => $this->schoolB->id, 'name' => '6ºA', 'grade_id' => $grade->id,
            'school_year_id' => $year->id, 'shift' => 'morning',
        ]);

        $adminA = $this->userWithRole('school_admin', $this->schoolA);

        $this->actingAs($adminA)->getJson("/api/admin/classes/{$classB->id}")->assertForbidden();
    }

    public function test_school_admin_can_create_class_in_own_school(): void
    {
        $grade = Grade::create(['name' => '6º ano', 'code' => '6EF2', 'education_level' => 'EF2', 'order' => 6]);
        $year = SchoolYear::create(['year' => 2026, 'starts_at' => '2026-02-02', 'ends_at' => '2026-12-18']);
        $admin = $this->userWithRole('school_admin', $this->schoolA);

        $response = $this->actingAs($admin)->postJson('/api/admin/classes', [
            'school_id' => $this->schoolA->id, 'name' => '7ºB', 'grade_id' => $grade->id,
            'school_year_id' => $year->id, 'shift' => 'afternoon',
        ]);

        $response->assertCreated();
    }

    public function test_school_admin_cannot_create_class_in_other_school(): void
    {
        $grade = Grade::create(['name' => '6º ano', 'code' => '6EF2', 'education_level' => 'EF2', 'order' => 6]);
        $year = SchoolYear::create(['year' => 2026, 'starts_at' => '2026-02-02', 'ends_at' => '2026-12-18']);
        $admin = $this->userWithRole('school_admin', $this->schoolA);

        $response = $this->actingAs($admin)->postJson('/api/admin/classes', [
            'school_id' => $this->schoolB->id, 'name' => '7ºB', 'grade_id' => $grade->id,
            'school_year_id' => $year->id, 'shift' => 'afternoon',
        ]);

        $response->assertForbidden();
    }

    public function test_teacher_cannot_access_admin_routes(): void
    {
        $teacher = $this->userWithRole('teacher', $this->schoolA);

        $this->actingAs($teacher)->getJson('/api/admin/schools')->assertForbidden();
    }

    public function test_school_admin_cannot_write_global_catalog(): void
    {
        $admin = $this->userWithRole('school_admin', $this->schoolA);

        $response = $this->actingAs($admin)->postJson('/api/admin/subjects', [
            'name' => 'Ciências', 'slug' => 'ciencias',
        ]);

        $response->assertForbidden();
    }

    public function test_department_admin_can_write_global_catalog(): void
    {
        $admin = $this->userWithRole('department_admin');

        $response = $this->actingAs($admin)->postJson('/api/admin/subjects', [
            'name' => 'Ciências', 'slug' => 'ciencias',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('subjects', ['slug' => 'ciencias']);
    }

    public function test_admin_can_create_student_user_with_default_password_and_login_by_ra(): void
    {
        $grade = Grade::create(['name' => '6º ano', 'code' => '6EF2', 'education_level' => 'EF2', 'order' => 6]);
        $year = SchoolYear::create(['year' => 2026, 'starts_at' => '2026-02-02', 'ends_at' => '2026-12-18']);
        $class = SchoolClass::create([
            'school_id' => $this->schoolA->id, 'name' => '6ºA', 'grade_id' => $grade->id,
            'school_year_id' => $year->id, 'shift' => 'morning',
        ]);

        $admin = $this->userWithRole('school_admin', $this->schoolA);

        $response = $this->actingAs($admin)->postJson('/api/admin/users', [
            'name' => 'Aluno Teste',
            'role' => 'student',
            'school_id' => $this->schoolA->id,
            'registration_number' => '999888',
            'class_id' => $class->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('students', ['registration_number' => '999888']);

        // O aluno recém-criado consegue logar com RA + senha padrão da rede.
        $login = $this->postJson('/api/login', [
            'login' => '999888',
            'password' => config('auth.student_default_password'),
        ], ['Referer' => 'http://localhost:3000']);

        $login->assertNoContent();
    }

    public function test_school_admin_cannot_create_department_admin(): void
    {
        $admin = $this->userWithRole('school_admin', $this->schoolA);

        $response = $this->actingAs($admin)->postJson('/api/admin/users', [
            'name' => 'Tentativa de escalada', 'email' => 'x@escola.edu.br', 'role' => 'department_admin',
        ]);

        $response->assertForbidden();
    }

    // ---- vínculo professor × turma × disciplina (teacher_classes) --------

    private function classAndTeacherInSchoolA(): array
    {
        $grade = Grade::create(['name' => '6º ano', 'code' => '6EF2', 'education_level' => 'EF2', 'order' => 6]);
        $year = SchoolYear::create(['year' => 2026, 'starts_at' => '2026-02-02', 'ends_at' => '2026-12-18']);
        $class = SchoolClass::create([
            'school_id' => $this->schoolA->id, 'name' => '6ºA', 'grade_id' => $grade->id,
            'school_year_id' => $year->id, 'shift' => 'morning',
        ]);
        $subject = Subject::create(['name' => 'Geografia', 'slug' => 'geografia']);
        $teacherUser = $this->userWithRole('teacher', $this->schoolA);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'school_id' => $this->schoolA->id]);

        return [$class, $teacher, $subject];
    }

    public function test_school_admin_can_assign_teacher_to_class_in_own_school(): void
    {
        [$class, $teacher, $subject] = $this->classAndTeacherInSchoolA();
        $admin = $this->userWithRole('school_admin', $this->schoolA);

        $response = $this->actingAs($admin)->postJson("/api/admin/classes/{$class->id}/teachers", [
            'teacher_id' => $teacher->id, 'subject_id' => $subject->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('teacher_classes', [
            'teacher_id' => $teacher->id, 'class_id' => $class->id, 'subject_id' => $subject->id,
        ]);
    }

    public function test_assigning_same_teacher_class_subject_twice_is_idempotent(): void
    {
        [$class, $teacher, $subject] = $this->classAndTeacherInSchoolA();
        $admin = $this->userWithRole('school_admin', $this->schoolA);

        $payload = ['teacher_id' => $teacher->id, 'subject_id' => $subject->id];
        $this->actingAs($admin)->postJson("/api/admin/classes/{$class->id}/teachers", $payload)->assertCreated();
        $this->actingAs($admin)->postJson("/api/admin/classes/{$class->id}/teachers", $payload)->assertCreated();

        $this->assertSame(1, DB::table('teacher_classes')
            ->where(['teacher_id' => $teacher->id, 'class_id' => $class->id, 'subject_id' => $subject->id])
            ->count());
    }

    public function test_cannot_assign_teacher_from_another_school_to_class(): void
    {
        [$class, , $subject] = $this->classAndTeacherInSchoolA();
        $teacherUserB = $this->userWithRole('teacher', $this->schoolB);
        $teacherB = Teacher::create(['user_id' => $teacherUserB->id, 'school_id' => $this->schoolB->id]);
        $admin = $this->userWithRole('department_admin');

        $response = $this->actingAs($admin)->postJson("/api/admin/classes/{$class->id}/teachers", [
            'teacher_id' => $teacherB->id, 'subject_id' => $subject->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_school_admin_cannot_assign_teacher_in_other_school(): void
    {
        [$class, $teacher, $subject] = $this->classAndTeacherInSchoolA();
        $adminB = $this->userWithRole('school_admin', $this->schoolB);

        $response = $this->actingAs($adminB)->postJson("/api/admin/classes/{$class->id}/teachers", [
            'teacher_id' => $teacher->id, 'subject_id' => $subject->id,
        ]);

        $response->assertForbidden();
    }

    public function test_school_admin_can_remove_teacher_from_class(): void
    {
        [$class, $teacher, $subject] = $this->classAndTeacherInSchoolA();
        $admin = $this->userWithRole('school_admin', $this->schoolA);
        $class->teachers()->attach($teacher->id, ['subject_id' => $subject->id]);

        $response = $this->actingAs($admin)->deleteJson("/api/admin/classes/{$class->id}/teachers/{$teacher->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('teacher_classes', ['teacher_id' => $teacher->id, 'class_id' => $class->id]);
    }

    // ---- reset de senha de aluno (regressão de autorização — ver StudentPasswordController) --

    public function test_school_admin_cannot_reset_password_of_student_from_another_school(): void
    {
        $grade = Grade::create(['name' => '6º ano', 'code' => '6EF2', 'education_level' => 'EF2', 'order' => 6]);
        $year = SchoolYear::create(['year' => 2026, 'starts_at' => '2026-02-02', 'ends_at' => '2026-12-18']);
        $classB = SchoolClass::create([
            'school_id' => $this->schoolB->id, 'name' => '6ºA', 'grade_id' => $grade->id,
            'school_year_id' => $year->id, 'shift' => 'morning',
        ]);
        $studentUserB = $this->userWithRole('student', $this->schoolB);
        $studentB = \App\Models\Student::create([
            'user_id' => $studentUserB->id, 'registration_number' => 'B-999',
            'school_id' => $this->schoolB->id, 'class_id' => $classB->id,
        ]);
        $adminA = $this->userWithRole('school_admin', $this->schoolA);

        $response = $this->actingAs($adminA)->postJson("/api/admin/students/{$studentB->id}/reset-password");

        $response->assertForbidden();
    }

    public function test_school_admin_can_reset_password_of_student_in_own_school(): void
    {
        $grade = Grade::create(['name' => '6º ano', 'code' => '6EF2', 'education_level' => 'EF2', 'order' => 6]);
        $year = SchoolYear::create(['year' => 2026, 'starts_at' => '2026-02-02', 'ends_at' => '2026-12-18']);
        $classA = SchoolClass::create([
            'school_id' => $this->schoolA->id, 'name' => '6ºA', 'grade_id' => $grade->id,
            'school_year_id' => $year->id, 'shift' => 'morning',
        ]);
        $studentUserA = $this->userWithRole('student', $this->schoolA);
        $studentA = \App\Models\Student::create([
            'user_id' => $studentUserA->id, 'registration_number' => 'A-999',
            'school_id' => $this->schoolA->id, 'class_id' => $classA->id,
        ]);
        $adminA = $this->userWithRole('school_admin', $this->schoolA);

        $response = $this->actingAs($adminA)->postJson("/api/admin/students/{$studentA->id}/reset-password");

        $response->assertOk();
    }
}
