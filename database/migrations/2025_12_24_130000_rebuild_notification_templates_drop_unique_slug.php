<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Create a new table without the unique constraint on slug
        Schema::create('notification_templates_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inbox_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('subject');
            $table->text('body_html');
            $table->string('locale')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->index(['slug', 'inbox_id']);
        });

        // Copy data from old table to new table
        DB::statement('INSERT INTO notification_templates_new (id, inbox_id, slug, subject, body_html, locale, enabled, created_at, updated_at)
                        SELECT id, inbox_id, slug, subject, body_html, locale, enabled, created_at, updated_at FROM notification_templates');

        // Drop old table and rename new one
        Schema::drop('notification_templates');
        Schema::rename('notification_templates_new', 'notification_templates');
    }

    public function down(): void
    {
        // Recreate the original table with unique slug
        Schema::create('notification_templates_old', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('subject');
            $table->text('body_html');
            $table->string('locale')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        DB::statement('INSERT INTO notification_templates_old (id, slug, subject, body_html, locale, enabled, created_at, updated_at)
                        SELECT id, slug, subject, body_html, locale, enabled, created_at, updated_at FROM notification_templates');

        Schema::drop('notification_templates');
        Schema::rename('notification_templates_old', 'notification_templates');
    }
};
