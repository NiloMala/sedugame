<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Admin\Concerns\ScopesToSchool;
use App\Http\Controllers\Controller;
use App\Models\Grade;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Catálogo de séries/anos escolares. Cadastro de rede (Secretaria) — não é
 * vinculado a uma escola específica, então só department_admin/super_admin
 * podem criar/editar/excluir; school_admin só lê (precisa pra montar turmas).
 */
class GradeController extends Controller
{
    use ScopesToSchool;

    public function index()
    {
        return ['data' => Grade::orderBy('order')->get()];
    }

    public function show(Grade $grade)
    {
        return ['data' => $grade];
    }

    public function store(Request $request)
    {
        $this->assertNetworkAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'code' => ['nullable', 'string', 'max:20'],
            'education_level' => ['required', 'in:EF1,EF2,EM,EJA'],
            'order' => ['required', 'integer', 'min:0', 'max:255'],
        ]);

        return response()->json(['data' => Grade::create($data)], 201);
    }

    public function update(Request $request, Grade $grade)
    {
        $this->assertNetworkAdmin($request);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:60'],
            'code' => ['nullable', 'string', 'max:20'],
            'education_level' => ['sometimes', 'in:EF1,EF2,EM,EJA'],
            'order' => ['sometimes', 'integer', 'min:0', 'max:255'],
        ]);

        $grade->update($data);

        return ['data' => $grade];
    }

    public function destroy(Request $request, Grade $grade)
    {
        $this->assertNetworkAdmin($request);
        $grade->delete();

        return response()->noContent();
    }

    private function assertNetworkAdmin(Request $request): void
    {
        if (! $this->isNetworkAdmin($request->user())) {
            throw new HttpException(403, 'Apenas a administração da Secretaria pode alterar este cadastro.');
        }
    }
}
