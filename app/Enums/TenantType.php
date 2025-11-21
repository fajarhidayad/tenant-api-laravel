<?php

namespace App\Enums;

enum TenantType: string
{
    case FOOD_TRUCK = 'food_truck';
    case BOOTH = 'booth';
    case SPACE_ONLY = 'space_only';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }
}
