<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $fillable = ['platform_name', 'theme_colors', 'scoring_rules'];

    protected function casts(): array
    {
        return [
            'theme_colors' => 'array',
            'scoring_rules' => 'array',
        ];
    }

    /**
     * Sempre uma linha só. Cria com valores padrão na primeira leitura/escrita.
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'platform_name' => 'Expedição do Saber',
            'theme_colors' => ['primary' => '#0f766e', 'accent' => '#f59e0b'],
            'scoring_rules' => [
                'base_score' => 1000,
                'hint_penalty' => 100,
                'extra_attempt_penalty' => 150,
                'fast_answer_bonus' => 100,
                'streak_bonus' => 200,
                'distance_bands_meters' => [
                    ['max' => 1000, 'score' => 1000],
                    ['max' => 5000, 'score' => 900],
                    ['max' => 20000, 'score' => 700],
                    ['max' => 50000, 'score' => 500],
                    ['max' => 100000, 'score' => 300],
                    ['max' => null, 'score' => 100],
                ],
            ],
        ]);
    }
}
