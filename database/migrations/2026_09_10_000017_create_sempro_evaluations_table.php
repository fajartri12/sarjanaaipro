<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sempro_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sempro_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sempro_question_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sempro_answer_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('concept')->nullable();     // pemahaman konsep
            $table->unsignedTinyInteger('relevance')->nullable();    // relevansi jawaban
            $table->unsignedTinyInteger('argumentation')->nullable();// argumentasi
            $table->unsignedTinyInteger('methodology')->nullable();  // metodologi
            $table->unsignedTinyInteger('clarity')->nullable();      // kejelasan
            $table->unsignedTinyInteger('confidence')->nullable();   // presentasi/kepercayaan diri
            $table->unsignedTinyInteger('score')->nullable();        // skor akhir 0-100
            $table->text('feedback')->nullable();
            $table->timestamps();

            $table->index(['sempro_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sempro_evaluations');
    }
};