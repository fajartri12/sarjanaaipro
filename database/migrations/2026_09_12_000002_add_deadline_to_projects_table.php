<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Target tanggal selesai; dipakai untuk pengingat deadline.
            $table->date('deadline')->nullable()->after('status');
            $table->timestamp('deadline_notified_at')->nullable()->after('deadline');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['deadline', 'deadline_notified_at']);
        });
    }
};