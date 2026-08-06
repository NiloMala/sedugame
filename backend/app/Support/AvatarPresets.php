<?php

namespace App\Support;

/**
 * Personagens de avatar pré-definidos — nunca foto real (ver migration
 * 2026_08_07_000004). Códigos batem com nomes de ícone do lucide-react
 * (mesmo pacote que o frontend já usa), pra não depender de arquivo de
 * imagem novo: o frontend renderiza cada preset como um ícone estilizado.
 */
class AvatarPresets
{
    public const OPTIONS = [
        ['code' => 'compass', 'label' => 'Bússola', 'color' => '#0EA5E9'],
        ['code' => 'map', 'label' => 'Mapa', 'color' => '#16A34A'],
        ['code' => 'binoculars', 'label' => 'Binóculo', 'color' => '#D97706'],
        ['code' => 'telescope', 'label' => 'Luneta', 'color' => '#7C3AED'],
        ['code' => 'mountain', 'label' => 'Montanha', 'color' => '#059669'],
        ['code' => 'backpack', 'label' => 'Mochila', 'color' => '#DC2626'],
    ];

    public static function codes(): array
    {
        return array_column(self::OPTIONS, 'code');
    }

    public static function isValid(string $code): bool
    {
        return in_array($code, self::codes(), true);
    }
}
