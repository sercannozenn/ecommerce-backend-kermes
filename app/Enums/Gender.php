<?php

namespace App\Enums;

enum Gender: string
{
    case KADIN         = 'kadin';
    case ERKEK         = 'erkek';
    case UNISEX        = 'unisex';
    case KIZ_COCUK     = 'kiz-cocuk';
    case ERKEK_COCUK   = 'erkek-cocuk';
    case UNISEX_COCUK  = 'unisex-cocuk';

    private const LABELS_TR = [
        'kadin'        => 'Kadın',
        'erkek'        => 'Erkek',
        'unisex'       => 'Unisex',
        'kiz-cocuk'    => 'Kız Çocuk',
        'erkek-cocuk'  => 'Erkek Çocuk',
        'unisex-cocuk' => 'Unisex Çocuk',
    ];

    private const LABELS_EN = [
        'kadin'        => 'Women',
        'erkek'        => 'Men',
        'unisex'       => 'Unisex',
        'kiz-cocuk'    => 'Girl',
        'erkek-cocuk'  => 'Boy',
        'unisex-cocuk' => 'Unisex Kid',
    ];

    /**
     * @param  'tr'|'en'  $lang
     * @return string
     */
    public function label(string $lang = 'tr'): string
    {
        return match ($lang) {
            'en'    => self::LABELS_EN[$this->value],
            default => self::LABELS_TR[$this->value],
        };
    }

    /**
     * @param  'tr'|'en'  $lang
     * @return array<int, array{value:string,label:string}>
     */
    public static function options(string $lang = 'tr'): array
    {
        $map = $lang === 'en' ? self::LABELS_EN : self::LABELS_TR;
        $opts = [];
        foreach (self::cases() as $g) {
            $opts[] = [
                'value' => $g->value,
                'label' => $map[$g->value],
            ];
        }
        return $opts;
    }
}
