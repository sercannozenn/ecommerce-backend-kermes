<?php

namespace App\Enums;

enum DiscountTargetType: string
{
    case PRODUCT  = 'product';
    case CATEGORY = 'category';
    case BRAND    = 'brand';
    case TAG      = 'tag';
    case USER     = 'user';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
