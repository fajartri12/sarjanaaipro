<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('university_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('university')->nullable();
            $table->string('degree_level', 4)->default('S1'); // S1, S2, S3, atau 'all'
            $table->text('description')->nullable();
            $table->unsignedInteger('price')->default(0); // 0 = termasuk paket berbayar
            $table->boolean('is_active')->default(true);
            $table->boolean('is_premium')->default(false);
            $table->json('config')->nullable(); // font, margin, spasi, sitasi, sampul
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort']);
            $table->index('degree_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('university_templates');
    }
};