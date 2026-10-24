<?php

namespace App\Models\Concerns;

use App\Services\ImageOptimizer;
use Illuminate\Support\Facades\Storage;

/**
 * Converts uploaded images to WebP whenever an image column changes.
 * The uploaded file is kept as the "original"; the column then points to the WebP.
 * Replaced or deleted files are removed from the public disk.
 */
trait OptimizesImages
{
    /** @return array<string, string> [display column => original column] */
    abstract protected function optimizedImageColumns(): array;

    protected static function bootOptimizesImages(): void
    {
        static::saving(function (self $model) {
            foreach ($model->optimizedImageColumns() as $column => $originalColumn) {
                $model->optimizeImageColumn($column, $originalColumn);
            }
        });

        static::deleted(function (self $model) {
            foreach ($model->optimizedImageColumns() as $column => $originalColumn) {
                $files = array_filter([$model->{$column}, $model->{$originalColumn}]);
                if ($model->{$column}) {
                    $files[] = ImageOptimizer::smallPath($model->{$column});
                }
                Storage::disk('public')->delete($files);
            }
        });
    }

    protected function optimizeImageColumn(string $column, string $originalColumn): void
    {
        // Seeder sets both columns itself; nothing to do then.
        if (! $this->isDirty($column) || $this->isDirty($originalColumn)) {
            return;
        }

        $oldFiles = array_filter([$this->getOriginal($column), $this->getOriginal($originalColumn)]);
        $path = $this->{$column};

        if (blank($path)) {
            $this->{$originalColumn} = null;
        } elseif (! str_ends_with(strtolower($path), '.webp')) {
            $result = app(ImageOptimizer::class)->convertStored($path);
            $this->{$originalColumn} = $path;
            $this->{$column} = $result['webp'];

            if (in_array('width', $this->getFillable(), true)) {
                $this->width = $result['width'];
                $this->height = $result['height'];
            }
        } else {
            $this->{$originalColumn} = null; // a WebP upload is its own original
        }

        $keep = [$this->{$column}, $this->{$originalColumn}];
        $stale = array_values(array_diff($oldFiles, $keep));
        if (($old = $this->getOriginal($column)) && $old !== $this->{$column}) {
            $stale[] = ImageOptimizer::smallPath($old);
        }
        Storage::disk('public')->delete($stale);
    }
}
