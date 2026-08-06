<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Uso: ->middleware('role:teacher') ou ->middleware('role:school_admin,department_admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->role || ! in_array($user->role->slug, $roles, true)) {
            return response()->json(['message' => 'Você não tem permissão para acessar este recurso.'], 403);
        }

        return $next($request);
    }
}
