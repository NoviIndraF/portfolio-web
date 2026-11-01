<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * v2: availability badge in the hero, case-study fields on projects (role, outcome),
     * the ID/EN switch and privacy-friendly analytics, all editable in the admin.
     */
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('availability', 120)->nullable()->after('tagline');
            $table->boolean('show_availability')->default(true)->after('availability');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->text('role')->nullable()->after('solution');
            $table->text('outcome')->nullable()->after('role');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('show_language_switch')->default(true)->after('toggle_3d');
            $table->string('analytics_script_url', 255)->nullable()->after('show_language_switch');
            $table->string('analytics_site_id', 120)->nullable()->after('analytics_script_url');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', fn (Blueprint $table) => $table->dropColumn(['availability', 'show_availability']));
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn(['role', 'outcome']));
        Schema::table('settings', fn (Blueprint $table) => $table->dropColumn(['show_language_switch', 'analytics_script_url', 'analytics_site_id']));
    }
};
