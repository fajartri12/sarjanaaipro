<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_bank', function (Blueprint $table) {
            $table->id();
            $table->string('category')->default('umum'); // metodologi | landasan teori | rumusan masalah | tujuan | hipotesis | hasil | pembahasan | umum
            $table->string('method')->nullable(); // kuantitatif | kualitatif | mixed | studi kasus | eksperimen
            $table->string('section')->nullable(); // bab1 | bab2 | bab3 | bab4 | bab5
            $table->enum('difficulty', ['dasar', 'menengah', 'sulit'])->default('menengah');
            $table->text('question');
            $table->text('expected_points')->nullable();
            $table->text('hint')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'method', 'section', 'difficulty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_bank');
    }
};
