<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CollectibleItem;
use App\Models\StudentCollectible;
use Illuminate\Http\Request;

/**
 * GET /api/collections — coleções do brief seção 18. Mesmo padrão de
 * /api/achievements: devolve o catálogo inteiro + o que o aluno já tem.
 */
class CollectionController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user()->student()->first();

        $unlockedAt = $student
            ? StudentCollectible::where('student_id', $student->id)->pluck('unlocked_at', 'collectible_item_id')
            : collect();

        $items = CollectibleItem::where('status', 'active')
            ->orderBy('category')->orderBy('name')
            ->get()
            ->map(fn (CollectibleItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'category' => $item->category,
                'icon' => $item->icon,
                'image_url' => $item->image_url,
                'rarity' => $item->rarity,
                'unlocked' => $unlockedAt->has($item->id),
                'unlocked_at' => $unlockedAt->get($item->id),
            ])->values();

        return ['data' => $items];
    }
}
