<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];


    protected $appends = ['value_url'];

    public function getValueUrlAttribute(): ?string
    {
        if ($this->isFileSetting()) {
            return Storage::url($this->value);
        }

        return null;
    }

    protected function isFileSetting(): bool
    {
        return in_array($this->key, ['logo', 'favicon']) && $this->value;
    }
}
