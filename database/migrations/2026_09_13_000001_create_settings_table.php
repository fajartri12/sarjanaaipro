<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Seed default channels dari config supaya admin punya data awal.
        $channels = config('payment.providers.manual.channels', []);
        DB::table('settings')->insert([
            'key' => 'payment_channels',
            'value' => json_encode($channels),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $contact = config('payment.providers.manual.contact', '');
        DB::table('settings')->insert([
            'key' => 'payment_contact',
            'value' => $contact,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};