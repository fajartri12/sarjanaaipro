<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\DocumentChunk;
use App\Services\AI\AiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * Ekstraksi teks PDF, potong jadi chunk, lalu buat embedding.
 * Dijalankan di queue karena PDF besar bisa makan waktu.
 */
class ProcessDocument implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;
    public int $tries = 2;

    public function __construct(public Document $document) {}

    public function handle(AiService $ai): void
    {
        $this->document->update(['status' => 'processing', 'failure_reason' => null]);

        try {
            $path = Storage::disk($this->document->disk)->path($this->document->path);

            $text = $this->extract($path);

            if (trim($text) === '') {
                throw new \RuntimeException('Tidak ada teks yang bisa dibaca dari dokumen ini. Kemungkinan hasil scan.');
            }

            $this->document->update([
                'extracted_text' => $text,
                'status' => 'ready',
            ]);

            $this->createChunks($ai, $text);
        } catch (Throwable $e) {
            $this->document->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function extract(string $path): string
    {
        if (! is_file($path)) {
            throw new \RuntimeException('File dokumen tidak ditemukan di storage.');
        }

        $parser = new Parser();
        $pdf = $parser->parseFile($path);

        return $pdf->getText();
    }

    private function createChunks(AiService $ai, string $text): void
    {
        $this->document->chunks()->delete();

        foreach ($this->chunks($text) as $i => $content) {
            $embedding = [];
            $tokens = (int) (mb_strlen($content) / 4);

            // embedding gagal tidak boleh menggagalkan seluruh proses
            try {
                $embedding = $ai->embed($content);
            } catch (Throwable $e) {
                report($e);
            }

            DocumentChunk::create([
                'document_id' => $this->document->id,
                'position' => $i,
                'content' => $content,
                'tokens' => $tokens,
                'embedding' => $embedding ?: null,
            ]);
        }
    }

    /** Pecah teks per ~1000 karakter dengan sedikit tumpang tindih antar potongan. */
    private function chunks(string $text, int $size = 1000, int $overlap = 150): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));

        if ($text === '') {
            return [];
        }

        $chunks = [];
        $length = mb_strlen($text);
        $start = 0;

        while ($start < $length) {
            $chunks[] = trim(mb_substr($text, $start, $size));
            $start += $size - $overlap;
        }

        return array_values(array_filter($chunks));
    }
}
