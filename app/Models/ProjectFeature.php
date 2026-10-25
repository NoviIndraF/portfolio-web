<?php

namespace App\Models;

use App\Models\Concerns\HasVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFeature extends Model
{
    use HasVisibility;

    protected $fillable = ['project_id', 'title', 'description', 'icon', 'project_image_id', 'sort_order'];

    /** Screenshot that shows this feature (optional). */
    public function image(): BelongsTo
    {
        return $this->belongsTo(ProjectImage::class, 'project_image_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
