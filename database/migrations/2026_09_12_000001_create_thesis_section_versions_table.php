<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thesis_section_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('thesis_sections')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->longText('content');
            $table->unsignedInteger('word_count')->default(0);
            $table->string('reason')->nullable(); // 'autosave', 'manual', 'ai_action'
            $table->timestamps();

            $table->index(['section_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thesis_section_versions');
    }
};