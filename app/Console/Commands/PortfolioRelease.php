<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Publishes a release: bumps VERSION, adds a CHANGELOG entry, saves a content snapshot
 * (storage/app/exports/vX.Y.Z), then commits all changes and tags them vX.Y.Z in git.
 */
class PortfolioRelease extends Command
{
    protected $signature = 'portfolio:release
        {level=patch : patch (1.0.0 → 1.0.1), minor (→ 1.1.0) or major (→ 2.0.0)}
        {--message= : What changed in this release (goes into CHANGELOG.md and the commit)}
        {--force : Skip the confirmation}';

    protected $description = 'Release a new version: bump VERSION, update CHANGELOG, snapshot content, git commit + tag';

    public function handle(): int
    {
        $level = $this->argument('level');
        if (! in_array($level, ['patch', 'minor', 'major'], true)) {
            $this->error('Level must be patch, minor or major.');

            return self::FAILURE;
        }
        if (! $this->git('rev-parse', '--is-inside-work-tree')->successful()) {
            $this->error('This folder is not a git repository. Run "git init" first.');

            return self::FAILURE;
        }

        $current = trim((string) @file_get_contents(base_path('VERSION'))) ?: '0.0.0';
        $version = self::bump($current, $level);
        $tag = 'v'.$version;
        if ($this->git('rev-parse', '-q', '--verify', 'refs/tags/'.$tag)->successful()) {
            $this->error("Tag {$tag} already exists.");

            return self::FAILURE;
        }

        $message = trim((string) $this->option('message')) ?: $this->ask('What changed in this release?');
        if (blank($message)) {
            $this->error('A release message is required.');

            return self::FAILURE;
        }

        $changes = trim($this->git('status', '--short')->output());
        $this->line("Release {$tag} (was v{$current}): {$message}");
        $this->line($changes !== '' ? "Files that will be committed:\n{$changes}" : 'No code changes: only VERSION, CHANGELOG and the content snapshot.');
        if (! $this->option('force') && ! $this->confirm('Commit everything above and tag it '.$tag.'?', true)) {
            return self::FAILURE;
        }

        File::put(base_path('VERSION'), $version.PHP_EOL);
        $this->addChangelogEntry($tag, $message);

        $snapshot = storage_path('app/exports/'.$tag);
        if ($this->call('portfolio:export', ['path' => $snapshot]) !== self::SUCCESS) {
            $this->error('Content snapshot failed; nothing was committed. VERSION and CHANGELOG.md were already updated.');

            return self::FAILURE;
        }

        $commit = $this->git('add', '-A')->successful()
            ? $this->git('commit', '-m', "release: {$tag} - {$message}")
            : null;
        if (! $commit?->successful()) {
            $this->error('git commit failed: '.trim($commit?->errorOutput() ?? 'git add failed'));

            return self::FAILURE;
        }
        $this->git('tag', '-a', $tag, '-m', $message);

        $this->info("Released {$tag}. Content snapshot: {$snapshot}");

        return self::SUCCESS;
    }

    /** Semantic version bump: "1.2.3" + "minor" → "1.3.0". */
    public static function bump(string $version, string $level): string
    {
        [$major, $minor, $patch] = array_map('intval', array_pad(explode('.', ltrim($version, 'v')), 3, 0));

        return match ($level) {
            'major' => ($major + 1).'.0.0',
            'minor' => "{$major}.".($minor + 1).'.0',
            default => "{$major}.{$minor}.".($patch + 1),
        };
    }

    private function addChangelogEntry(string $tag, string $message): void
    {
        $path = base_path('CHANGELOG.md');
        $entry = "## {$tag} - ".now()->toDateString()."\n- {$message}\n\n";
        $text = File::exists($path) ? File::get($path) : "# Changelog\n\n";
        // New entries go above the previous release.
        $at = strpos($text, '## ');
        File::put($path, $at === false ? rtrim($text)."\n\n".$entry : substr_replace($text, $entry, $at, 0));
    }

    private function git(string ...$args): ProcessResult
    {
        return Process::path(base_path())->run(['git', ...$args]);
    }
}
