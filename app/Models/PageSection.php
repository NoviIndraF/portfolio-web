<?php

namespace App\Models;

use App\Models\Concerns\HasVisibility;
use Illuminate\Database\Eloquent\Model;

/**
 * A section of the home page (below the hero). Built-in sections can be renamed,
 * reordered and hidden; custom "text" and "timeline" sections can also be added and deleted.
 */
class PageSection extends Model
{
    use HasVisibility;

    public const BUILT_IN = ['projects', 'skills', 'about', 'contact'];

    public const TYPES = [
        'projects' => 'Featured projects (built-in)',
        'skills' => 'Skills (built-in)',
        'about' => 'About (built-in)',
        'contact' => 'Contact (built-in)',
        'text' => 'Text block',
        'timeline' => 'Timeline (experience, education, certificates…)',
    ];

    protected $fillable = [
        'type', 'anchor', 'nav_label', 'eyebrow', 'title', 'intro', 'body', 'items', 'sort_order',
    ];

    protected static function booted(): void
    {
        // Built-in sections can be hidden, never deleted.
        static::deleting(fn (PageSection $s) => ! $s->isBuiltIn());
    }

    protected function casts(): array
    {
        return ['items' => 'array', 'sort_order' => 'integer'];
    }

    public function isBuiltIn(): bool
    {
        return in_array($this->type, self::BUILT_IN, true);
    }

    /** Field value, or the default text from lang/<locale>/site.php for built-in sections. */
    public function text(string $field): ?string
    {
        if (filled($this->{$field})) {
            return $this->{$field};
        }
        if (! $this->isBuiltIn()) {
            return null;
        }
        $key = "site.{$this->type}.{$field}";

        return __($key) !== $key ? __($key) : null;
    }
}
