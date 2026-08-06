<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttemptAnswer;
use App\Models\Campaign;
use App\Models\Location;
use App\Models\Mission;
use App\Models\StudentAchievement;
use App\Models\StudentCollectible;
use App\Models\StudentProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PassportController extends Controller
{
    /**
     * GET /api/passport — shape fixo, ver docs/03-api-contract.md.
     */
    public function show(Request $request)
    {
        $student = $request->user()->student()->first(); // ->first() bypassa cache de relação
        abort_unless($student, 403);

        $student->load('schoolClass', 'school');

        return ['data' => [
            'name' => $request->user()->name,
            'school' => $student->school?->name,
            'class' => $student->schoolClass?->name,
            'level' => $this->levelPayload($student),
            'experience' => $student->experience,
            'streak_days' => $student->streak_days,
            'avatar' => [
                'base' => $student->avatar_base,
                'accessory' => $student->equippedAccessory ? [
                    'id' => $student->equippedAccessory->id,
                    'name' => $student->equippedAccessory->name,
                    'icon' => $student->equippedAccessory->icon,
                ] : null,
            ],
            'completed_campaigns' => $this->completedCampaigns($student),
            'locations_visited' => $this->locationsVisited($student),
            'achievements' => $this->unlockedAchievements($student),
            'collectibles_count' => StudentCollectible::where('student_id', $student->id)->count(),
            'performance_by_subject' => $this->performanceBySubject($student),
        ]];
    }

    private function levelPayload($student): ?array
    {
        $level = $student->level();

        return $level ? ['id' => $level->id, 'name' => $level->name, 'order' => $level->order] : null;
    }

    private function completedCampaigns($student): array
    {
        $progressByCampaign = StudentProgress::where('student_id', $student->id)
            ->whereNotNull('completed_at')
            ->get()
            ->groupBy('campaign_id');

        $completed = [];

        foreach ($progressByCampaign as $campaignId => $rows) {
            $missionIds = Mission::where('campaign_id', $campaignId)->pluck('id');
            if ($missionIds->isEmpty() || $rows->pluck('mission_id')->unique()->count() < $missionIds->count()) {
                continue;
            }

            $campaign = Campaign::find($campaignId);
            if (! $campaign) {
                continue;
            }

            $completed[] = [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'completed_at' => $rows->max('completed_at'),
            ];
        }

        return $completed;
    }

    private function locationsVisited($student): array
    {
        return Location::query()
            ->join('mission_stages', 'mission_stages.location_id', '=', 'locations.id')
            ->join('questions', 'questions.mission_stage_id', '=', 'mission_stages.id')
            ->join('attempt_answers', 'attempt_answers.question_id', '=', 'questions.id')
            ->join('attempts', 'attempts.id', '=', 'attempt_answers.attempt_id')
            ->where('attempts.student_id', $student->id)
            ->where('attempt_answers.is_correct', true)
            ->select('locations.id', 'locations.name', 'locations.latitude', 'locations.longitude')
            ->distinct()
            ->get()
            ->map(fn ($location) => [
                'id' => $location->id,
                'name' => $location->name,
                'latitude' => (float) $location->latitude,
                'longitude' => (float) $location->longitude,
            ])->values()->all();
    }

    private function unlockedAchievements($student): array
    {
        return StudentAchievement::with('achievement')
            ->where('student_id', $student->id)
            ->orderByDesc('unlocked_at')
            ->get()
            ->map(fn (StudentAchievement $unlock) => [
                'id' => $unlock->achievement->id,
                'title' => $unlock->achievement->title,
                'icon' => $unlock->achievement->icon,
                'unlocked_at' => $unlock->unlocked_at,
            ])->values()->all();
    }

    private function performanceBySubject($student): array
    {
        return AttemptAnswer::query()
            ->join('attempts', 'attempts.id', '=', 'attempt_answers.attempt_id')
            ->join('questions', 'questions.id', '=', 'attempt_answers.question_id')
            ->join('subjects', 'subjects.id', '=', 'questions.subject_id')
            ->where('attempts.student_id', $student->id)
            ->groupBy('subjects.id', 'subjects.name')
            ->select(
                'subjects.name as subject',
                DB::raw('sum(case when attempt_answers.is_correct then 1 else 0 end) as correct'),
                DB::raw('count(*) as total')
            )
            ->get()
            ->map(fn ($row) => [
                'subject' => $row->subject,
                'accuracy_percent' => $row->total > 0 ? (int) round($row->correct / $row->total * 100) : 0,
            ])->values()->all();
    }
}
