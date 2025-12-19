<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'entity_id')) {
                $table->foreignId('entity_id')->nullable()->constrained('entities')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'entity_id')) {
                // dropForeignIdFor expects the column name or referenced table depending on Laravel version
                // Try to drop the foreign key/column safely
                try {
                    $table->dropForeignIdFor('entities');
                } catch (\Throwable $e) {
                    // fallback: drop column if the helper fails
                    if (Schema::hasColumn('tickets', 'entity_id')) {
                        $table->dropColumn('entity_id');
                    }
                }
            }
        });
    }
};
