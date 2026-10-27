<?php

namespace Tests\Feature;

use App\Filament\Pages\EditProfile;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileGalleryTest extends TestCase
{
    use RefreshDatabase;

    private Profile $profile;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->withoutVite();
        Setting::create(['site_title' => 'Test', 'toggle_3d' => true]);
        $this->profile = Profile::create(['name' => 'Novi Indra Fanani', 'tagline' => 'Mobile App Developer', 'email' => 'novi@example.com']);
    }

    private function photo(string $caption, int $order, bool $visible = true): ProfilePhoto
    {
        $path = Storage::disk('public')->putFileAs('profile/gallery', UploadedFile::fake()->image("{$order}.png", 400, 500), "{$order}.png");

        return $this->profile->photos()->create(['path' => $path, 'caption' => $caption, 'sort_order' => $order, 'is_visible' => $visible]);
    }

    public function test_about_shows_visible_gallery_photos_in_admin_order(): void
    {
        $this->photo('Second photo', 2);
        $this->photo('First photo', 1);
        $this->photo('Hidden photo', 3, visible: false);

        $html = $this->get('/')->assertOk()
            ->assertSee('data-photo-carousel', false)
            ->assertSeeInOrder(['First photo', 'Second photo'])
            ->assertDontSee('Hidden photo')
            ->getContent();

        $this->assertSame(2, substr_count($html, 'data-photo-dot'));
        $this->assertStringContainsString('.webp', $html); // uploads are converted to WebP
    }

    public function test_about_falls_back_to_profile_photo_without_gallery(): void
    {
        $this->get('/')->assertOk()->assertDontSee('data-photo-carousel', false);
    }

    public function test_admin_profile_keeps_and_removes_gallery_photos(): void
    {
        $keep = $this->photo('Keep me', 1);
        $remove = $this->photo('Remove me', 2);
        $this->actingAs(User::factory()->create());

        $page = Livewire::test(EditProfile::class)->assertHasNoFormErrors();
        $state = $page->get('data.photos');
        $this->assertCount(2, $state);

        // Drop the second item and save: the row and its files go, the first stays.
        $page->set('data.photos', array_slice($state, 0, 1, true))->call('save')->assertHasNoFormErrors();

        $this->assertModelExists($keep);
        $this->assertModelMissing($remove);
        Storage::disk('public')->assertMissing($remove->path);
    }

    public function test_export_then_import_restores_gallery(): void
    {
        $this->photo('Meetup', 1);
        $this->photo('Office', 2, visible: false);

        $dir = storage_path('framework/testing/export-'.uniqid());
        $this->artisan('portfolio:export', ['path' => $dir])->assertSuccessful();
        $this->artisan('portfolio:import', ['path' => $dir, '--force' => true])->assertSuccessful();

        $photos = Profile::current()->photos;
        $this->assertSame(['Meetup', 'Office'], $photos->pluck('caption')->all());
        $this->assertFalse($photos[1]->is_visible);
        Storage::disk('public')->assertExists($photos[0]->path);

        File::deleteDirectory($dir);
    }
}
