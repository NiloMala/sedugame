<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\FormatsPagination;
use App\Http\Controllers\Controller;
use App\Models\Mission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MissionController extends Controller
{
    use FormatsPagination;

    public function index(Request $request)
    {
        $query = Mission::with('campaign')
            ->when($request->filled('campaign_id'), fn ($q) => $q->where('campaign_id', $request->integer('campaign_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('campaign_id')->orderBy('order');

        return $this->paginated($query->paginate(20));
    }

    public function show(Mission $mission)
    {
        return ['data' => $mission->load('campaign', 'stages.location', 'stages.media')];
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['title']);
        $data['status'] = $data['status'] ?? 'draft';

        return response()->json(['data' => Mission::create($data)], 201);
    }

    public function update(Request $request, Mission $mission)
    {
        $data = $this->validated($request, sometimes: true);
        $mission->update($data);

        return ['data' => $mission];
    }

    public function destroy(Mission $mission)
    {
        $mission->delete();

        return response()->noContent();
    }

    private function validated(Request $request, bool $sometimes = false): array
    {
        $required = $sometimes ? 'sometimes' : 'required';

        return $request->validate([
            'campaign_id' => [$required, 'exists:campaigns,id'],
            'title' => [$required, 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'narrative' => ['nullable', 'string'],
            'objective' => ['nullable', 'string', 'max:255'],
            'order' => ['sometimes', 'integer', 'min:0'],
            'difficulty' => [$required, 'in:easy,medium,hard'],
            'estimated_minutes' => ['nullable', 'integer', 'min:0'],
            'max_score' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:draft,published,archived'],
            'unlock_rule' => ['nullable', 'array'],
        ]);
    }
}
