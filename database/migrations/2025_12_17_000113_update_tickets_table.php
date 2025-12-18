<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'ticket_number')) {
                $table->string('ticket_number')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('tickets', 'type_id')) {
                $table->foreignId('type_id')->nullable()->constrained('ticket_types')->nullOnDelete();
            }
            if (!Schema::hasColumn('tickets', 'status_id')) {
                $table->foreignId('status_id')->nullable()->constrained('ticket_statuses')->nullOnDelete();
            }
            if (!Schema::hasColumn('tickets', 'contact_id')) {
                $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            }
        });

        // Drop columns separately with index cleanup
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'status')) {
                $table->dropIndex('tickets_status_index');
                $table->dropColumn('status');
            }
        });

        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'priority')) {
                $table->dropIndex('tickets_priority_index');
                $table->dropColumn('priority');
            }
        });

        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'content')) {
                $table->dropColumn('content');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeignIdFor('ticket_types');
            $table->dropForeignIdFor('ticket_statuses');
            $table->dropForeignIdFor('contacts');
            $table->dropColumn('ticket_number');
            $table->dropColumn('type_id');
            $table->dropColumn('status_id');
            $table->dropColumn('contact_id');

            $table->string('status', 32)->default('open');
            $table->string('priority', 32)->default('normal');
            $table->text('content')->nullable();
        });
    }
};
