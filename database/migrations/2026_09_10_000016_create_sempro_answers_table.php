<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sempro_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sempro_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sempro_question_id')->constrained()->cascadeOnDelete();
            $table->longText('answer')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['sempro_session_id', 'sempro_question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sempro_answers');
    }
};
