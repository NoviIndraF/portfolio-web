<?php

namespace App\Console\Commands;

use App\Models\Profile;
use App\Models\ProjectImage;
use App\Services\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/** Regenerates the small "-sm" WebP variants for all images (safe to run any time). */
class OptimizeImages extends Command
{
    protected $signature = 'portfolio:images';

    protected $description = 'Create the small WebP variants used for thumbnails and phones';

    public function handle(ImageOptimizer $optimizer): int
    {
        $disk = Storage::disk('public');
        $paths = ProjectImage::pluck('path')->push(Profile::value('photo'))->filter();

        foreach ($paths as $path) {
            if (! $disk->exists($path)) {
                $this->warn("Missing: {$path}");

                continue;
            }
            $optimizer->storeSmall($disk->path($path), $path);
            $this->line("✓ {$path}");
        }
        $this->info("Done: {$paths->count()} images.");

        return self::SUCCESS;
    }
}
