<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\FormatsPagination;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Mission;
use App\Models\Student;
use App\Models\StudentProgress;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    use FormatsPagination;

    public function index(Request $request)
    {
        $student = $request->user()->student()->first(); // ->first() bypassa cache de relação

        $query = Campaign::with('primarySubject', 'grade')
            ->withCount('missions')
            ->where('status', 'published')
            ->when($request->filled('subject_id'), fn ($q) => $q->where('primary_subject_id', $request->integer('subject_id')))
            ->when($request->filled('grade_id'), fn ($q) => $q->where('grade_id', $request->integer('grade_id')))
            ->when($request->filled('difficulty'), fn ($q) => $q->where('difficulty', $request->string('difficulty')))
            ->orderBy('title');

        $paginator = $query->paginate(20);

        return $this->paginated($paginator, fn (Campaign $campaign) => $this->presentCampaign($campaign, $student));
    }

    public function show(Request $request, Campaign $campaign)
    {
        abort_unless($campaign->status === 'published', 404);

        $student = $request->user()->student()->first(); // ->first() bypassa cache de relação
        $campaign->loadMissing('primarySubject', 'grade');
        $campaign->loadCount('missions');

        $data = $this->presentCampaign($campaign, $student);

        $progressByMission = $student
            ? StudentProgress::where('student_id', $student->id)->where('campaign_id', $campaign->id)->get()->keyBy('mission_id')
            : collect();

        $data['missions'] = $campaign->missions()->where('status', 'published')->orderBy('order')->get()
            ->map(function (Mission $mission) use ($progressByMission, $student) {
                $progress = $progressByMission->get($mission->id);

                return [
                    'id' => $mission->id,
                    'title' => $mission->title,
                    'order' => $mission->order,
                    'status' => $progress?->completed_at ? 'completed' : ($progress ? 'in_progress' : 'available'),
                    'locked' => $student ? ! $this->missionUnlocked($mission, $student) : true,
                ];
            })->values();

        return ['data' => $data];
    }

    private function presentCampaign(Campaign $campaign, ?Student $student): array
    {
        $progressPercent = 0;
        $status = 'available';

        if ($student) {
            $total = $campaign->missions_count ?: 1;
            $completed = StudentProgress::where('student_id', $student->id)
                ->where('campaign_id', $campaign->id)
                ->whereNotNull('completed_at')
                ->count();
            $progressPercent = (int) round(min(100, $completed / $total * 100));
            $status = $completed >= $total && $total > 0 ? 'completed' : ($completed > 0 ? 'in_progress' : 'available');
        }

        return [
            'id' => $campaign->id,
            'title' => $campaign->title,
            'slug' => $campaign->slug,
            'description' => $campaign->description,
            'cover_image_url' => $campaign->cover_image_url,
            'primary_subject' => [
                'id' => $campaign->primarySubject->id,
                'name' => $campaign->primarySubject->name,
                'color' => $campaign->primarySubject->color,
            ],
            'grade' => ['id' => $campaign->grade->id, 'name' => $campaign->grade->name],
            'difficulty' => $campaign->difficulty,
            'missions_count' => $campaign->missions_count,
            'estimated_minutes' => $campaign->estimated_minutes,
            'progress' => ['percent' => $progressPercent, 'status' => $status],
        ];
    }

    /**
     * unlock_rule = null → livre. Senão, {"requires_mission_id": X, "min_score"?: N}.
     */
    private function missionUnlocked(Mission $mission, Student $student): bool
    {
        $rule = $mission->unlock_rule;
        if (! $rule || ! isset($rule['requires_mission_id'])) {
            return true;
        }

        $progress = StudentProgress::where('student_id', $student->id)
            ->where('mission_id', $rule['requires_mission_id'])
            ->first();

        if (! $progress || ! $progress->completed_at) {
            return false;
        }

        return ! isset($rule['min_score']) || $progress->best_score >= $rule['min_score'];
    }
}
