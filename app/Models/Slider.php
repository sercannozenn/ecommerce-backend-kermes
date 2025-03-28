<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method create(array $data)
 * @method orderBy(string $string, string $string1)
 * @property string $path
 * @property boolean $is_active
 */
class Slider extends Model
{
    protected $fillable = [
        'path',
        'row_1_text',
        'row_1_color',
        'row_1_css',
        'row_2_text',
        'row_2_color',
        'row_2_css',
        'button_text',
        'button_url',
        'button_target',
        'button_color',
        'button_css',
        'is_active'
    ];

    protected $appends = ['path_url'];

    public function getPathUrlAttribute(): ?string
    {
        return $this->path ? asset('storage/' . $this->path) : null;
    }
}
