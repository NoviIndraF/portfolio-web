<?php

namespace App\Console\Commands;

use Database\Seeders\PortfolioSeeder;
use Illuminate\Console\Command;

/**
 * Imports a folder made by `portfolio:export` (or the original data/ + seed/ package),
 * REPLACING all current content. Admin users are not touched.
 */
class ImportContent extends Command
{
    protected $signature = 'portfolio:import {path? : Folder with content.json (default: this project, data/content.json)} {--force : Skip the confirmation}';

    protected $description = 'Replace all portfolio content with an export folder (backup restore / move to MySQL)';

    public function handle(): int
    {
        $path = $this->argument('path') ? realpath($this->argument('path')) : base_path();
        if (! $path || ! (is_file("{$path}/content.json") || is_file("{$path}/data/content.json"))) {
            $this->error('No content.json found in '.($this->argument('path') ?? base_path()));

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("This REPLACES all projects, skills, sections, profile and settings in the '".config('database.default')."' database with {$path}. Continue?")) {
            return self::FAILURE;
        }

        $seeder = $this->laravel->make(PortfolioSeeder::class);
        $seeder->root = $path;
        $seeder->setContainer($this->laravel)->setCommand($this)->__invoke();

        $this->call('portfolio:images');
        $this->info('Import finished.');

        return self::SUCCESS;
    }
}
