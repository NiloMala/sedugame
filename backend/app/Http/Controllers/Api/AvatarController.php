<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentCollectible;
use App\Support\AvatarPresets;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Avatar do aluno: personagem ilustrado pré-definido (AvatarPresets) +
 * acessório opcional de coleção. Nunca foto real — ver comentário na
 * migration 2026_08_07_000004.
 */
class AvatarController extends Controller
{
    public function show(Request $request)
    {
        $student = $request->user()->student()->first();
        abort_unless($student, 403);

        return ['data' => $this->present($student)];
    }

    public function update(Request $request)
    {
        $student = $request->user()->student()->first();
        abort_unless($student, 403);

        $data = $request->validate([
            'avatar_base' => ['required', Rule::in(AvatarPresets::codes())],
            'equipped_accessory_id' => ['nullable', 'exists:collectible_items,id'],
        ]);

        if (! empty($data['equipped_accessory_id'])) {
            $owns = StudentCollectible::where('student_id', $student->id)
                ->where('collectible_item_id', $data['equipped_accessory_id'])->exists();
            abort_unless($owns, 422, 'Você ainda não desbloqueou esse acessório.');
        }

        $student->update([
            'avatar_base' => $data['avatar_base'],
            'equipped_accessory_id' => $data['equipped_accessory_id'] ?? null,
        ]);

        return ['data' => $this->present($student->fresh())];
    }

    private function present($student): array
    {
        $unlockedAccessories = StudentCollectible::with('item')
            ->where('student_id', $student->id)
            ->whereHas('item', fn ($q) => $q->where('category', 'avatar_accessory'))
            ->get()
            ->map(fn (StudentCollectible $unlock) => [
                'id' => $unlock->item->id,
                'name' => $unlock->item->name,
                'icon' => $unlock->item->icon,
                'image_url' => $unlock->item->image_url,
            ])->values();

        return [
            'avatar_base' => $student->avatar_base,
            'equipped_accessory' => $student->equippedAccessory ? [
                'id' => $student->equippedAccessory->id,
                'name' => $student->equippedAccessory->name,
                'icon' => $student->equippedAccessory->icon,
            ] : null,
            'available_bases' => AvatarPresets::OPTIONS,
            'unlocked_accessories' => $unlockedAccessories,
        ];
    }
}
