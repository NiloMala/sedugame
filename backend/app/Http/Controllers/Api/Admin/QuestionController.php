<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\FormatsPagination;
use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Aceita options/hints/location aninhados no mesmo request — não faz sentido
 * pedir pro professor gerenciar isso em telas separadas (brief seção 57:
 * "editor de questões" é um formulário só).
 */
class QuestionController extends Controller
{
    use FormatsPagination;

    public function index(Request $request)
    {
        $query = Question::with('subject', 'skill', 'grade', 'author')
            ->when($request->filled('subject_id'), fn ($q) => $q->where('subject_id', $request->integer('subject_id')))
            ->when($request->filled('skill_id'), fn ($q) => $q->where('skill_id', $request->integer('skill_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id');

        return $this->paginated($query->paginate(20));
    }

    public function show(Question $question)
    {
        return ['data' => $question->load('subject', 'skill', 'grade', 'author', 'options', 'hints', 'location')];
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['author_id'] = $request->user()->id;
        $data['status'] = $data['status'] ?? 'private';

        $question = DB::transaction(fn () => $this->saveWithNested($data, Question::create($this->coreFields($data))));

        return response()->json(['data' => $question->load('options', 'hints', 'location')], 201);
    }

    public function update(Request $request, Question $question)
    {
        $data = $this->validated($request, sometimes: true);

        $question = DB::transaction(function () use ($data, $question) {
            $question->update($this->coreFields($data, $question));

            return $this->saveWithNested($data, $question);
        });

        return ['data' => $question->load('options', 'hints', 'location')];
    }

    public function destroy(Question $question)
    {
        $question->delete();

        return response()->noContent();
    }

    /**
     * POST /api/admin/questions/{id}/review — fluxo de moderação de conteúdo
     * gerado por professor/IA (brief seção 24/25).
     */
    public function review(Request $request, Question $question)
    {
        $data = $request->validate([
            'status' => ['required', 'in:school,network,official,archived'],
            'notes' => ['nullable', 'string'],
        ]);

        $question->update(['status' => $data['status']]);

        // TODO (Fase 2): registrar em content_reviews com notes + reviewer_id.

        return ['data' => $question];
    }

    private function coreFields(array $data, ?Question $existing = null): array
    {
        return array_filter([
            'mission_stage_id' => $data['mission_stage_id'] ?? $existing?->mission_stage_id,
            'subject_id' => $data['subject_id'] ?? null,
            'skill_id' => $data['skill_id'] ?? null,
            'grade_id' => $data['grade_id'] ?? null,
            'type' => $data['type'] ?? null,
            'statement' => $data['statement'] ?? null,
            'explanation' => $data['explanation'] ?? null,
            'difficulty' => $data['difficulty'] ?? null,
            'max_score' => $data['max_score'] ?? 1000,
            'time_limit_seconds' => $data['time_limit_seconds'] ?? null,
            'status' => $data['status'] ?? null,
            'author_id' => $data['author_id'] ?? null,
        ], fn ($value) => $value !== null);
    }

    private function saveWithNested(array $data, Question $question): Question
    {
        if (array_key_exists('options', $data)) {
            $question->options()->delete();
            foreach ($data['options'] ?? [] as $index => $option) {
                $question->options()->create([
                    'text' => $option['text'] ?? null,
                    'image_url' => $option['image_url'] ?? null,
                    'is_correct' => $option['is_correct'] ?? false,
                    'order' => $option['order'] ?? $index,
                    'side' => $option['side'] ?? null,
                ]);
            }
        }

        if (array_key_exists('hints', $data)) {
            $question->hints()->delete();
            foreach ($data['hints'] ?? [] as $index => $hint) {
                $question->hints()->create([
                    'type' => $hint['type'] ?? 'text',
                    'content' => $hint['content'],
                    'score_penalty' => $hint['score_penalty'] ?? 100,
                    'order' => $hint['order'] ?? $index,
                ]);
            }
        }

        if (array_key_exists('location', $data)) {
            $question->location()->delete();
            if (! empty($data['location'])) {
                $question->location()->create([
                    'latitude' => $data['location']['latitude'],
                    'longitude' => $data['location']['longitude'],
                    'accepted_radius_meters' => $data['location']['accepted_radius_meters'] ?? 0,
                ]);
            }
        }

        return $question->fresh();
    }

    private function validated(Request $request, bool $sometimes = false): array
    {
        $required = $sometimes ? 'sometimes' : 'required';

        return $request->validate([
            'mission_stage_id' => ['nullable', 'exists:mission_stages,id'],
            'subject_id' => [$required, 'exists:subjects,id'],
            'skill_id' => [$required, 'exists:skills,id'],
            'grade_id' => [$required, 'exists:grades,id'],
            'type' => [$required, Rule::in(['single_choice', 'multiple_choice', 'true_false', 'map_location', 'ordering', 'matching', 'fill_blank', 'short_answer'])],
            'statement' => [$required, 'string'],
            'explanation' => ['nullable', 'string'],
            'difficulty' => [$required, 'in:easy,medium,hard'],
            'max_score' => ['nullable', 'integer', 'min:0'],
            'time_limit_seconds' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:private,school,network,official,archived'],

            'options' => ['nullable', 'array'],
            'options.*.text' => ['nullable', 'string'],
            'options.*.image_url' => ['nullable', 'string'],
            'options.*.is_correct' => ['nullable', 'boolean'],
            'options.*.order' => ['nullable', 'integer'],
            'options.*.side' => ['nullable', 'in:left,right'],

            'hints' => ['nullable', 'array'],
            'hints.*.type' => ['nullable', 'string', 'max:50'],
            'hints.*.content' => ['required_with:hints', 'string'],
            'hints.*.score_penalty' => ['nullable', 'integer', 'min:0'],
            'hints.*.order' => ['nullable', 'integer'],

            'location' => ['nullable', 'array'],
            'location.latitude' => ['required_with:location', 'numeric', 'between:-90,90'],
            'location.longitude' => ['required_with:location', 'numeric', 'between:-180,180'],
            'location.accepted_radius_meters' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
