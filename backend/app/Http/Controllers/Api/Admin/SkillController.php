<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Admin\Concerns\ScopesToSchool;
use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SkillController extends Controller
{
    use ScopesToSchool;

    public function index(Request $request)
    {
        $query = Skill::with(['subject', 'grade'])
            ->when($request->filled('subject_id'), fn ($q) => $q->where('subject_id', $request->integer('subject_id')))
            ->when($request->filled('grade_id'), fn ($q) => $q->where('grade_id', $request->integer('grade_id')));

        return ['data' => $query->orderBy('title')->paginate(20)];
    }

    public function show(Skill $skill)
    {
        return ['data' => $skill->load('subject', 'grade')];
    }

    public function store(Request $request)
    {
        $this->assertNetworkAdmin($request);

        $data = $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'grade_id' => ['required', 'exists:grades,id'],
            'code' => ['nullable', 'string', 'max:30'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        return response()->json(['data' => Skill::create($data)], 201);
    }

    public function update(Request $request, Skill $skill)
    {
        $this->assertNetworkAdmin($request);

        $data = $request->validate([
            'subject_id' => ['sometimes', 'exists:subjects,id'],
            'grade_id' => ['sometimes', 'exists:grades,id'],
            'code' => ['nullable', 'string', 'max:30'],
            'title' => ['sometimes', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        $skill->update($data);

        return ['data' => $skill];
    }

    public function destroy(Request $request, Skill $skill)
    {
        $this->assertNetworkAdmin($request);
        $skill->delete();

        return response()->noContent();
    }

    private function assertNetworkAdmin(Request $request): void
    {
        if (! $this->isNetworkAdmin($request->user())) {
            throw new HttpException(403, 'Apenas a administração da Secretaria pode alterar este cadastro.');
        }
    }
}
