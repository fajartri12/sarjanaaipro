<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student')->after('email');
            $table->string('phone')->nullable()->after('role');
            $table->string('university')->nullable()->after('phone');
            $table->string('study_program')->nullable()->after('university');
            $table->string('avatar')->nullable()->after('study_program');
            $table->boolean('is_active')->default(true)->after('avatar');
            $table->timestamp('last_login_at')->nullable()->after('is_active');

            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn([
                'role',
                'phone',
                'university',
                'study_program',
                'avatar',
                'is_active',
                'last_login_at',
            ]);
        });
    }
};