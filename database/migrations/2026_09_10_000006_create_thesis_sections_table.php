<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thesis_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('key'); // 1.1, 2.3, dst
            $table->string('chapter'); // BAB I
            $table->string('title');
            $table->longText('content')->nullable();
            $table->unsignedInteger('word_count')->default(0);
            $table->string('status')->default('empty'); // empty | draft | done
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thesis_sections');
    }
};
