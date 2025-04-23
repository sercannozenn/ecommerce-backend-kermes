<?php

namespace App\Enums;

enum DiscountAmountType: string
{
    case PRODUCT  = 'product';
    case CATEGORY = 'category';
    case BRAND    = 'brand';
    case TAG      = 'tag';
    case USER     = 'user';

    case PERCENTAGE = 'percentage';
    case FIXED      = 'fixed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
