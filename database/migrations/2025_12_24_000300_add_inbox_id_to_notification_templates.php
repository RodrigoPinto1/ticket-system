<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->foreignId('inbox_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->index(['slug', 'inbox_id']);
        });
    }

    public function down(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inbox_id');
            $table->dropIndex(['slug', 'inbox_id']);
        });
    }
};
