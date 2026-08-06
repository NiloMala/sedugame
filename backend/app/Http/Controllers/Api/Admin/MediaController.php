<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\FormatsPagination;
use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;

/**
 * MVP: só registra metadados + file_url (upload já feito em storage/S3-R2 por
 * fora). Endpoint de upload direto de arquivo fica pra Fase 2.
 */
class MediaController extends Controller
{
    use FormatsPagination;

    public function index(Request $request)
    {
        $query = Media::query()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->orderByDesc('id');

        return $this->paginated($query->paginate(20));
    }

    public function show(Media $medium)
    {
        return ['data' => $medium];
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        return response()->json(['data' => Media::create($data)], 201);
    }

    public function update(Request $request, Media $medium)
    {
        $data = $this->validated($request, sometimes: true);
        $medium->update($data);

        return ['data' => $medium];
    }

    public function destroy(Media $medium)
    {
        $medium->delete();

        return response()->noContent();
    }

    private function validated(Request $request, bool $sometimes = false): array
    {
        $required = $sometimes ? 'sometimes' : 'required';

        return $request->validate([
            'type' => [$required, 'in:image,panorama_360,video,audio'],
            'title' => ['nullable', 'string', 'max:150'],
            'file_url' => [$required, 'string', 'max:255'],
            'thumbnail_url' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:120'],
            'license' => ['nullable', 'string', 'max:120'],
            'author' => ['nullable', 'string', 'max:120'],
            'attribution' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);
    }
}
