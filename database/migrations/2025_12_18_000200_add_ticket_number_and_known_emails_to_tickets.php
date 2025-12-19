<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Add a human friendly ticket number (TC-000001) and a JSON array of known/cc emails
            if (!Schema::hasColumn('tickets', 'ticket_number')) {
                $table->string('ticket_number')->nullable()->unique()->after('id');
            }

            if (!Schema::hasColumn('tickets', 'known_emails')) {
                $table->json('known_emails')->nullable()->after('contact_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'known_emails')) {
                $table->dropColumn('known_emails');
            }

            if (Schema::hasColumn('tickets', 'ticket_number')) {
                $table->dropUnique(['ticket_number']);
                $table->dropColumn('ticket_number');
            }
        });
    }
};
