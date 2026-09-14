<?php

namespace App\Http\Controllers;

use App\Models\PlagiarismReport;
use App\Models\Project;
use App\Support\AiText;
use App\Support\Labels;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class ExportController extends Controller
{
    public function pdf(Project $project): Response
    {
        $this->authorize('view', $project);

        $sections = $project->sections()->orderBy('position')->get();
        $chapters = $sections->groupBy('chapter');

        // Bangun daftar isi: [chapter => [section => page], ...]
        // Halaman dihitung kasar: 1 cover + 1 TOC + 1 per bab + sisanya.
        $toc = [];
        $page = 3; // Cover + TOC
        foreach ($chapters as $chapterName => $chapterSections) {
            $toc[$chapterName] = [];
            foreach ($chapterSections as $section) {
                $toc[$chapterName][$section->key.' '.$section->title] = $page;
                $page += max(1, (int) ceil($section->word_count / 300));
            }
        }

        $html = view('export.draft', [
            'project' => $project,
            'chapters' => $chapters,
            'references' => $project->references,
            'toc' => $toc,
            'format' => $project->format(),
        ])->render();

        $format = $project->format();

        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', $format['font_family']);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Nomor halaman mengikuti posisi yang diminta template.
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont($format['font_family'], 'normal');

        if ($format['page_number_position'] !== 'none') {
            $y = 800;
            $x = 280;

            if ($format['page_number_position'] === 'bottom-right') {
                $x = 500;
            }

            $canvas->page_text($x, $y, '{PAGE_NUM} / {PAGE_COUNT}', $font, 10, [0, 0, 0]);
        }

        $filename = preg_replace('/[^a-zA-Z0-9\-\_]/', '_', $project->title ?? $project->name);
        $filename = substr($filename, 0, 80).'.pdf';

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-cache',
        ]);
    }

    /**
     * Ekspor naskah ke format DOCX (Microsoft Word).
     *
     * DOCX cuma ZIP berisi XML OOXML; cukup tiga entri minimal, jadi tidak
     * perlu library tambahan.
     */
    public function docx(Project $project): BinaryFileResponse
    {
        $this->authorize('view', $project);

        // DOCX adalah fitur berbayar: plan Free tidak punya akses.
        $limit = $project->user->currentPlan()?->limit('export_docx');
        if ($limit === 0) {
            abort(403, 'Export DOCX tersedia untuk paket Student ke atas. Upgrade plan Anda untuk mengunduh naskah Word.');
        }

        $format = $project->format();
        $fs = $format['font_size'] * 2; // OOXML half-points
        $indent = (int) round($format['paragraph_indent'] * 567); // cm → twips (1 cm ≈ 567 twips)
        $spacingAfter = (int) round($format['paragraph_spacing'] * 567);

        $p = function (string $text, string $align = 'both', bool $bold = false, ?int $size = null, bool $indentPara = false) use ($fs, $indent, $spacingAfter): string {
            $sz = $size ?? $fs;
            $ind = $indentPara ? '<w:ind w:firstLine="'.$indent.'"/>' : '';

            return '<w:p><w:pPr>'.$ind.'<w:jc w:val="'.$align.'"/><w:spacing w:after="'.$spacingAfter.'"/></w:pPr>'
                .'<w:r><w:rPr>'.($bold ? '<w:b w:val="on"/>' : '').'<w:sz w:val="'.$sz.'"/></w:rPr><w:t xml:space="preserve">'
                .htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</w:t></w:r></w:p>'."\n";
        };

        $title = $project->title ?? $project->name;
        $coverTitle = $format['cover']['uppercase_title'] ? mb_strtoupper($title) : $title;

        $body = $p($coverTitle, 'center', true, $fs + 8)
            .$p($project->documentTitle(), 'center')
            .$p('diajukan untuk memenuhi salah satu syarat', 'center')
            .$p('memperoleh gelar '.Labels::degree($project->degree_level), 'center')
            .$p('oleh', 'center')
            .$p($project->user->name ?? 'Nama Mahasiswa', 'center', true)
            .$p($project->study_program ?: 'Program Studi', 'center')
            .$p($project->university ?: 'Universitas', 'center')
            .$p($project->advisor ? 'Dosen Pembimbing: '.$project->advisor : '', 'center')
            .$p((string) now()->year, 'center')
            .'<w:br w:type="page"/>'."\n";

        foreach ($project->sections()->orderBy('position')->get()->groupBy('chapter') as $chapter => $sections) {
            $heading = $format['heading_case'] === 'uppercase' ? mb_strtoupper($chapter) : $chapter;
            $body .= $p($heading, 'center', true, $fs + 4);

            foreach ($sections as $section) {
                $body .= $p($section->key.' '.$section->title, 'left', true);

                // Simpan sebagai teks biasa dulu supaya blok HTML tidak menyatu.
                $plain = AiText::plain((string) $section->content) ?? '';

                if (trim($plain) === '') {
                    $body .= '<w:p><w:r><w:rPr><w:i w:val="on"/></w:rPr><w:t>[Bagian ini belum ditulis]</w:t></w:r></w:p>'."\n";

                    continue;
                }

                foreach (preg_split('/\n{2,}/', $plain) as $para) {
                    $para = trim(preg_replace('/\s+/', ' ', $para));
                    if ($para !== '') {
                        $body .= $p($para, 'both', false, 24, true);
                    }
                }
            }
        }

        if ($project->references->count()) {
            $body .= '<w:br w:type="page"/>'."\n".$p('DAFTAR PUSTAKA', 'center', true, 28);
            foreach ($project->references as $i => $ref) {
                $body .= $p($ref->bibliography('apa', $i + 1), 'both', false, 24, true);
            }
        }

        $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            .$body.'</w:body></w:document>';

        $temp = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive;
        $zip->open($temp, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'</Relationships>');
        $zip->addFromString('word/document.xml', $document);
        $zip->close();

        $filename = preg_replace('/[^a-zA-Z0-9\-\_]/', '_', $title);
        $filename = substr($filename, 0, 80).'.docx';

        return response()->download($temp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Ekspor laporan kemiripan ke PDF, untuk dilampirkan ke pembimbing.
     *
     * Yang diambil hanya hasil pemeriksaan terbaru per bagian dan yang terbaru
     * untuk seluruh project, supaya laporan tidak menumpuk hasil uji coba lama.
     */
    public function similarityPdf(Project $project): Response
    {
        $this->authorize('view', $project);

        $sections = $project->sections()
            ->whereNotNull('content')
            ->orderBy('position')
            ->get(['id', 'chapter', 'key', 'title', 'word_count']);

        $latestBySection = PlagiarismReport::latestPerSection($project->id);
        $latestFull = PlagiarismReport::latestFull($project->id);

        $html = view('export.similarity', [
            'project' => $project,
            'sections' => $sections,
            'latestBySection' => $latestBySection,
            'latestFull' => $latestFull,
            'generatedAt' => now(),
        ])->render();

        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text(280, 800, 'Halaman {PAGE_NUM} dari {PAGE_COUNT}', $font, 9, [0.42, 0.45, 0.50]);

        $filename = preg_replace('/[^a-zA-Z0-9\-\_]/', '_', $project->title ?? $project->name);
        $filename = 'kemiripan-'.substr($filename, 0, 70).'.pdf';

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
