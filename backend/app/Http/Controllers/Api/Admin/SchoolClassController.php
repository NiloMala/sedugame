<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Admin\Concerns\ScopesToSchool;
use App\Http\Controllers\Concerns\FormatsPagination;
use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class SchoolClassController extends Controller
{
    use ScopesToSchool, FormatsPagination;

    public function index(Request $request)
    {
        $query = SchoolClass::with(['school', 'grade', 'schoolYear', 'teachers.user'])->orderBy('name');
        $this->scopeQueryToSchool($request, $query);

        if ($this->isNetworkAdmin($request->user()) && $request->filled('school_id')) {
            $query->where('school_id', $request->integer('school_id'));
        }

        return $this->paginated($query->paginate(20));
    }

    public function show(Request $request, SchoolClass $class)
    {
        $this->assertSchoolAccess($request, $class->school_id);

        return ['data' => $class->load('school', 'grade', 'schoolYear', 'teachers.user')];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'school_id' => ['required', 'exists:schools,id'],
            'name' => ['required', 'string', 'max:60'],
            'grade_id' => ['required', 'exists:grades,id'],
            'school_year_id' => ['required', 'exists:school_years,id'],
            'shift' => ['required', 'in:morning,afternoon,night,full'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        $this->assertSchoolAccess($request, (int) $data['school_id']);

        return response()->json(['data' => SchoolClass::create($data)], 201);
    }

    public function update(Request $request, SchoolClass $class)
    {
        $this->assertSchoolAccess($request, $class->school_id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:60'],
            'grade_id' => ['sometimes', 'exists:grades,id'],
            'school_year_id' => ['sometimes', 'exists:school_years,id'],
            'shift' => ['sometimes', 'in:morning,afternoon,night,full'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        $class->update($data);

        return ['data' => $class];
    }

    public function destroy(Request $request, SchoolClass $class)
    {
        $this->assertSchoolAccess($request, $class->school_id);
        $class->delete();

        return response()->noContent();
    }
}
