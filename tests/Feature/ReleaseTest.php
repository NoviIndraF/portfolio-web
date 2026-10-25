<?php

namespace Tests\Feature;

use App\Console\Commands\PortfolioRelease;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_version_bump_follows_semver(): void
    {
        $this->assertSame('1.0.1', PortfolioRelease::bump('1.0.0', 'patch'));
        $this->assertSame('1.3.0', PortfolioRelease::bump('v1.2.9', 'minor'));
        $this->assertSame('2.0.0', PortfolioRelease::bump('1.4.2', 'major'));
        $this->assertSame('0.0.1', PortfolioRelease::bump('', 'patch'));
    }

    public function test_release_rejects_unknown_level_before_touching_anything(): void
    {
        $version = file_get_contents(base_path('VERSION'));

        $this->artisan('portfolio:release', ['level' => 'huge', '--message' => 'x', '--force' => true])
            ->expectsOutputToContain('Level must be patch, minor or major.')
            ->assertFailed();

        $this->assertSame($version, file_get_contents(base_path('VERSION')));
    }

    public function test_admin_footer_shows_release_version(): void
    {
        config(['app.version' => '9.8.7']);

        $this->actingAs(User::factory()->create())->get('/admin')->assertOk()->assertSee('v9.8.7');
    }
}
