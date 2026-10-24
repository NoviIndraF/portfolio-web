<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Makes every piece of content hideable (without deleting it),
 * adds "featured" projects and editable / reorderable home page sections.
 */
return new class extends Migration
{
    /** Child tables that get an is_visible switch. */
    private array $hideable = ['skill_groups', 'skills', 'project_features', 'project_tech', 'project_links', 'project_images'];

    public function up(): void
    {
        foreach ($this->hideable as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->boolean('is_visible')->default(true)->after('id');
            });
        }

        Schema::table('projects', function (Blueprint $t) {
            // Featured projects appear in the home carousel; all published ones on /projects.
            $t->boolean('is_featured')->default(true)->after('is_published');
            $t->string('year', 20)->nullable()->after('type');
        });

        Schema::create('page_sections', function (Blueprint $t) {
            $t->id();
            // Built-in: projects, skills, about, contact. Custom: text, timeline.
            $t->string('type', 30);
            $t->string('anchor', 60)->unique();     // used for the #link in the menu
            $t->string('nav_label', 60)->nullable(); // empty = not in the menu
            $t->string('eyebrow', 100)->nullable();
            $t->string('title')->nullable();
            $t->text('intro')->nullable();
            $t->longText('body')->nullable();         // text sections (rich text)
            $t->json('items')->nullable();            // timeline items
            $t->boolean('is_visible')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });

        // Built-in sections in the current order. Empty texts fall back to lang/en/site.php.
        $now = now();
        $rows = [
            ['type' => 'projects', 'anchor' => 'projects', 'nav_label' => 'Projects', 'sort_order' => 1],
            ['type' => 'skills', 'anchor' => 'skills', 'nav_label' => 'Skills', 'sort_order' => 2],
            ['type' => 'about', 'anchor' => 'about', 'nav_label' => 'About', 'sort_order' => 3],
            ['type' => 'contact', 'anchor' => 'contact', 'nav_label' => 'Contact', 'sort_order' => 4],
        ];
        DB::table('page_sections')->insert(array_map(
            fn ($r) => $r + ['is_visible' => true, 'created_at' => $now, 'updated_at' => $now],
            $rows,
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('page_sections');
        Schema::table('projects', fn (Blueprint $t) => $t->dropColumn(['is_featured', 'year']));
        foreach ($this->hideable as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('is_visible'));
        }
    }
};
