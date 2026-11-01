<?php

namespace Tests\Feature;

use App\Filament\Resources\PageSections\Pages\CreatePageSection;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Http\Middleware\SetLocale;
use App\Models\PageSection;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Setting;
use App\Models\SkillGroup;
use App\Models\User;
use App\Support\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CustomizableContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->withoutVite();
        Setting::create(['site_title' => 'Test', 'toggle_3d' => true]);
        Profile::create(['name' => 'Novi Indra Fanani', 'tagline' => 'Mobile App Developer', 'email' => 'novi@example.com']);
    }

    private function project(array $attrs = []): Project
    {
        static $n = 0;
        $n++;

        return Project::create($attrs + [
            'title' => "Project {$n}",
            'slug' => "project-{$n}",
            'short_description' => 'Description.',
            'type' => 'mobile',
            'accent_color' => '#4C8DFF',
            'scene_type' => 'generic',
            'sort_order' => $n,
            'is_published' => true,
            'is_featured' => true,
        ]);
    }

    public function test_hidden_features_tech_links_and_images_are_not_shown(): void
    {
        $p = $this->project(['slug' => 'app']);
        $p->features()->create(['title' => 'Visible feature', 'sort_order' => 1]);
        $p->features()->create(['title' => 'Hidden feature', 'is_visible' => false, 'sort_order' => 2]);
        $p->tech()->create(['name' => 'Kotlin', 'sort_order' => 1]);
        $p->tech()->create(['name' => 'SecretLib', 'is_visible' => false, 'sort_order' => 2]);
        $p->links()->create(['label' => 'Hidden link', 'url' => 'https://example.com/x', 'is_visible' => false, 'sort_order' => 1]);

        $this->get('/projects/app')
            ->assertOk()
            ->assertSee('Visible feature')->assertDontSee('Hidden feature')
            ->assertSee('Kotlin')->assertDontSee('SecretLib')
            ->assertDontSee('Hidden link')->assertDontSee('id="links-title"', false);
    }

    public function test_hidden_skill_groups_and_skills_are_not_shown(): void
    {
        $g = SkillGroup::create(['name' => 'Mobile', 'sort_order' => 1]);
        $g->skills()->create(['name' => 'Flutter', 'sort_order' => 1]);
        $g->skills()->create(['name' => 'Hidden skill', 'is_visible' => false, 'sort_order' => 2]);
        $hidden = SkillGroup::create(['name' => 'Hidden group', 'is_visible' => false, 'sort_order' => 2]);
        $hidden->skills()->create(['name' => 'Cobol', 'sort_order' => 1]);

        $this->get('/')->assertSee('Flutter')->assertDontSee('Hidden skill')->assertDontSee('Hidden group')->assertDontSee('Cobol');
    }

    public function test_hero_3d_gets_icons_of_visible_skills_once_each(): void
    {
        $mobile = SkillGroup::create(['name' => 'Mobile', 'sort_order' => 1]);
        $mobile->skills()->create(['name' => 'Flutter', 'sort_order' => 1]);
        $mobile->skills()->create(['name' => 'Clean Architecture', 'sort_order' => 2]);
        $mobile->skills()->create(['name' => 'Firebase', 'sort_order' => 3]);
        $mobile->skills()->create(['name' => 'Cloud Firestore', 'sort_order' => 4]);
        $mobile->skills()->create(['name' => 'Laravel', 'is_visible' => false, 'sort_order' => 5]);
        $devops = SkillGroup::create(['name' => 'DevOps', 'sort_order' => 2]);
        $devops->skills()->create(['name' => 'C#', 'sort_order' => 1]);
        $devops->skills()->create(['name' => 'GitHub', 'sort_order' => 2]);

        $response = $this->get('/');
        $icons = json_decode(html_entity_decode(str($response->getContent())->match('/data-tech-icons="([^"]+)"/')->toString()), true);

        // In turns per group, one tile per icon (Firestore shares Firebase's), hidden skills left out, C# as .NET.
        $this->assertSame(['/icons/simple/flutter.svg', '/icons/simple/dotnet.svg', '/icons/simple/firebase.svg', '/icons/simple/github.svg'], $icons);
        // Small screens get the same stack as a row of icons with names for screen readers.
        $response->assertSeeInOrder(['aria-label="Tech stack"', 'sr-only">Flutter<', 'sr-only">C#<', 'sr-only">Firebase<', 'sr-only">GitHub<'], false);
    }

    public function test_home_shows_featured_projects_and_all_projects_page_shows_every_published_one(): void
    {
        $this->project(['title' => 'Featured One']);
        $this->project(['title' => 'Not Featured', 'is_featured' => false, 'type' => 'web']);
        $this->project(['title' => 'Draft Project', 'is_published' => false]);

        $this->get('/')->assertSee('Featured One')->assertDontSee('Not Featured')->assertSee('See all 2 projects');
        $this->get('/projects')
            ->assertOk()
            ->assertSee('Featured One')->assertSee('Not Featured')->assertDontSee('Draft Project')
            ->assertSee('data-filter-value="web"', false);
    }

    public function test_sections_follow_database_order_visibility_and_texts(): void
    {
        $this->project(['title' => 'Some App']);
        PageSection::where('type', 'skills')->first()->update(['is_visible' => false]);
        PageSection::where('type', 'contact')->first()->update(['title' => 'Say hello', 'sort_order' => 0]);

        $html = $this->get('/')->assertOk()->assertSee('Say hello')->assertDontSee('id="skills"', false)->getContent();

        $this->assertLessThan(strpos($html, 'id="projects"'), strpos($html, 'id="contact"'), 'contact should come before projects');
        $this->assertStringNotContainsString('href="#skills"', $html, 'hidden section must leave the menu');
    }

    public function test_custom_timeline_and_text_sections(): void
    {
        $timeline = new PageSection(['type' => 'timeline', 'anchor' => 'experience', 'nav_label' => 'Experience', 'title' => 'Work experience', 'sort_order' => 5, 'items' => [
            ['period' => '2022 – Now', 'title' => 'Mobile Developer', 'subtitle' => 'Acme', 'description' => "- Built the POS app\n- Cut crashes by 80%", 'is_visible' => true],
            ['period' => '2020', 'title' => 'Hidden job', 'is_visible' => false],
        ]]);
        $timeline->save();
        (new PageSection(['type' => 'text', 'anchor' => 'note', 'title' => 'A note', 'sort_order' => 6, 'body' => '<p>Hello <strong>HR</strong></p><script>alert(1)</script>']))->save();

        $this->get('/')
            ->assertSee('Work experience')->assertSee('Mobile Developer')->assertSee('Acme')->assertDontSee('Hidden job')
            ->assertSeeInOrder(['<li', 'Built the POS app</li>', '<li', 'Cut crashes by 80%</li>'], false)
            ->assertSee('href="#experience"', false)
            ->assertSee('<strong>HR</strong>', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_logo_strip_shows_visible_logos_and_names(): void
    {
        $logo = Storage::disk('public')->putFileAs('sections/logos', UploadedFile::fake()->image('acme.png', 200, 80), 'acme.png');
        (new PageSection(['type' => 'logos', 'anchor' => 'clients', 'title' => 'Companies I worked with', 'sort_order' => 7, 'items' => [
            ['name' => 'Acme Corp', 'logo' => $logo, 'url' => 'https://acme.test', 'is_visible' => true],
            ['name' => 'Text Only Ltd', 'is_visible' => true],
            ['name' => 'Secret Client', 'is_visible' => false],
        ]]))->save();

        $this->get('/')
            ->assertSee('Companies I worked with')
            ->assertSee('Acme Corp')->assertSee('Text Only Ltd')->assertDontSee('Secret Client')
            ->assertSee(Storage::disk('public')->url($logo), false)
            ->assertSee('href="https://acme.test" target="_blank" rel="noopener" class="underline">Acme Corp</a>', false);
    }

    public function test_project_page_describes_the_app_for_search_engines(): void
    {
        $this->project(['title' => 'Schema App', 'slug' => 'schema-app', 'year' => '2024']);

        $this->get('/projects/schema-app')
            ->assertSee('"@type":"SoftwareApplication"', false)
            ->assertSee('"name":"Schema App"', false)
            ->assertSee('"dateCreated":"2024"', false);
    }

    public function test_unknown_page_shows_the_styled_not_found_page(): void
    {
        $this->get('/projects/does-not-exist')->assertNotFound()->assertSee(__('site.not_found.title'));
    }

    public function test_availability_badge_follows_the_admin_toggle(): void
    {
        Profile::query()->first()->update(['availability' => 'Open to work · Remote', 'show_availability' => true]);
        $this->get('/')->assertSee('Open to work · Remote');

        Profile::query()->first()->update(['show_availability' => false]);
        app()->forgetInstance(Site::class); // the site object caches the profile for one request
        $this->get('/')->assertDontSee('Open to work · Remote');
    }

    public function test_project_page_shows_the_case_study_in_order(): void
    {
        $this->project(['slug' => 'case', 'problem' => 'Paper forms', 'role' => 'Lead mobile dev', 'solution' => 'A Flutter app', 'outcome' => 'Saved 5 hours a week']);

        $this->get('/projects/case')->assertSeeInOrder(['Problem', 'Paper forms', 'My role', 'Lead mobile dev', 'Solution', 'A Flutter app', 'Outcome', 'Saved 5 hours a week']);
    }

    public function test_testimonials_section_shows_visible_recommendations(): void
    {
        (new PageSection(['type' => 'testimonials', 'anchor' => 'kind-words', 'title' => 'Kind words', 'sort_order' => 8, 'items' => [
            ['quote' => 'Ships clean, tested code.', 'name' => 'Rina', 'role' => 'Engineering Manager', 'url' => 'https://linkedin.test/rina', 'is_visible' => true],
            ['quote' => 'Not public yet.', 'name' => 'Hidden Person', 'is_visible' => false],
        ]]))->save();

        $this->get('/')
            ->assertSee('Kind words')->assertSee('Ships clean, tested code.')->assertSee('Engineering Manager')
            ->assertSee('href="https://linkedin.test/rina"', false)
            ->assertDontSee('Not public yet.')->assertDontSee('Hidden Person');
    }

    public function test_indonesian_texts_have_the_same_keys_as_english(): void
    {
        $this->assertSame(array_keys(Arr::dot(require lang_path('en/site.php'))), array_keys(Arr::dot(require lang_path('id/site.php'))));
    }

    public function test_language_switch_remembers_the_choice_over_the_browser_language(): void
    {
        $this->get('/lang/id')->assertRedirect()->assertCookie('locale', 'id');
        $this->withCookie('locale', 'id')->get('/')->assertSee('Dibangun dengan Laravel')->assertSee('lang="id"', false);
        $this->withCookie('locale', 'en')->withHeader('Accept-Language', 'id-ID,id;q=0.9')->get('/')->assertSee('Built with Laravel');
    }

    public function test_without_a_choice_the_site_language_is_used_not_the_browser_one(): void
    {
        // Admin texts are in one language; following the browser would mix languages on the page.
        $this->withHeader('Accept-Language', 'id-ID,id;q=0.9')->get('/projects')->assertSee('All projects')->assertSee('content="en_US"', false);
    }

    public function test_chosen_language_sets_og_locale_and_translates_timeline_links(): void
    {
        (new PageSection(['type' => 'timeline', 'anchor' => 'certs', 'title' => 'Certificates', 'sort_order' => 4, 'items' => [
            ['title' => 'Flutter Expert', 'url' => 'https://dicoding.test/cert'],
        ]]))->save();

        $this->withCookie('locale', 'id')->get('/')->assertSee('content="id_ID"', false)->assertSee('Lihat');
    }

    public function test_language_switch_only_redirects_back_to_this_site(): void
    {
        $this->withHeader('Referer', 'https://evil.example/phish')->get('/lang/id')->assertRedirect(route('home'));
        $this->withHeader('Referer', url('/projects'))->get('/lang/id')->assertRedirect(url('/projects'));
    }

    public function test_admin_requests_are_not_touched_by_the_site_language(): void
    {
        $livewire = collect(app('router')->getRoutes())->first(fn ($r) => str_ends_with($r->uri(), '/update') && str_starts_with($r->uri(), 'livewire'));

        $this->assertNotNull($livewire);
        $this->assertNotContains(SetLocale::class, app('router')->gatherRouteMiddleware($livewire));
    }

    public function test_empty_lists_are_left_out_of_the_page_and_the_menu_numbers(): void
    {
        (new PageSection(['type' => 'text', 'anchor' => 'one', 'nav_label' => 'One', 'title' => 'One', 'sort_order' => 101]))->save();
        (new PageSection(['type' => 'testimonials', 'anchor' => 'kind', 'nav_label' => 'Kind words', 'title' => 'Kind words', 'sort_order' => 102, 'items' => [
            ['quote' => 'Hidden quote', 'name' => 'Someone', 'is_visible' => false],
        ]]))->save();
        (new PageSection(['type' => 'text', 'anchor' => 'two', 'nav_label' => 'Two', 'title' => 'Two', 'sort_order' => 103]))->save();

        $html = $this->get('/')->assertDontSee('Kind words')->getContent();
        $number = fn (string $label) => (int) str($html)->match('/>(\d{2})<\/span> '.$label.'</')->toString();

        // The hidden-only testimonials section takes no number: "Two" follows "One" directly.
        $this->assertSame($number('One') + 1, $number('Two'));
    }

    public function test_stats_band_has_one_column_per_stat(): void
    {
        Profile::query()->first()->update(['stats' => [
            ['value' => '4+', 'label' => 'Years'], ['value' => '10+', 'label' => 'Apps'],
            ['value' => '2', 'label' => 'Certs'], ['value' => '3', 'label' => 'Awards'],
        ]]);

        $this->get('/')->assertSee('grid-template-columns: repeat(4, minmax(0, 1fr))', false);
    }

    public function test_language_switch_off_keeps_the_default_language(): void
    {
        Setting::query()->first()->update(['show_language_switch' => false]);

        $this->withCookie('locale', 'id')->get('/')->assertSee('Built with Laravel')->assertDontSee('href="'.route('lang', 'id').'"', false);
    }

    public function test_analytics_script_loads_only_when_configured(): void
    {
        $this->get('/')->assertDontSee('data-website-id', false);

        Setting::query()->first()->update(['analytics_script_url' => 'https://stats.test/script.js', 'analytics_site_id' => 'abc-123']);
        app()->forgetInstance(Site::class);

        $this->get('/')->assertSee('<script defer src="https://stats.test/script.js" data-website-id="abc-123"', false);
    }

    public function test_built_in_sections_cannot_be_deleted(): void
    {
        PageSection::where('type', 'about')->first()->delete();
        $this->assertTrue(PageSection::where('type', 'about')->exists());
    }

    public function test_free_text_project_type_gets_a_readable_label(): void
    {
        $this->project(['slug' => 'game', 'type' => 'game', 'year' => '2024']);
        $this->get('/projects/game')->assertSee('Game · 2024');
    }

    public function test_duplicate_copies_relations_as_hidden_draft(): void
    {
        $p = $this->project(['title' => 'Original', 'slug' => 'original']);
        $p->features()->create(['title' => 'Feature A', 'sort_order' => 1]);
        $p->tech()->create(['name' => 'Flutter', 'sort_order' => 1]);
        $this->actingAs(User::factory()->create());

        Livewire::test(ListProjects::class)->callTableAction('replicate', $p);

        $copy = Project::where('slug', 'original-copy')->firstOrFail();
        $this->assertFalse($copy->is_published);
        $this->assertSame(['Feature A'], $copy->features()->pluck('title')->all());
        $this->assertSame(['Flutter'], $copy->tech()->pluck('name')->all());
    }

    public function test_admin_can_create_a_timeline_section(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreatePageSection::class)
            ->fillForm([
                'type' => 'timeline',
                'title' => 'Experience',
                'anchor' => 'experience',
                'nav_label' => 'Experience',
                'is_visible' => true,
                'items' => [['period' => '2023', 'title' => 'Flutter Developer', 'subtitle' => 'Startup', 'description' => null, 'url' => null, 'link_label' => null, 'is_visible' => true]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $section = PageSection::where('anchor', 'experience')->firstOrFail();
        $this->assertSame('timeline', $section->type);
        $this->get('/')->assertSee('Flutter Developer')->assertSee('href="#experience"', false);
    }

    public function test_admin_can_save_project_with_hidden_tech(): void
    {
        $p = $this->project(['slug' => 'edit-me']);
        $p->tech()->create(['name' => 'Flutter', 'sort_order' => 1]);
        $p->tech()->create(['name' => 'Dart', 'sort_order' => 2]);
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(EditProject::class, ['record' => $p->slug]);
        $tech = $component->get('data.tech');
        $key = array_key_last($tech);
        $tech[$key]['is_visible'] = false;

        $component->set('data.tech', $tech)->set('data.year', '2025')->call('save')->assertHasNoFormErrors();

        $p->refresh();
        $this->assertSame('2025', $p->year);
        $this->assertSame(['Flutter'], $p->tech()->visible()->pluck('name')->all());
    }

    public function test_skills_link_to_projects_whose_technologies_match(): void
    {
        $app = $this->project(['title' => 'Flutter App']);
        $app->tech()->create(['name' => 'Flutter Bloc', 'sort_order' => 1]);
        $group = SkillGroup::create(['name' => 'Mobile', 'sort_order' => 1]);
        $group->skills()->create(['name' => 'Flutter', 'sort_order' => 1]);
        $group->skills()->create(['name' => 'Cobol', 'sort_order' => 2]);

        $html = $this->get('/')->assertOk()->getContent();

        preg_match('/data-skill="Flutter" data-skill-projects="([^"]+)"/', $html, $m);
        $linked = json_decode(html_entity_decode($m[1] ?? '[]'), true);
        $this->assertSame(['Flutter App'], array_column($linked, 'title'));
        $this->assertStringNotContainsString('data-skill="Cobol"', $html, 'a skill without projects stays a plain chip');
    }

    public function test_feature_linked_to_a_screen_points_at_its_position_among_visible_screenshots(): void
    {
        $p = $this->project(['slug' => 'screens']);
        $p->images()->create(['path' => 'projects/screens/a.webp', 'kind' => 'screenshot', 'is_visible' => false, 'sort_order' => 1]);
        $p->images()->create(['path' => 'projects/screens/b.webp', 'kind' => 'screenshot', 'sort_order' => 2]);
        $target = $p->images()->create(['path' => 'projects/screens/c.webp', 'kind' => 'screenshot', 'sort_order' => 3]);
        $p->features()->create(['title' => 'Quiz', 'project_image_id' => $target->id, 'sort_order' => 1]);
        $p->features()->create(['title' => 'No screen', 'sort_order' => 2]);

        $html = $this->get('/projects/screens')->assertOk()->getContent();

        // The hidden first screenshot is not on the phone, so "c" is the 2nd visible screen (index 1).
        $this->assertSame(1, substr_count($html, 'data-screen-index='));
        $this->assertStringContainsString('data-screen-index="1"', $html);
    }

    public function test_export_then_import_restores_all_content(): void
    {
        $p = $this->project(['title' => 'Exported App', 'slug' => 'exported', 'year' => '2023', 'is_featured' => false, 'role' => 'Lead dev', 'outcome' => 'Won a grant']);
        Profile::query()->first()->update(['availability' => 'Open to work', 'show_availability' => false]);
        Setting::query()->first()->update(['show_language_switch' => false, 'analytics_script_url' => 'https://stats.test/script.js', 'analytics_site_id' => 'abc-123']);
        $png = UploadedFile::fake()->image('s.png', 600, 1200);
        $shot = $p->images()->create(['path' => Storage::disk('public')->putFileAs('projects/exported', $png, 's.png'), 'kind' => 'screenshot', 'sort_order' => 1]);
        $p->features()->create(['title' => 'Hidden one', 'is_visible' => false, 'project_image_id' => $shot->id, 'sort_order' => 1]);
        (new PageSection(['type' => 'timeline', 'anchor' => 'edu', 'title' => 'Education', 'sort_order' => 9, 'items' => [['title' => 'University']]]))->save();
        $logo = Storage::disk('public')->putFileAs('sections/logos', UploadedFile::fake()->image('acme.png', 200, 80), 'acme.png');
        (new PageSection(['type' => 'logos', 'anchor' => 'clients', 'title' => 'Clients', 'sort_order' => 10, 'items' => [['name' => 'Acme', 'logo' => $logo]]]))->save();

        $dir = storage_path('framework/testing/export-'.uniqid());
        $this->artisan('portfolio:export', ['path' => $dir])->assertSuccessful();

        Project::query()->get()->each->delete();
        PageSection::whereIn('anchor', ['edu', 'clients'])->delete();
        Storage::disk('public')->delete($logo);

        $this->artisan('portfolio:import', ['path' => $dir, '--force' => true])->assertSuccessful();

        $restored = Project::where('slug', 'exported')->firstOrFail();
        $this->assertSame('2023', $restored->year);
        $this->assertSame(['Lead dev', 'Won a grant'], [$restored->role, $restored->outcome]);
        $this->assertSame(['Open to work', false], [Profile::query()->first()->availability, Profile::query()->first()->show_availability]);
        $settings = Setting::query()->first();
        $this->assertSame([false, 'https://stats.test/script.js', 'abc-123'], [$settings->show_language_switch, $settings->analytics_script_url, $settings->analytics_site_id]);
        $this->assertFalse($restored->is_featured);
        $this->assertFalse($restored->features()->first()->is_visible);
        $this->assertSame($restored->images()->first()->id, $restored->features()->first()->project_image_id);
        $this->assertSame(1, $restored->images()->count());
        Storage::disk('public')->assertExists($restored->images()->first()->path);
        $this->assertSame('University', PageSection::where('anchor', 'edu')->first()->items[0]['title']);
        $restoredLogo = PageSection::where('anchor', 'clients')->first()->items[0];
        $this->assertSame('Acme', $restoredLogo['name']);
        Storage::disk('public')->assertExists($restoredLogo['logo']);

        File::deleteDirectory($dir);
    }
}
