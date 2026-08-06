<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Level;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\StudentProgress;
use Illuminate\Support\Collection;

/**
 * XP, subida de nível e desbloqueio de conquistas — seção 16 do brief.
 * Roda no fechamento de uma tentativa (Attempt::complete).
 */
class ProgressionService
{
    /**
     * XP concedido por missão: uma fração da pontuação total, com piso de 1
     * ponto de XP por questão respondida corretamente (pra sempre progredir
     * algo, mesmo com pontuação baixa).
     */
    public function experienceForAttempt(Attempt $attempt): int
    {
        $correctAnswers = $attempt->answers()->where('is_correct', true)->count();

        return max($correctAnswers * 10, (int) round($attempt->score / 10));
    }

    /**
     * Aplica XP, atualiza student_progress e devolve o resultado consolidado
     * pra resposta de POST /api/attempts/{id}/complete.
     *
     * @return array{score:int, experience_gained:int, level_up:bool, achievements_unlocked: Collection<int, Achievement>}
     */
    public function applyCompletion(Attempt $attempt): array
    {
        $student = $attempt->student;
        $levelBefore = $student->level();

        $experienceGained = $this->experienceForAttempt($attempt);
        $student->increment('experience', $experienceGained);
        $student->refresh();

        $levelAfter = $student->level();
        $levelUp = $levelAfter && (! $levelBefore || $levelAfter->id !== $levelBefore->id);

        $this->updateProgress($attempt);

        $unlocked = $this->unlockAchievements($student, $attempt);

        return [
            'score' => $attempt->score,
            'experience_gained' => $experienceGained,
            'level_up' => $levelUp,
            'achievements_unlocked' => $unlocked,
        ];
    }

    private function updateProgress(Attempt $attempt): void
    {
        $progress = StudentProgress::firstOrNew([
            'student_id' => $attempt->student_id,
            'campaign_id' => $attempt->campaign_id,
            'mission_id' => $attempt->mission_id,
        ]);

        $progress->attempts_count = ($progress->attempts_count ?? 0) + 1;
        $progress->best_score = max($progress->best_score ?? 0, $attempt->score);
        $progress->progress_percent = 100;
        $progress->completed_at = now();
        $progress->save();
    }

    /**
     * @return Collection<int, Achievement>
     */
    private function unlockAchievements(Student $student, Attempt $attempt): Collection
    {
        $alreadyUnlocked = StudentAchievement::where('student_id', $student->id)->pluck('achievement_id');

        $candidates = Achievement::where('status', 'active')
            ->whereNotIn('id', $alreadyUnlocked)
            ->get();

        $unlocked = collect();

        foreach ($candidates as $achievement) {
            if ($this->achievementSatisfied($achievement, $student, $attempt)) {
                StudentAchievement::create([
                    'student_id' => $student->id,
                    'achievement_id' => $achievement->id,
                    'unlocked_at' => now(),
                ]);

                if ($achievement->experience_reward > 0) {
                    $student->increment('experience', $achievement->experience_reward);
                }

                $unlocked->push($achievement);
            }
        }

        return $unlocked;
    }

    private function achievementSatisfied(Achievement $achievement, Student $student, Attempt $attempt): bool
    {
        $value = $achievement->rule_value ?? [];

        return match ($achievement->rule_type) {
            'first_mission_completed' => StudentProgress::where('student_id', $student->id)
                ->whereNotNull('completed_at')->count() === 1,

            'missions_completed_count' => StudentProgress::where('student_id', $student->id)
                ->whereNotNull('completed_at')->count() >= ($value['count'] ?? PHP_INT_MAX),

            'correct_answers_count' => AttemptAnswer::whereIn('attempt_id', $student->attempts()->pluck('id'))
                ->where('is_correct', true)->count() >= ($value['count'] ?? PHP_INT_MAX),

            'mission_without_hints' => $attempt->answers()->sum('hints_used') === 0,

            'campaign_completed' => isset($value['campaign_id'])
                && $attempt->campaign_id === (int) $value['campaign_id']
                && $this->campaignFullyCompleted($student, (int) $value['campaign_id']),

            default => false,
        };
    }

    private function campaignFullyCompleted(Student $student, int $campaignId): bool
    {
        $missionIds = \App\Models\Mission::where('campaign_id', $campaignId)->pluck('id');
        if ($missionIds->isEmpty()) {
            return false;
        }

        $completedCount = StudentProgress::where('student_id', $student->id)
            ->whereIn('mission_id', $missionIds)
            ->whereNotNull('completed_at')
            ->count();

        return $completedCount >= $missionIds->count();
    }
}
