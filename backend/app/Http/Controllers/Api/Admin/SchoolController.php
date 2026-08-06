<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Admin\Concerns\ScopesToSchool;
use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SchoolController extends Controller
{
    use ScopesToSchool;

    public function index(Request $request)
    {
        $query = School::query()->orderBy('name');
        $this->scopeQueryToSchool($request, $query, 'id');

        return ['data' => $query->paginate(20)];
    }

    public function show(Request $request, School $school)
    {
        $this->assertSchoolAccess($request, $school->id);

        return ['data' => $school];
    }

    public function store(Request $request)
    {
        // Criar escola nova é exclusivo da Secretaria — school_admin já nasce vinculado a uma escola.
        if (! $this->isNetworkAdmin($request->user())) {
            throw new HttpException(403, 'Apenas a administração da Secretaria pode cadastrar novas escolas.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'code' => ['required', 'string', 'max:50', 'unique:schools,code'],
            'address' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'size:2'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        return response()->json(['data' => School::create($data)], 201);
    }

    public function update(Request $request, School $school)
    {
        $this->assertSchoolAccess($request, $school->id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:200'],
            'address' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:120'],
            'city' => ['sometimes', 'string', 'max:120'],
            'state' => ['sometimes', 'string', 'size:2'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        $school->update($data);

        return ['data' => $school];
    }

    public function destroy(Request $request, School $school)
    {
        if (! $this->isNetworkAdmin($request->user())) {
            throw new HttpException(403, 'Apenas a administração da Secretaria pode excluir escolas.');
        }

        $school->delete();

        return response()->noContent();
    }
}
