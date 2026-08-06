<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Admin\Concerns\ScopesToSchool;
use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SubjectController extends Controller
{
    use ScopesToSchool;

    public function index()
    {
        return ['data' => Subject::orderBy('name')->get()];
    }

    public function show(Subject $subject)
    {
        return ['data' => $subject];
    }

    public function store(Request $request)
    {
        $this->assertNetworkAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'max:80', 'alpha_dash', 'unique:subjects,slug'],
            'icon' => ['nullable', 'string', 'max:60'],
            'color' => ['nullable', 'string', 'max:7'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        return response()->json(['data' => Subject::create($data)], 201);
    }

    public function update(Request $request, Subject $subject)
    {
        $this->assertNetworkAdmin($request);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:80'],
            'icon' => ['nullable', 'string', 'max:60'],
            'color' => ['nullable', 'string', 'max:7'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        $subject->update($data);

        return ['data' => $subject];
    }

    public function destroy(Request $request, Subject $subject)
    {
        $this->assertNetworkAdmin($request);
        $subject->delete();

        return response()->noContent();
    }

    private function assertNetworkAdmin(Request $request): void
    {
        if (! $this->isNetworkAdmin($request->user())) {
            throw new HttpException(403, 'Apenas a administração da Secretaria pode alterar este cadastro.');
        }
    }
}
