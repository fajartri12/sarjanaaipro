<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Kemiripan · {{ $project->name }}</title>
    <style>
        @page { margin: 2cm; }
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.5;
        }
        .head {
            border-bottom: 2px solid #b45309;
            padding-bottom: 14px;
            margin-bottom: 22px;
        }
        .brand { font-size: 20px; font-weight: bold; color: #92400e; letter-spacing: -0.5px; }
        .brand-sub { font-size: 10px; color: #6b7280; margin-top: 2px; }
        .doc-title { font-size: 13px; font-weight: bold; color: #111827; margin-top: 10px; }
        table.meta { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.meta td { vertical-align: top; padding: 0; }
        .label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin-bottom: 3px;
        }
        .value { font-size: 12px; font-weight: bold; color: #111827; }
        .value-plain { font-size: 11px; color: #374151; }

        /* Kotak skor keseluruhan */
        .score-box {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 22px;
        }
        .score-num { font-size: 30px; font-weight: bold; letter-spacing: -1px; }
        .score-word { font-size: 11px; color: #6b7280; margin-top: 2px; }
        .safe { color: #047857; }
        .warn { color: #b45309; }
        .bad  { color: #b91c1c; }
        .stamp {
            display: inline-block;
            border: 2px solid #9ca3af;
            border-radius: 5px;
            padding: 4px 12px;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }
        .stamp-safe { border-color: #047857; color: #047857; }
        .stamp-warn { border-color: #b45309; color: #b45309; }
        .stamp-bad  { border-color: #b91c1c; color: #b91c1c; }

        h2.section {
            font-size: 12px;
            font-weight: bold;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 6px;
            margin: 26px 0 12px;
        }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.items th {
            background: #f9fafb;
            border-bottom: 1px solid #d1d5db;
            padding: 8px 10px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #4b5563;
            text-align: left;
        }
        table.items td {
            padding: 9px 10px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        table.items td.right, table.items th.right { text-align: right; }
        .sec-chapter { font-size: 9px; text-transform: uppercase; letter-spacing: 0.4px; color: #9ca3af; }
        .sec-title { font-weight: bold; color: #111827; }
        .sec-words { font-size: 10px; color: #6b7280; }
        .sc { font-weight: bold; }

        .match { margin: 0 0 10px; padding: 9px 11px; border-left: 3px solid #e5e7eb; background: #fafafa; }
        .match-head { font-size: 10px; color: #4b5563; margin-bottom: 3px; }
        .match-label { font-weight: bold; color: #111827; }
        .match-quote { font-size: 10px; font-style: italic; color: #4b5563; }
        .empty { font-size: 11px; color: #6b7280; font-style: italic; }
        .note {
            margin-top: 24px;
            padding: 11px 13px;
            background: #fffbeb;
            border-left: 3px solid #d97706;
            font-size: 10px;
            color: #92400e;
        }
        .sign { width: 100%; margin-top: 44px; border-collapse: collapse; }
        .sign td { width: 50%; text-align: center; font-size: 10px; color: #4b5563; vertical-align: bottom; }
        .sign .line { border-top: 1px solid #9ca3af; margin: 52px 30px 5px; }
    </style>
</head>
<body>

@php
    $tone = fn (int $score) => $score >= 50 ? 'bad' : ($score >= 20 ? 'warn' : 'safe');
    $verdict = fn (int $score) => $score >= 50
        ? 'Perlu ditulis ulang'
        : ($score >= 20 ? 'Perlu ditinjau' : 'Aman');

    $overall = $latestFull?->similarity_score;
    if ($overall === null && $latestBySection->isNotEmpty()) {
        $overall = (int) round($latestBySection->avg('similarity_score'));
    }
@endphp

<div class="head">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td>
                <div class="brand">Sarjana AI</div>
                <div class="brand-sub">Laporan pemeriksaan kemiripan naskah</div>
            </td>
            <td style="text-align: right;">
                <div class="label">Dicetak</div>
                <div class="value-plain">{{ \App\Support\Labels::tanggal($generatedAt) }}</div>
            </td>
        </tr>
    </table>
</div>

<table class="meta">
    <tr>
        <td width="50%">
            <div class="label">Naskah</div>
            <div class="value">{{ $project->title ?? $project->name }}</div>
            <div class="value-plain">{{ $project->name }}</div>
        </td>
        <td width="50%">
            <div class="label">Penulis</div>
            <div class="value">{{ $project->user?->name ?? '—' }}</div>
            @if ($project->user?->university)
                <div class="value-plain">{{ $project->user->university }}</div>
            @endif
            @if ($project->user?->program)
                <div class="value-plain">{{ $project->user->program }}</div>
            @endif
        </td>
    </tr>
</table>

@if ($overall !== null)
    <div class="score-box">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td>
                    <div class="label">Skor kemiripan keseluruhan</div>
                    <div class="score-num {{ $tone($overall) }}">{{ $overall }}%</div>
                    <div class="score-word">
                        Rata-rata dari {{ $latestBySection->count() ?: count($latestFull?->matches ?? []) }} bagian yang diperiksa
                    </div>
                </td>
                <td style="text-align: right;">
                    <span class="stamp stamp-{{ $tone($overall) }}">{{ $verdict($overall) }}</span>
                </td>
            </tr>
        </table>

        @if ($latestFull?->summary)
            <div class="value-plain" style="margin-top: 12px;">{{ $latestFull->summary }}</div>
        @endif
    </div>
@else
    <div class="note">
        Belum ada hasil pemeriksaan kemiripan untuk naskah ini. Jalankan pemeriksaan
        lebih dulu, lalu cetak ulang laporan.
    </div>
@endif

<h2 class="section">Skor per bagian</h2>

<table class="items">
    <thead>
        <tr>
            <th>Bagian</th>
            <th class="right">Kata</th>
            <th class="right">Kemiripan</th>
            <th>Penilaian</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($sections as $section)
            @php $report = $latestBySection->get($section->id); @endphp
            <tr>
                <td>
                    <div class="sec-chapter">{{ $section->chapter }}</div>
                    <div class="sec-title">{{ $section->title }}</div>
                    @if ($report)
                        <div class="sec-words">Diperiksa {{ \App\Support\Labels::tanggal($report->created_at) }}</div>
                    @endif
                </td>
                <td class="right sec-words">{{ \App\Support\Labels::angka($section->word_count ?? 0) }}</td>
                <td class="right">
                    @if ($report)
                        <span class="sc {{ $tone($report->similarity_score) }}">{{ $report->similarity_score }}%</span>
                    @else
                        <span class="sec-words">—</span>
                    @endif
                </td>
                <td class="sec-words">{{ $report ? $verdict($report->similarity_score) : 'Belum diperiksa' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="empty">Belum ada bagian berisi tulisan untuk diperiksa.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<h2 class="section">Kalimat yang terdeteksi</h2>

@php
    // Kumpulkan temuan dari hasil project terbaru, atau jatuh ke hasil per bagian.
    $findings = collect();
    foreach ($sections as $section) {
        $report = $latestBySection->get($section->id);
        foreach ($report?->matches ?? [] as $match) {
            $findings->push([
                'section' => trim($section->chapter.' '.$section->title),
                'label' => $match['label'] ?? '—',
                'source' => $match['source'] ?? 'document',
                'snippet' => $match['snippet'] ?? '',
                'score' => (int) ($match['score'] ?? 0),
            ]);
        }
    }
    $findings = $findings->sortByDesc('score')->take(40);
@endphp

@if ($findings->isEmpty())
    <p class="empty">Tidak ada kalimat yang terdeteksi mirip dengan sumber mana pun.</p>
@else
    @foreach ($findings as $finding)
        <div class="match">
            <div class="match-head">
                <span class="sc {{ $tone($finding['score']) }}">{{ $finding['score'] }}%</span>
                · {{ $finding['section'] }}
                · sumber: <span class="match-label">{{ $finding['label'] }}</span>
                ({{ $finding['source'] === 'document' ? 'dokumen Anda' : 'bagian lain' }})
            </div>
            <div class="match-quote">&ldquo;{{ $finding['snippet'] }}&rdquo;</div>
        </div>
    @endforeach
@endif

<div class="note">
    Pemeriksaan ini membandingkan kalimat Anda dengan dokumen yang Anda unggah dan dengan
    bagian lain di naskah yang sama. Kemiripan di bawah 20% umumnya wajar, terutama pada
    kutipan langsung dan istilah baku. Laporan ini alat bantu, bukan pengganti pemeriksaan
    pembimbing.
</div>

<table class="sign">
    <tr>
        <td>
            <div class="line"></div>
            Penulis
        </td>
        <td>
            <div class="line"></div>
            Pembimbing
        </td>
    </tr>
</table>

</body>
</html>
