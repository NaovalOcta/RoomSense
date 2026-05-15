<?php
// database/migrations/2024_01_01_000002_add_cancelled_status_and_audit_to_bookings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('status');
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');

            $table->foreign('cancelled_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['cancelled_by', 'cancelled_at']);
        });
    }
};
