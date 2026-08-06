<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Admin\Concerns\ScopesToSchool;
use App\Http\Controllers\Concerns\FormatsPagination;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserController extends Controller
{
    use ScopesToSchool, FormatsPagination;

    /**
     * school_admin não pode criar/promover ninguém pra esses papéis — só a Secretaria.
     */
    private const NETWORK_ONLY_ROLES = ['department_admin', 'super_admin'];

    public function index(Request $request)
    {
        $query = User::with('role', 'school')
            ->when($request->filled('role'), fn ($q) => $q->whereHas('role', fn ($r) => $r->where('slug', $request->string('role'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('name');

        $this->scopeQueryToSchool($request, $query);

        return $this->paginated($query->paginate(20));
    }

    public function show(Request $request, User $user)
    {
        $this->assertSchoolAccess($request, $user->school_id);

        return ['data' => $user->load('role', 'school', 'student.schoolClass', 'teacher')];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            // Staff precisa de e-mail real. Aluno não (login é por RA) — o e-mail dele
            // é sintetizado a partir do RA só pra satisfazer a coluna, ver método abaixo.
            'email' => ['required_unless:role,student', 'nullable', 'email', 'max:150', 'unique:users,email'],
            'role' => ['required', 'string', 'exists:roles,slug'],
            'school_id' => ['nullable', 'exists:schools,id'],
            'password' => ['nullable', 'string', 'min:8'],
            // Só quando role = student:
            'registration_number' => ['required_if:role,student', 'nullable', 'string', 'max:50', 'unique:students,registration_number'],
            'class_id' => ['required_if:role,student', 'nullable', 'exists:classes,id'],
            'birth_date' => ['nullable', 'date'],
        ]);

        $role = Role::where('slug', $data['role'])->firstOrFail();

        if (in_array($role->slug, self::NETWORK_ONLY_ROLES, true) && ! $this->isNetworkAdmin($request->user())) {
            throw new HttpException(403, 'Apenas a administração da Secretaria pode criar esse tipo de usuário.');
        }

        $this->assertSchoolAccess($request, $data['school_id'] ?? null);

        $user = DB::transaction(function () use ($data, $role) {
            $isStudent = $role->slug === 'student';

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'] ?? $this->syntheticStudentEmail($data['registration_number']),
                'password' => $data['password'] ?? ($isStudent
                    ? config('auth.student_default_password')
                    : Str::password(12)),
                'role_id' => $role->id,
                'school_id' => $data['school_id'] ?? null,
                'status' => 'active',
            ]);

            if ($isStudent) {
                Student::create([
                    'user_id' => $user->id,
                    'registration_number' => $data['registration_number'],
                    'school_id' => $data['school_id'],
                    'class_id' => $data['class_id'],
                    'birth_date' => $data['birth_date'] ?? null,
                ]);
            } elseif ($role->slug === 'teacher') {
                Teacher::create([
                    'user_id' => $user->id,
                    'school_id' => $data['school_id'],
                ]);
            }

            return $user;
        });

        return response()->json(['data' => $user->load('role', 'school', 'student', 'teacher')], 201);
    }

    public function update(Request $request, User $user)
    {
        $this->assertSchoolAccess($request, $user->school_id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'status' => ['sometimes', 'in:active,inactive,pending'],
            'avatar_url' => ['nullable', 'string'],
        ]);

        $user->update($data);

        return ['data' => $user->load('role', 'school')];
    }

    public function destroy(Request $request, User $user)
    {
        $this->assertSchoolAccess($request, $user->school_id);
        $user->delete();

        return response()->noContent();
    }

    private function syntheticStudentEmail(?string $registrationNumber): ?string
    {
        // Aluno pode não ter e-mail real (login é por RA) — gera um identificador
        // único interno só pra satisfazer a coluna, nunca usado pra contato.
        return $registrationNumber ? "ra{$registrationNumber}@ra.expedicaodosaber.local" : null;
    }
}
