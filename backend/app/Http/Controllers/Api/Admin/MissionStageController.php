<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mission;
use App\Models\MissionStage;
use Illuminate\Http\Request;

/**
 * Nested sob a missão: /api/admin/missions/{mission}/stages. Não tem
 * endpoint próprio na listagem do contrato porque etapa não existe fora do
 * contexto de uma missão.
 */
class MissionStageController extends Controller
{
    public function index(Mission $mission)
    {
        return ['data' => $mission->stages()->with('location', 'media')->orderBy('order')->get()];
    }

    public function store(Request $request, Mission $mission)
    {
        $data = $this->validated($request);
        $mediaIds = $data['media_ids'] ?? null;
        unset($data['media_ids']);
        $data['mission_id'] = $mission->id;

        $stage = MissionStage::create($data);
        if ($mediaIds !== null) {
            $stage->media()->sync(collect($mediaIds)->mapWithKeys(fn ($id, $order) => [$id => ['order' => $order]]));
        }

        return response()->json(['data' => $stage->load('location', 'media')], 201);
    }

    public function update(Request $request, Mission $mission, MissionStage $stage)
    {
        abort_unless($stage->mission_id === $mission->id, 404);

        $data = $this->validated($request, sometimes: true);
        $mediaIds = $data['media_ids'] ?? null;
        unset($data['media_ids']);

        $stage->update($data);
        if ($mediaIds !== null) {
            $stage->media()->sync(collect($mediaIds)->mapWithKeys(fn ($id, $order) => [$id => ['order' => $order]]));
        }

        return ['data' => $stage->load('location', 'media')];
    }

    public function destroy(Mission $mission, MissionStage $stage)
    {
        abort_unless($stage->mission_id === $mission->id, 404);
        $stage->delete();

        return response()->noContent();
    }

    private function validated(Request $request, bool $sometimes = false): array
    {
        return $request->validate([
            'title' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'order' => ['sometimes', 'integer', 'min:0'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'media_ids' => ['nullable', 'array'],
            'media_ids.*' => ['exists:media,id'],
        ]);
    }
}
