<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Attempt;
use Illuminate\Http\Request;

class StudentActivityController extends Controller
{
    /**
     * GET /api/activities — atividades atribuídas à turma do aluno logado.
     */
    public function index(Request $request)
    {
        // ->student()->first() (não ->student) evita relação em cache — importante
        // quando o mesmo objeto de usuário autenticado pode ser reaproveitado
        // entre chamadas (ex.: em testes que encadeiam várias requisições).
        $student = $request->user()->student()->first();

        if (! $student) {
            return ['data' => []];
        }

        $activities = Activity::with('campaign')
            ->whereHas('classes', fn ($q) => $q->where('classes.id', $student->class_id))
            ->orderByDesc('starts_at')
            ->get()
            ->map(function (Activity $activity) use ($student) {
                $bestAttempt = Attempt::where('student_id', $student->id)
                    ->where('activity_id', $activity->id)
                    ->orderByDesc('score')
                    ->first();

                return [
                    'id' => $activity->id,
                    'title' => $activity->title,
                    'campaign' => [
                        'id' => $activity->campaign->id,
                        'title' => $activity->campaign->title,
                        'cover_image_url' => $activity->campaign->cover_image_url,
                    ],
                    'starts_at' => $activity->starts_at,
                    'ends_at' => $activity->ends_at,
                    'attempt_limit' => $activity->attempt_limit,
                    'status' => $activity->status,
                    'best_attempt' => $bestAttempt ? [
                        'score' => $bestAttempt->score,
                        'status' => $bestAttempt->status,
                    ] : null,
                ];
            });

        return ['data' => $activities];
    }
}
