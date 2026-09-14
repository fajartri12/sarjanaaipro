<?php

namespace App\Http\Controllers;

use App\Services\AI\TitleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DemoController extends Controller
{
    public function __construct(private TitleService $titles) {}

    /**
     * Demo mencari judul — tanpa login, tanpa menyimpan.
     * Hanya simulasi: generate 3 judul contoh berdasarkan topik.
     */
    public function titles(Request $request): JsonResponse
    {
        $data = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
            'method' => ['nullable', 'string', 'max:100'],
            'object' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        // Demo: generate 3 judul contoh (tanpa simpan ke DB)
        $examples = [
            [
                'title' => 'Pengaruh ' . ($data['topic'] ?? 'Variabel X') . ' terhadap ' . ($data['object'] ?? 'Variabel Y') . ' di ' . ($data['location'] ?? 'Lokasi Penelitian'),
                'relevance' => 88,
                'novelty' => 72,
                'feasibility' => 85,
                'gap_score' => 76,
                'description' => 'Mengukur seberapa besar pengaruh variabel independen terhadap variabel dependen dengan pendekatan kuantitatif.',
            ],
            [
                'title' => 'Strategi ' . ($data['method'] ?? 'Penerapan') . ' ' . ($data['topic'] ?? 'Topik') . ' pada ' . ($data['object'] ?? 'Objek') . ': Studi Kasus di ' . ($data['location'] ?? 'Lokasi'),
                'relevance' => 82,
                'novelty' => 78,
                'feasibility' => 80,
                'gap_score' => 81,
                'description' => 'Menganalisis strategi implementasi yang efektif berdasarkan studi kasus di lokasi penelitian.',
            ],
            [
                'title' => 'Analisis Faktor-Faktor yang Mempengaruhi ' . ($data['topic'] ?? 'Topik') . ' pada ' . ($data['object'] ?? 'Objek') . ' di ' . ($data['location'] ?? 'Lokasi'),
                'relevance' => 85,
                'novelty' => 70,
                'feasibility' => 90,
                'gap_score' => 74,
                'description' => 'Mengidentifikasi dan menganalisis faktor-faktor dominan yang mempengaruhi variabel penelitian.',
            ],
        ];

        return response()->json([
            'success' => true,
            'titles' => $examples,
        ]);
    }
}