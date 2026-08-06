<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mission;

class MissionController extends Controller
{
    public function show(Mission $mission)
    {
        abort_unless($mission->status === 'published', 404);

        $mission->load(['stages' => fn ($q) => $q->orderBy('order'), 'stages.location', 'stages.media']);

        return ['data' => [
            'id' => $mission->id,
            'campaign_id' => $mission->campaign_id,
            'title' => $mission->title,
            'narrative' => $mission->narrative,
            'objective' => $mission->objective,
            'difficulty' => $mission->difficulty,
            'max_score' => $mission->max_score,
            'stages' => $mission->stages->map(fn ($stage) => [
                'id' => $stage->id,
                'order' => $stage->order,
                'content' => $stage->content,
                'location' => $stage->location ? [
                    'id' => $stage->location->id,
                    'name' => $stage->location->name,
                    'latitude' => (float) $stage->location->latitude,
                    'longitude' => (float) $stage->location->longitude,
                ] : null,
                'media' => $stage->media->map(fn ($media) => [
                    'id' => $media->id,
                    'type' => $media->type,
                    'file_url' => $media->file_url,
                ])->values(),
            ])->values(),
        ]];
    }
}
