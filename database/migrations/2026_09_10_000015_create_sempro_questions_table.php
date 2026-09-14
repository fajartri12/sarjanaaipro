<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sempro_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sempro_session_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('category')->default('umum'); // metodologi | landasan teori | hasil | umum
            $table->text('question');
            $table->text('expected_points')->nullable(); // poin jawaban yang diharapkan
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sempro_questions');
    }
};
