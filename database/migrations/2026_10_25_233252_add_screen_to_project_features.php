<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional link from a feature to the screenshot that shows it,
 * so the project page can switch the phone to that screen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_features', function (Blueprint $table) {
            $table->foreignId('project_image_id')->nullable()->after('icon')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_features', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_image_id');
        });
    }
};
