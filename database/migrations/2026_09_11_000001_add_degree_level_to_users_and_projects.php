<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('degree_level')->default('S1')->after('study_program');
            $table->index('degree_level');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->string('degree_level')->default('S1')->after('method');
            $table->index('degree_level');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['degree_level']);
            $table->dropColumn('degree_level');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['degree_level']);
            $table->dropColumn('degree_level');
        });
    }
};
