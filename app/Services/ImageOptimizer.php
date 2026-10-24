<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Stores an image on the public disk as: the untouched original, a resized WebP for display,
 * and a small WebP ("-sm") for thumbnails and phones (used through srcset).
 * Uses GD only (no extra PHP extensions needed).
 */
class ImageOptimizer
{
    public const SMALL_WIDTH = 560;

    public function __construct(
        private int $maxSide = 1600,
        private int $quality = 80,
        private string $disk = 'public',
    ) {}

    /**
     * @param  string  $sourcePath  Absolute path of the source file.
     * @param  string  $dir  Target directory on the disk, e.g. "projects/codelytic".
     * @return array{original: string, webp: string, width: int, height: int}
     */
    public function store(string $sourcePath, string $dir): array
    {
        $name = pathinfo($sourcePath, PATHINFO_FILENAME);
        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        $storage = Storage::disk($this->disk);

        $original = "{$dir}/original/{$name}.{$ext}";
        $storage->put($original, file_get_contents($sourcePath));

        [$webpBytes, $w, $h] = $this->toWebp($sourcePath);
        $webp = "{$dir}/{$name}.webp";
        $storage->put($webp, $webpBytes);
        $this->storeSmall($sourcePath, $webp);

        return ['original' => $original, 'webp' => $webp, 'width' => $w, 'height' => $h];
    }

    /** Converts an image already on the disk (e.g. a Filament upload). Returns the WebP path. */
    public function convertStored(string $path): array
    {
        $abs = Storage::disk($this->disk)->path($path);
        [$bytes, $w, $h] = $this->toWebp($abs);
        $webp = preg_replace('/\.[^.\/]+$/', '', $path).'.webp';
        Storage::disk($this->disk)->put($webp, $bytes);
        $this->storeSmall($abs, $webp);

        return ['webp' => $webp, 'width' => $w, 'height' => $h];
    }

    /** Path of the small variant for a display WebP path. */
    public static function smallPath(string $webpPath): string
    {
        return preg_replace('/\.webp$/i', '-sm.webp', $webpPath);
    }

    /** Creates (or recreates) the small variant next to an existing display WebP. */
    public function storeSmall(string $sourcePath, string $webpPath): void
    {
        [$bytes] = $this->toWebp($sourcePath, self::SMALL_WIDTH, byWidth: true, quality: 75);
        Storage::disk($this->disk)->put(self::smallPath($webpPath), $bytes);
    }

    /** @return array{0: string, 1: int, 2: int} */
    private function toWebp(string $path, ?int $max = null, bool $byWidth = false, ?int $quality = null): array
    {
        $src = @imagecreatefromstring(file_get_contents($path));
        if (! $src) {
            throw new RuntimeException("Unsupported image: {$path}");
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $scale = $byWidth
            ? min(1, ($max ?? $this->maxSide) / $w)
            : min(1, ($max ?? $this->maxSide) / max($w, $h));
        $nw = (int) round($w * $scale);
        $nh = (int) round($h * $scale);

        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        ob_start();
        imagewebp($dst, null, $quality ?? $this->quality);
        $bytes = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return [$bytes, $nw, $nh];
    }
}
