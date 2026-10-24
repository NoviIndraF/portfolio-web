<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * All portfolio content tables. Every column here is editable from the admin panel.
 * Kept database-agnostic so it runs on SQLite (default) and MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Single-row table: the site owner.
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tagline');
            $table->text('bio')->nullable();
            $table->json('stats')->nullable();          // [{label, value}]
            $table->string('photo')->nullable();        // WebP for display
            $table->string('photo_original')->nullable();
            $table->string('cv_pdf')->nullable();       // null = hide "Download CV"
            $table->string('email');
            $table->string('github')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('short_description');
            $table->string('impact_line', 500)->nullable();
            $table->text('problem')->nullable();        // optional "problem" summary for detail page
            $table->text('solution')->nullable();       // optional "solution" summary for detail page
            $table->string('type', 50);                 // mobile, mobile+web, mobile+iot
            $table->string('accent_color', 9)->default('#4C8DFF');
            $table->string('scene_type', 50);           // learning, donation, shoe-store, chat, health-iot, hydroponic, analytics
            $table->string('model_glb')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_published')->default(true)->index();
            $table->json('recognition')->nullable();    // {type, holder, published}
            $table->timestamps();
        });

        Schema::create('project_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('url');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('project_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('project_tech', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('project_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('path');                     // WebP for display
            $table->string('original_path')->nullable();
            $table->string('kind', 20)->default('screenshot'); // screenshot, web, admin, hardware, certificate
            $table->string('caption')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('skill_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skill_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('icon')->nullable();         // simple-icons slug, e.g. "flutter"
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Single-row table: global site settings.
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_title');
            $table->string('meta_description', 300)->nullable();
            $table->string('primary_color', 9)->default('#4C8DFF');
            $table->string('background_color', 9)->default('#0B1020');
            $table->string('text_color', 9)->default('#E8ECF6');
            $table->boolean('toggle_3d')->default(true);
            $table->string('og_image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('skill_groups');
        Schema::dropIfExists('project_images');
        Schema::dropIfExists('project_tech');
        Schema::dropIfExists('project_features');
        Schema::dropIfExists('project_links');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('profiles');
    }
};
