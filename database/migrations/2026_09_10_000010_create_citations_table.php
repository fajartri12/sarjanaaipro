<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reference_id')->constrained()->cascadeOnDelete();
            $table->foreignId('thesis_section_id')->nullable()->constrained()->nullOnDelete();
            $table->string('style')->default('apa'); // apa | ieee | harvard
            $table->text('in_text')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'style']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citations');
    }
};
