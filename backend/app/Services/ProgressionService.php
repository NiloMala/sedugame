<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\CollectibleItem;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\StudentCollectible;
use App\Models\StudentProgress;
use Illuminate\Support\Collection;

/**
 * XP, subida de nível, sequência de dias, conquistas e colecionáveis —
 * seções 16/18 do brief. Roda no fechamento de uma tentativa
 * (Attempt::complete).
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
     * Aplica XP, sequência de dias, colecionável de recompensa da missão,
     * atualiza student_progress e devolve o resultado consolidado pra
     * resposta de POST /api/attempts/{id}/complete.
     *
     * @return array{score:int, experience_gained:int, level_up:bool, achievements_unlocked: Collection<int, Achievement>, collectibles_unlocked: Collection<int, CollectibleItem>}
     */
    public function applyCompletion(Attempt $attempt): array
    {
        $student = $attempt->student;
        $levelBefore = $student->level();

        $experienceGained = $this->experienceForAttempt($attempt);
        $student->increment('experience', $experienceGained);
        $student->registerActivityToday();
        $student->refresh();

        $levelAfter = $student->level();
        $levelUp = $levelAfter && (! $levelBefore || $levelAfter->id !== $levelBefore->id);

        $this->updateProgress($attempt);

        $collectiblesUnlocked = $this->grantMissionReward($student, $attempt);
        $achievementsUnlocked = $this->unlockAchievements($student, $attempt);
        $collectiblesUnlocked = $collectiblesUnlocked->merge($this->collectiblesFromAchievements($student, $achievementsUnlocked));

        return [
            'score' => $attempt->score,
            'experience_gained' => $experienceGained,
            'level_up' => $levelUp,
            'achievements_unlocked' => $achievementsUnlocked,
            'collectibles_unlocked' => $collectiblesUnlocked,
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
     * Concede o colecionável de recompensa da missão (se tiver e o aluno
     * ainda não tiver) — seção 10 do brief ("recompensa" como atributo de
     * missão, nunca modelado até agora).
     *
     * @return Collection<int, CollectibleItem>
     */
    private function grantMissionReward(Student $student, Attempt $attempt): Collection
    {
        return collect([$this->grantCollectible($student, $attempt->mission?->reward_collectible_item_id)])->filter();
    }

    /**
     * @param  Collection<int, Achievement>  $achievements
     * @return Collection<int, CollectibleItem>
     */
    private function collectiblesFromAchievements(Student $student, Collection $achievements): Collection
    {
        return $achievements
            ->map(fn (Achievement $achievement) => $this->grantCollectible($student, $achievement->reward_collectible_item_id))
            ->filter()
            ->values();
    }

    private function grantCollectible(Student $student, ?int $itemId): ?CollectibleItem
    {
        if (! $itemId) {
            return null;
        }

        $alreadyOwned = StudentCollectible::where('student_id', $student->id)
            ->where('collectible_item_id', $itemId)->exists();
        if ($alreadyOwned) {
            return null;
        }

        StudentCollectible::create([
            'student_id' => $student->id,
            'collectible_item_id' => $itemId,
            'unlocked_at' => now(),
        ]);

        return CollectibleItem::find($itemId);
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

            'streak_days' => $student->streak_days >= ($value['days'] ?? PHP_INT_MAX),

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
