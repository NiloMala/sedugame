<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\StudentAchievement;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    /**
     * GET /api/achievements — todas as conquistas ativas + quais o aluno já desbloqueou.
     */
    public function index(Request $request)
    {
        $student = $request->user()->student()->first(); // ->first() bypassa cache de relação

        $unlockedAt = $student
            ? StudentAchievement::where('student_id', $student->id)->pluck('unlocked_at', 'achievement_id')
            : collect();

        $achievements = Achievement::where('status', 'active')->orderBy('title')->get()
            ->map(fn (Achievement $achievement) => [
                'id' => $achievement->id,
                'title' => $achievement->title,
                'description' => $achievement->description,
                'icon' => $achievement->icon,
                'unlocked' => $unlockedAt->has($achievement->id),
                'unlocked_at' => $unlockedAt->get($achievement->id),
            ])->values();

        return ['data' => $achievements];
    }
}
