<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\FormatsPagination;
use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    use FormatsPagination;

    public function index(Request $request)
    {
        $query = Location::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->orderBy('name');

        return $this->paginated($query->paginate(20));
    }

    public function show(Location $location)
    {
        return ['data' => $location];
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        return response()->json(['data' => Location::create($data)], 201);
    }

    public function update(Request $request, Location $location)
    {
        $data = $this->validated($request, sometimes: true);
        $location->update($data);

        return ['data' => $location];
    }

    public function destroy(Location $location)
    {
        $location->delete();

        return response()->noContent();
    }

    private function validated(Request $request, bool $sometimes = false): array
    {
        $required = $sometimes ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'latitude' => [$required, 'numeric', 'between:-90,90'],
            'longitude' => [$required, 'numeric', 'between:-180,180'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'size:2'],
            'country' => ['nullable', 'string', 'max:80'],
            'biome' => ['nullable', 'string', 'max:80'],
            'historical_period' => ['nullable', 'string', 'max:80'],
            'source_type' => ['nullable', 'string', 'max:50'],
            'source_url' => ['nullable', 'string', 'max:255'],
            'license' => ['nullable', 'string', 'max:120'],
            'attribution' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
