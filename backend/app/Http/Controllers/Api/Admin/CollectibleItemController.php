<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Admin\Concerns\ScopesToSchool;
use App\Http\Controllers\Concerns\FormatsPagination;
use App\Http\Controllers\Controller;
use App\Models\CollectibleItem;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Colecionáveis (brief seção 18) são cadastro de rede — mesma regra de
 * escrita de subjects/skills/achievements (só department_admin/super_admin).
 */
class CollectibleItemController extends Controller
{
    use FormatsPagination, ScopesToSchool;

    private const CATEGORIES = [
        'monument', 'animal', 'biome', 'map', 'historical_figure',
        'coat_of_arms', 'flag', 'postcard', 'artifact', 'culture', 'avatar_accessory',
    ];

    public function index(Request $request)
    {
        $query = CollectibleItem::query()
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));

        return $this->paginated($query->orderBy('name')->paginate(20));
    }

    public function show(CollectibleItem $item)
    {
        return ['data' => $item];
    }

    public function store(Request $request)
    {
        $this->assertNetworkAdmin($request);

        $data = $request->validate($this->rules());

        return response()->json(['data' => CollectibleItem::create($data)], 201);
    }

    public function update(Request $request, CollectibleItem $item)
    {
        $this->assertNetworkAdmin($request);

        $data = $request->validate($this->rules(sometimes: true));

        $item->update($data);

        return ['data' => $item];
    }

    public function destroy(Request $request, CollectibleItem $item)
    {
        $this->assertNetworkAdmin($request);
        $item->delete();

        return response()->noContent();
    }

    private function rules(bool $sometimes = false): array
    {
        $required = $sometimes ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'category' => [$required, 'in:'.implode(',', self::CATEGORIES)],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'icon' => ['nullable', 'string', 'max:60'],
            'rarity' => ['sometimes', 'in:common,rare,epic'],
            'status' => ['sometimes', 'in:active,inactive'],
        ];
    }

    private function assertNetworkAdmin(Request $request): void
    {
        if (! $this->isNetworkAdmin($request->user())) {
            throw new HttpException(403, 'Apenas a administração da Secretaria pode alterar este cadastro.');
        }
    }
}
