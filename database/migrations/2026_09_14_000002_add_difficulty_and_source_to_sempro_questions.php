<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sempro_questions', function (Blueprint $table) {
            // Tingkat kesulitan: dasar | menengah | sulit. Null = belum ditentukan.
            $table->string('difficulty')->nullable()->after('category');
            // 'bank' kalau dari question_bank, 'ai' kalau hasil generate. Untuk analitik.
            $table->string('source')->default('ai')->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('sempro_questions', function (Blueprint $table) {
            $table->dropColumn(['difficulty', 'source']);
        });
    }
};
