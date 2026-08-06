<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SettingsController extends Controller
{
    public function show()
    {
        return ['data' => PlatformSetting::current()];
    }

    public function update(Request $request)
    {
        if (! in_array($request->user()->role->slug, ['department_admin', 'super_admin'], true)) {
            throw new HttpException(403, 'Apenas a administração da Secretaria pode alterar as configurações da rede.');
        }

        $data = $request->validate([
            'platform_name' => ['sometimes', 'string', 'max:150'],
            'theme_colors' => ['sometimes', 'array'],
            'scoring_rules' => ['sometimes', 'array'],
        ]);

        $settings = PlatformSetting::current();
        $settings->update($data);

        return ['data' => $settings];
    }
}
