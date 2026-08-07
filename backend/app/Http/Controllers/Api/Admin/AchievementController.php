<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Admin\Concerns\ScopesToSchool;
use App\Http\Controllers\Concerns\FormatsPagination;
use App\Http\Controllers\Controller;
use App\Models\Achievement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Conquistas são cadastro de rede (como subjects/skills) — não pertencem a
 * uma escola, então só department_admin/super_admin escrevem; qualquer
 * admin (inclusive school_admin) pode listar/ver.
 */
class AchievementController extends Controller
{
    use FormatsPagination, ScopesToSchool;

    /**
     * Tipos de regra reconhecidos por ProgressionService::achievementSatisfied().
     * Cadastrar um rule_type fora dessa lista deixaria a conquista órfã (nunca
     * desbloqueia, pois cai no `default => false` do match).
     */
    private const RULE_TYPES = [
        'first_mission_completed',
        'missions_completed_count',
        'correct_answers_count',
        'mission_without_hints',
        'streak_days',
        'campaign_completed',
    ];

    public function index()
    {
        return $this->paginated(
            Achievement::with('rewardCollectible')->orderBy('title')->paginate(20)
        );
    }

    public function show(Achievement $achievement)
    {
        return ['data' => $achievement->load('rewardCollectible')];
    }

    public function store(Request $request)
    {
        $this->assertNetworkAdmin($request);

        $data = $request->validate($this->rules());

        return response()->json(['data' => Achievement::create($data)], 201);
    }

    public function update(Request $request, Achievement $achievement)
    {
        $this->assertNetworkAdmin($request);

        $data = $request->validate($this->rules(sometimes: true));

        $achievement->update($data);

        return ['data' => $achievement];
    }

    public function destroy(Request $request, Achievement $achievement)
    {
        $this->assertNetworkAdmin($request);
        $achievement->delete();

        return response()->noContent();
    }

    private function rules(bool $sometimes = false): array
    {
        $required = $sometimes ? 'sometimes' : 'required';

        return [
            'title' => [$required, 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:60'],
            'rule_type' => [$required, Rule::in(self::RULE_TYPES)],
            'rule_value' => ['nullable', 'array'],
            'experience_reward' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:active,inactive'],
            'reward_collectible_item_id' => ['nullable', 'exists:collectible_items,id'],
        ];
    }

    private function assertNetworkAdmin(Request $request): void
    {
        if (! $this->isNetworkAdmin($request->user())) {
            throw new HttpException(403, 'Apenas a administração da Secretaria pode alterar este cadastro.');
        }
    }
}
