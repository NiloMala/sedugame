<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Admin\Concerns\ScopesToSchool;
use App\Http\Controllers\Controller;
use App\Models\Level;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Níveis são cadastro de rede — mesma regra de escrita de subjects/skills
 * (só department_admin/super_admin). Sem paginação: em qualquer rede real
 * são poucos registros (o brief seção 16 sugere ~6) e a progressão de XP
 * do aluno (Level::forExperience) depende de enxergar a lista inteira.
 */
class LevelController extends Controller
{
    use ScopesToSchool;

    public function index()
    {
        return ['data' => Level::orderBy('order')->get()];
    }

    public function show(Level $level)
    {
        return ['data' => $level];
    }

    public function store(Request $request)
    {
        $this->assertNetworkAdmin($request);

        $data = $request->validate($this->rules());

        return response()->json(['data' => Level::create($data)], 201);
    }

    public function update(Request $request, Level $level)
    {
        $this->assertNetworkAdmin($request);

        $data = $request->validate($this->rules(sometimes: true));

        $level->update($data);

        return ['data' => $level];
    }

    public function destroy(Request $request, Level $level)
    {
        $this->assertNetworkAdmin($request);
        $level->delete();

        return response()->noContent();
    }

    private function rules(bool $sometimes = false): array
    {
        $required = $sometimes ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'max:100'],
            'minimum_experience' => [$required, 'integer', 'min:0'],
            'maximum_experience' => ['nullable', 'integer', 'gte:minimum_experience'],
            'icon' => ['nullable', 'string', 'max:60'],
            'order' => [$required, 'integer', 'min:1', 'max:255'],
        ];
    }

    private function assertNetworkAdmin(Request $request): void
    {
        if (! $this->isNetworkAdmin($request->user())) {
            throw new HttpException(403, 'Apenas a administração da Secretaria pode alterar este cadastro.');
        }
    }
}
