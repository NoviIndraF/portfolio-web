<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmin();

        // The portfolio seeder REPLACES all content. Protect edits made in the admin panel.
        if (Project::exists() && ! env('SEED_FORCE', false)) {
            $this->command?->warn('Content already exists: skipped PortfolioSeeder. Run with SEED_FORCE=true to reset all content from data/content.json.');

            return;
        }

        $this->call(PortfolioSeeder::class);
    }

    /**
     * Admin login for /admin. Created once; later seeding never resets the password.
     * Uses ADMIN_PASSWORD from .env, or generates a random one and prints it once.
     */
    private function seedAdmin(): void
    {
        $email = env('ADMIN_EMAIL') ?: 'admin@example.com';
        if (User::where('email', $email)->exists()) {
            return;
        }

        $password = env('ADMIN_PASSWORD') ?: Str::password(16, symbols: false);
        User::create(['name' => env('ADMIN_NAME', 'Admin'), 'email' => $email, 'password' => $password]);

        if (! env('ADMIN_PASSWORD')) {
            $this->command?->warn("Admin account created: {$email} / {$password}");
            $this->command?->warn('Save this password now, then change it in /admin/profile.');
        }
    }
}
