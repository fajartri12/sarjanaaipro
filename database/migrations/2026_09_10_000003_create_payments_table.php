<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // kode internal, dipakai di checkout
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->default('manual'); // midtrans | xendit | manual
            $table->string('provider_reference')->nullable();
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('IDR');
            $table->string('status')->default('pending'); // pending | paid | failed | expired | refunded
            $table->timestamp('paid_at')->nullable();
            $table->json('payload')->nullable(); // response mentah dari gateway
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['provider', 'provider_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
