<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/** Content that can be hidden on the website without deleting it (is_visible column). */
trait HasVisibility
{
    public function initializeHasVisibility(): void
    {
        $this->mergeFillable(['is_visible']);
        $this->mergeCasts(['is_visible' => 'boolean']);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('is_visible'), true);
    }
}
