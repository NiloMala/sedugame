<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Admin\Concerns\ScopesToSchool;
use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SchoolYearController extends Controller
{
    use ScopesToSchool;

    public function index()
    {
        return ['data' => SchoolYear::orderByDesc('year')->get()];
    }

    public function show(SchoolYear $schoolYear)
    {
        return ['data' => $schoolYear];
    }

    public function store(Request $request)
    {
        $this->assertNetworkAdmin($request);

        $data = $request->validate([
            'year' => ['required', 'integer', 'digits:4', 'unique:school_years,year'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'status' => ['sometimes', 'in:active,closed'],
        ]);

        return response()->json(['data' => SchoolYear::create($data)], 201);
    }

    public function update(Request $request, SchoolYear $schoolYear)
    {
        $this->assertNetworkAdmin($request);

        $data = $request->validate([
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date', 'after:starts_at'],
            'status' => ['sometimes', 'in:active,closed'],
        ]);

        $schoolYear->update($data);

        return ['data' => $schoolYear];
    }

    public function destroy(Request $request, SchoolYear $schoolYear)
    {
        $this->assertNetworkAdmin($request);
        $schoolYear->delete();

        return response()->noContent();
    }

    private function assertNetworkAdmin(Request $request): void
    {
        if (! $this->isNetworkAdmin($request->user())) {
            throw new HttpException(403, 'Apenas a administração da Secretaria pode alterar este cadastro.');
        }
    }
}
