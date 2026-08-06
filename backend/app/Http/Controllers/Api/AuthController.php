<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/login
     *
     * Aceita tanto e-mail (staff: professor, coordenador, diretor, admin) quanto
     * RA - registration_number (aluno) no campo `login`. Ver docs/03-api-contract.md.
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = $this->resolveUserByLogin($data['login']);

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Credenciais inválidas.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'login' => ['Este usuário está inativo. Procure a administração da escola.'],
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();

        return response()->noContent();
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    /**
     * GET /api/me
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('role', 'school');

        $payload = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->slug,
            'avatar_url' => $user->avatar_url,
            'school' => $user->school ? [
                'id' => $user->school->id,
                'name' => $user->school->name,
            ] : null,
        ];

        if ($user->role->slug === 'student') {
            $student = $user->student()->with('schoolClass')->first();

            if ($student) {
                $level = $student->level();
                $payload['student'] = [
                    'id' => $student->id,
                    'class' => $student->schoolClass ? [
                        'id' => $student->schoolClass->id,
                        'name' => $student->schoolClass->name,
                    ] : null,
                    'level' => $level ? [
                        'id' => $level->id,
                        'name' => $level->name,
                        'order' => $level->order,
                    ] : null,
                    'experience' => $student->experience,
                    'experience_to_next_level' => $level && $level->maximum_experience
                        ? max(0, $level->maximum_experience - $student->experience)
                        : null,
                ];
            }
        }

        return response()->json(['data' => $payload]);
    }

    /**
     * POST /api/forgot-password
     *
     * Somente para staff com e-mail real. Alunos usam RA + senha padrão;
     * reset de senha de aluno é feito pelo admin (ver StudentPasswordController).
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        // Resposta genérica sempre, para não revelar quais e-mails existem na base.
        return response()->noContent();
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password)])->save();
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->noContent();
    }

    private function resolveUserByLogin(string $login): ?User
    {
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return User::where('email', $login)->first();
        }

        // Não é e-mail: trata como RA de aluno.
        $student = Student::where('registration_number', $login)->first();

        return $student?->user;
    }
}
