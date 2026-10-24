<?php

namespace App\Support;

use App\Models\PageSection;
use App\Models\Profile;
use App\Models\Setting;

/**
 * Per-request access to the site settings and owner profile.
 */
class Site
{
    private ?Setting $settings = null;

    private ?Profile $profile = null;

    private $sections = null;

    public function settings(): Setting
    {
        return $this->settings ??= Setting::current();
    }

    public function profile(): Profile
    {
        return $this->profile ??= Profile::current() ?? new Profile(['name' => config('app.name'), 'tagline' => '', 'email' => '']);
    }

    /** Visible home page sections in order. */
    public function sections()
    {
        return $this->sections ??= PageSection::visible()->orderBy('sort_order')->get();
    }

    /** Menu items: visible sections that have a menu label. */
    public function nav()
    {
        return $this->sections()->filter(fn ($s) => filled($s->nav_label))->values();
    }

    /** Absolute URL of the default share image: settings OG image, else the original profile photo. */
    public function defaultOgImage(): ?string
    {
        $settings = $this->settings();
        if ($settings->og_image) {
            return $settings->ogImageUrl();
        }
        $profile = $this->profile();
        $path = $profile->photo_original ?: $profile->photo;

        return $path ? asset('storage/'.$path) : null;
    }
}
