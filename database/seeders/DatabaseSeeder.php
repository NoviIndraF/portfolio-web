<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin login for /admin. Created once; the password is never reset by later seeding.
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'novi.indra1453@gmail.com')],
            ['name' => 'Novi Indra Fanani', 'password' => env('ADMIN_PASSWORD', '')],
        );

        // The portfolio seeder REPLACES all content. Protect edits made in the admin panel.
        if (Project::exists() && ! env('SEED_FORCE', false)) {
            $this->command?->warn('Content already exists: skipped PortfolioSeeder. Run with SEED_FORCE=true to reset all content from data/content.json.');

            return;
        }

        $this->call(PortfolioSeeder::class);
    }
}
