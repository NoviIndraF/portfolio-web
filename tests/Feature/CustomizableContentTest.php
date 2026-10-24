<?php

namespace Tests\Feature;

use App\Filament\Resources\PageSections\Pages\CreatePageSection;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Models\PageSection;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Setting;
use App\Models\SkillGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
            ['period' => '2022 – Now', 'title' => 'Mobile Developer', 'subtitle' => 'Acme', 'is_visible' => true],
            ['period' => '2020', 'title' => 'Hidden job', 'is_visible' => false],
        ]]);
        $timeline->save();
        (new PageSection(['type' => 'text', 'anchor' => 'note', 'title' => 'A note', 'sort_order' => 6, 'body' => '<p>Hello <strong>HR</strong></p><script>alert(1)</script>']))->save();

        $this->get('/')
            ->assertSee('Work experience')->assertSee('Mobile Developer')->assertSee('Acme')->assertDontSee('Hidden job')
            ->assertSee('href="#experience"', false)
            ->assertSee('<strong>HR</strong>', false)
            ->assertDontSee('<script>alert(1)</script>', false);
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

    public function test_export_then_import_restores_all_content(): void
    {
        $p = $this->project(['title' => 'Exported App', 'slug' => 'exported', 'year' => '2023', 'is_featured' => false]);
        $p->features()->create(['title' => 'Hidden one', 'is_visible' => false, 'sort_order' => 1]);
        $png = UploadedFile::fake()->image('s.png', 600, 1200);
        $p->images()->create(['path' => Storage::disk('public')->putFileAs('projects/exported', $png, 's.png'), 'kind' => 'screenshot', 'sort_order' => 1]);
        (new PageSection(['type' => 'timeline', 'anchor' => 'edu', 'title' => 'Education', 'sort_order' => 9, 'items' => [['title' => 'University']]]))->save();

        $dir = storage_path('framework/testing/export-'.uniqid());
        $this->artisan('portfolio:export', ['path' => $dir])->assertSuccessful();

        Project::query()->get()->each->delete();
        PageSection::where('anchor', 'edu')->delete();

        $this->artisan('portfolio:import', ['path' => $dir, '--force' => true])->assertSuccessful();

        $restored = Project::where('slug', 'exported')->firstOrFail();
        $this->assertSame('2023', $restored->year);
        $this->assertFalse($restored->is_featured);
        $this->assertFalse($restored->features()->first()->is_visible);
        $this->assertSame(1, $restored->images()->count());
        Storage::disk('public')->assertExists($restored->images()->first()->path);
        $this->assertSame('University', PageSection::where('anchor', 'edu')->first()->items[0]['title']);

        File::deleteDirectory($dir);
    }
}
