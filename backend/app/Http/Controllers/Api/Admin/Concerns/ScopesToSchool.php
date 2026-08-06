<?php

namespace App\Http\Controllers\Api\Admin\Concerns;

use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * school_admin só enxerga/edita dados da própria escola; department_admin e
 * super_admin enxergam a rede toda. Usado pelos controllers admin que lidam
 * com recursos vinculados a uma escola (turmas, usuários).
 */
trait ScopesToSchool
{
    protected function isNetworkAdmin(User $user): bool
    {
        return in_array($user->role->slug, ['department_admin', 'super_admin'], true);
    }

    /**
     * Lança 403 se um school_admin tentar acessar/gravar em escola que não é a sua.
     */
    protected function assertSchoolAccess(Request $request, ?int $schoolId): void
    {
        $user = $request->user();

        if ($this->isNetworkAdmin($user)) {
            return;
        }

        if ($schoolId === null || $schoolId !== $user->school_id) {
            throw new HttpException(403, 'Você só pode gerenciar recursos da sua própria escola.');
        }
    }

    /**
     * Restringe uma query a `school_id` quando o usuário é school_admin.
     */
    protected function scopeQueryToSchool(Request $request, $query, string $column = 'school_id')
    {
        $user = $request->user();

        if (! $this->isNetworkAdmin($user)) {
            $query->where($column, $user->school_id);
        }

        return $query;
    }
}
