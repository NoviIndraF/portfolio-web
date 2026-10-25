<?php

namespace Tests\Feature;

use App\Filament\Resources\PageSections\PageSectionResource;
use App\Models\PageSection;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortfolioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->withoutVite();

        Setting::create(['site_title' => 'Test Portfolio', 'toggle_3d' => true]);
        Profile::create([
            'name' => 'Novi Indra Fanani',
            'tagline' => 'Mobile App Developer | Flutter & Android',
            'bio' => 'Builds apps. Writes clean code.',
            'stats' => [['label' => 'Apps built', 'value' => '10+']],
            'email' => 'novi@example.com',
            'github' => 'https://github.com/example',
        ]);
    }

    private function project(array $attrs = []): Project
    {
        return Project::create($attrs + [
            'title' => 'Codelytic',
            'slug' => 'codelytic',
            'short_description' => 'Online learning app.',
            'impact_line' => 'Problem-based learning on mobile and web.',
            'type' => 'mobile+web',
            'accent_color' => '#4C8DFF',
            'scene_type' => 'learning',
            'sort_order' => 1,
            'is_published' => true,
        ]);
    }

    public function test_home_shows_profile_and_published_projects_only(): void
    {
        $this->project();
        $this->project(['title' => 'Secret Draft', 'slug' => 'draft', 'is_published' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Mobile App Developer')
            ->assertSee('Codelytic')
            ->assertSee('Problem-based learning on mobile and web.')
            ->assertDontSee('Secret Draft')
            ->assertSee('<meta property="og:title"', false);
    }

    public function test_download_cv_button_is_hidden_without_a_cv_and_shown_with_one(): void
    {
        $this->get('/')->assertDontSee('Download CV');

        Storage::disk('public')->put('profile/cv.pdf', '%PDF-1.4 test');
        Profile::first()->update(['cv_pdf' => 'profile/cv.pdf']);
        $this->app->forgetScopedInstances(); // a real server starts fresh on every request

        $this->get('/')->assertSee('Download CV');
    }

    public function test_project_page_hides_links_section_when_there_are_no_links(): void
    {
        $project = $this->project();
        $project->features()->create(['title' => 'Quiz', 'sort_order' => 1]);

        $this->get('/projects/codelytic')
            ->assertOk()
            ->assertSee('Key features')
            ->assertSee('Quiz')
            ->assertDontSee('id="links-title"', false);

        $project->links()->create(['label' => 'Web', 'url' => 'https://example.com', 'sort_order' => 1]);
        $this->get('/projects/codelytic')->assertSee('id="links-title"', false);
    }

    public function test_project_with_recognition_shows_hki_badge(): void
    {
        $this->project([
            'recognition' => ['type' => 'Software copyright registration (HKI)', 'holder' => 'Universitas Negeri Malang', 'published' => '16 November 2020'],
        ]);

        $this->get('/projects/codelytic')
            ->assertSee('Registered Software Copyright (HKI)')
            ->assertSee('Universitas Negeri Malang');
    }

    public function test_draft_project_is_404_for_visitors_but_visible_to_admin(): void
    {
        $this->project(['is_published' => false]);

        $this->get('/projects/codelytic')->assertNotFound()->assertSee("This page doesn't exist", false);
        $this->actingAs(User::factory()->create())->get('/projects/codelytic')->assertOk();
    }

    public function test_admin_requires_login_and_works_for_admin(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');

        $admin = User::factory()->create();
        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/projects')->assertOk();
        $this->actingAs($admin)->get('/admin/portfolio-profile')->assertOk();
        $this->actingAs($admin)->get('/admin/settings')->assertOk();
    }

    public function test_admin_menu_has_experience_and_education_shortcuts_when_sections_exist(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)->get('/admin')->assertOk()->assertDontSee('/admin/page-sections/', false);

        $experience = PageSection::create(['type' => 'timeline', 'anchor' => 'experience', 'title' => 'Where I have worked', 'items' => []]);
        $education = PageSection::create(['type' => 'timeline', 'anchor' => 'education', 'title' => 'Learning and recognition', 'items' => []]);

        $this->actingAs($admin)->get('/admin')->assertOk()
            ->assertSee(PageSectionResource::getUrl('edit', ['record' => $experience]), false)
            ->assertSee(PageSectionResource::getUrl('edit', ['record' => $education]), false);
    }

    public function test_sitemap_and_robots(): void
    {
        $this->project();
        $this->project(['title' => 'Draft', 'slug' => 'draft', 'is_published' => false]);

        $this->get('/sitemap.xml')->assertOk()->assertSee('/projects/codelytic')->assertDontSee('/projects/draft');
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('sitemap.xml');
    }

    public function test_hero_browser_rotates_through_web_images_only(): void
    {
        $project = $this->project();
        $this->get('/')->assertOk()->assertDontSee('data-web-screens', false);

        foreach (['web-1' => 'web', 'web-2' => 'web', 'phone' => 'screenshot'] as $name => $kind) {
            $path = Storage::disk('public')->putFileAs('projects/codelytic', UploadedFile::fake()->image("$name.png", 1600, 800), "$name.png");
            $project->images()->create(['path' => $path, 'kind' => $kind, 'sort_order' => 1]);
        }

        $html = $this->get('/')->assertOk()->getContent();
        preg_match('/data-web-screens="([^"]+)"/', $html, $match);
        $urls = json_decode(html_entity_decode($match[1] ?? ''), true);

        $this->assertSame([
            Storage::disk('public')->url('projects/codelytic/web-1.webp'),
            Storage::disk('public')->url('projects/codelytic/web-2.webp'),
        ], $urls);
    }

    public function test_uploaded_image_is_converted_to_webp_with_small_variant_and_cleaned_up(): void
    {
        $project = $this->project();
        $png = UploadedFile::fake()->image('screen.png', 1080, 2280);
        $path = Storage::disk('public')->putFileAs('projects/codelytic', $png, 'screen.png');

        $image = $project->images()->create(['path' => $path, 'kind' => 'screenshot', 'sort_order' => 1]);

        $this->assertSame('projects/codelytic/screen.webp', $image->path);
        $this->assertSame('projects/codelytic/screen.png', $image->original_path);
        Storage::disk('public')->assertExists(['projects/codelytic/screen.webp', 'projects/codelytic/screen-sm.webp', 'projects/codelytic/screen.png']);
        $this->assertSame(758, $image->width); // longest side scaled to 1600

        $image->delete();
        Storage::disk('public')->assertMissing(['projects/codelytic/screen.webp', 'projects/codelytic/screen-sm.webp', 'projects/codelytic/screen.png']);
    }

    public function test_structured_data_is_valid_json(): void
    {
        $html = $this->get('/')->getContent();
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $data = json_decode(trim($m[1] ?? ''), true);

        $this->assertSame('https://schema.org', $data['@context'] ?? null);
        $this->assertSame('Novi Indra Fanani', $data['name'] ?? null);
    }

    public function test_3d_toggle_is_exposed_to_the_page(): void
    {
        $this->get('/')->assertSee('data-3d="on"', false);
        Setting::first()->update(['toggle_3d' => false]);
        $this->app->forgetScopedInstances();
        $this->get('/')->assertSee('data-3d="off"', false);
    }

    public function test_seeder_does_not_overwrite_existing_content(): void
    {
        $this->project(['title' => 'Edited in admin']);

        $this->artisan('db:seed')->assertSuccessful();

        $this->assertSame(1, Project::count());
        $this->assertSame('Edited in admin', Project::first()->title);
    }
}
