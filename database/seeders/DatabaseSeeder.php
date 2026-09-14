<?php

namespace Database\Seeders;

use App\Models\UniversityTemplate;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(PlanSeeder::class);
        $this->call(QuestionBankSeeder::class);

        UniversityTemplate::seedDefaults();

        User::updateOrCreate(
            ['email' => 'admin@sarjanaai.test'],
            [
                'name' => 'Admin Sarjana AI',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ],
        );

        User::updateOrCreate(
            ['email' => 'mahasiswa@sarjanaai.test'],
            [
                'name' => 'Mahasiswa Contoh',
                'password' => Hash::make('password'),
                'role' => User::ROLE_STUDENT,
                'email_verified_at' => now(),
                'university' => 'Universitas Contoh',
                'study_program' => 'Sistem Informasi',
            ],
        );
    }
}
