<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Coba alur penyusunan penelitian tanpa biaya.',
                'price' => 0,
                'interval' => 'month',
                'sort' => 1,
                'is_popular' => false,
                'limits' => [
                    'generate_titles' => 5,
                    'ai_chat' => 10,
                    'projects' => 1,
                    'pdf_analysis' => 0,
                    'ai_reviewer' => 2,
                    'export_docx' => 0,
                ],
                'features' => [
                    '5 Generate Judul',
                    '10 AI Chat',
                    '1 Project',
                    'Basic Sempro',
                    '2 Review AI',
                    'Export PDF',
                ],
            ],
            [
                'name' => 'Student',
                'slug' => 'student',
                'description' => 'Untuk mahasiswa yang sedang menyusun proposal.',
                'price' => 79_000,
                'interval' => 'month',
                'sort' => 2,
                'is_popular' => true,
                'limits' => [
                    'generate_titles' => -1,
                    'ai_chat' => -1,
                    'projects' => 5,
                    'pdf_analysis' => 20,
                    'ai_reviewer' => 10,
                    'export_docx' => -1,
                ],
                'features' => [
                    'Unlimited Generate Judul & AI Chat',
                    'Chat AI di dalam project',
                    '5 Project',
                    'Cek Kemiripan (Plagiarism)',
                    'Sempro Simulator',
                    'Export PDF',
                    'Export DOCX (Word)',
                ],
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Untuk yang butuh analisis jurnal dan reviewer mendalam.',
                'price' => 199_000,
                'interval' => 'month',
                'sort' => 3,
                'is_popular' => false,
                'limits' => [
                    'generate_titles' => -1,
                    'ai_chat' => -1,
                    'projects' => -1,
                    'pdf_analysis' => -1,
                    'ai_reviewer' => -1,
                    'export_docx' => -1,
                ],
                'features' => [
                    'Unlimited Project',
                    'Chat AI di dalam project',
                    'Cek Kemiripan (Plagiarism)',
                    'Advanced Research & AI Reviewer',
                    'PDF Analysis',
                    'Export PDF & DOCX (Word)',
                    'Template Premium Universitas',
                    'Priority AI',
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
