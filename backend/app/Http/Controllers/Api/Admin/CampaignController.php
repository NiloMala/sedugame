<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\FormatsPagination;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CampaignController extends Controller
{
    use FormatsPagination;

    public function index(Request $request)
    {
        $query = Campaign::with('primarySubject', 'grade', 'author')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('subject_id'), fn ($q) => $q->where('primary_subject_id', $request->integer('subject_id')))
            ->orderByDesc('id');

        return $this->paginated($query->paginate(20));
    }

    public function show(Campaign $campaign)
    {
        return ['data' => $campaign->load('primarySubject', 'grade', 'author', 'missions')];
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['title']);
        $data['author_id'] = $request->user()->id;
        $data['status'] = 'draft';

        return response()->json(['data' => Campaign::create($data)], 201);
    }

    public function update(Request $request, Campaign $campaign)
    {
        $data = $this->validated($request, sometimes: true);
        $campaign->update($data);

        return ['data' => $campaign];
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();

        return response()->noContent();
    }

    /**
     * POST /api/admin/campaigns/{id}/publish
     * Fluxo de publicação (brief seção 23): aqui simplificado pra draft -> published direto
     * quando quem publica já é admin (o fluxo de revisão de professor entra na Fase 2).
     */
    public function publish(Campaign $campaign)
    {
        abort_if($campaign->missions()->where('status', 'published')->doesntExist(), 422, 'A campanha precisa de ao menos uma missão publicada antes de ser publicada.');

        $campaign->update(['status' => 'published', 'published_at' => now()]);

        return ['data' => $campaign];
    }

    private function validated(Request $request, bool $sometimes = false): array
    {
        $required = $sometimes ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$required, 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:160', 'alpha_dash', 'unique:campaigns,slug'],
            'description' => ['nullable', 'string'],
            'cover_image_url' => ['nullable', 'string', 'max:255'],
            'primary_subject_id' => [$required, 'exists:subjects,id'],
            'grade_id' => [$required, 'exists:grades,id'],
            'difficulty' => [$required, 'in:easy,medium,hard'],
            'visibility' => ['sometimes', 'in:private,school,network'],
            'estimated_minutes' => ['nullable', 'integer', 'min:0'],
            'max_score' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
