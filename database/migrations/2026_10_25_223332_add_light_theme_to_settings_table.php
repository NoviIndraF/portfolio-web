<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Light mode colors and the mode new visitors see first (system / light / dark). */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('light_background_color', 9)->default('#F6F8FC')->after('text_color');
            $table->string('light_text_color', 9)->default('#0B1020')->after('light_background_color');
            $table->string('default_theme', 10)->default('system')->after('light_text_color');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['light_background_color', 'light_text_color', 'default_theme']);
        });
    }
};
