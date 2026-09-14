<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thesis_titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            // input pencarian
            $table->string('study_program')->nullable();
            $table->string('topic')->nullable();
            $table->string('object')->nullable();
            $table->string('location')->nullable();
            $table->string('method')->nullable();
            $table->string('keywords')->nullable();
            // hasil analisis/skor
            $table->unsignedTinyInteger('relevance')->nullable();
            $table->unsignedTinyInteger('novelty')->nullable();
            $table->unsignedTinyInteger('feasibility')->nullable();
            $table->unsignedTinyInteger('complexity')->nullable();
            $table->unsignedTinyInteger('gap_score')->nullable();
            $table->text('research_gap')->nullable();
            $table->json('variables')->nullable();
            $table->text('recommendation')->nullable();
            $table->text('risk')->nullable();
            $table->boolean('is_selected')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_selected']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thesis_titles');
    }
};
