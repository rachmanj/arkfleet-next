<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasSelectableScope
{
    public function scopeSelectable(Builder $query): Builder
    {
        return $query->where('is_selectable', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
