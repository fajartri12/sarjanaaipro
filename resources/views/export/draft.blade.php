<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $project->title ?? $project->name }}</title>
    <style>
        /* Ukuran huruf, spasi, margin, dan gaya heading mengikuti template kampus project. */
        {!! $project->template?->toCss() ?? '' !!}

        .cover {
            page-break-after: always;
            text-align: center;
            padding-top: 4cm;
        }
        .cover h1 {
            font-size: {{ $format['font_size'] + 4 }}pt;
            font-weight: bold;
            line-height: {{ $format['line_height'] }};
            margin: 1cm 0 0.75cm;
            {{ $format['cover']['uppercase_title'] ? 'text-transform: uppercase;' : '' }}
        }
        .cover p {
            font-size: {{ $format['font_size'] }}pt;
            line-height: {{ $format['line_height'] }};
            margin: 0.25cm 0;
            text-indent: 0;
        }
        .cover .meta {
            margin-top: 3cm;
        }
        .page-break {
            page-break-after: always;
        }
        h2 {
            font-size: {{ $format['font_size'] + 2 }}pt;
            font-weight: bold;
            line-height: {{ $format['line_height'] }};
            margin: 0 0 1cm;
            text-align: center;
            page-break-after: avoid;
        }
        h3 {
            font-size: {{ $format['font_size'] }}pt;
            font-weight: bold;
            line-height: {{ $format['line_height'] }};
            margin: 0.5cm 0 0.25cm;
            page-break-after: avoid;
        }
        ul, ol {
            margin: 0 0 {{ $format['paragraph_spacing'] }}cm {{ $format['paragraph_indent'] }}cm;
            padding-left: 0.6cm;
        }
        li {
            margin-bottom: 0.15cm;
            text-align: justify;
        }
        .no-indent,
        .cover p {
            text-indent: 0;
        }
        .bibliography {
            padding-left: 1.27cm;
            text-indent: -1.27cm;
            margin-bottom: 0.4cm;
            text-align: left;
        }
        .placeholder {
            color: #555;
            font-style: italic;
            text-indent: 0;
        }
        .toc h2 {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 1cm;
        }
        .toc-table {
            width: 100%;
            border-collapse: collapse;
        }
        .toc-table tr {
            border-bottom: 1px dotted #ccc;
        }
        .toc-title {
            padding: 0.15cm 0;
            text-align: left;
        }
        .toc-page {
            text-align: right;
            padding-left: 1cm;
        }
    </style>
</head>
<body>

    {{-- Cover --}}
    <div class="cover">
        <h1>{{ $project->title ?? $project->name }}</h1>
        <p>{{ $project->documentTitle() }}</p>
        <p>diajukan untuk memenuhi salah satu syarat</p>
        <p>memperoleh gelar {{ \App\Support\Labels::degree($project->degree_level) }}</p>
        <div class="meta">
            <p>oleh</p>
            <p>{{ $project->user->name ?? 'Nama Mahasiswa' }}</p>
            <p>{{ $project->study_program ?: 'Program Studi' }}</p>
            <p>{{ $project->university ?: 'Universitas' }}</p>
            <p>{{ $project->advisor ? 'Dosen Pembimbing: '.$project->advisor : '' }}</p>
            <p>{{ now()->translatedFormat('Y') }}</p>
        </div>
    </div>

    {{-- Daftar Isi --}}
    <div class="page-break"></div>
    <div class="toc">
        <h2>Daftar Isi</h2>
        <table class="toc-table">
            @foreach ($toc as $chapter => $sections)
                @foreach ($sections as $title => $page)
                    <tr>
                        <td class="toc-title">{{ $title }}</td>
                        <td class="toc-page">{{ $page }}</td>
                    </tr>
                @endforeach
            @endforeach
        </table>
    </div>

    {{-- Isi draft --}}
    @foreach ($chapters as $chapterName => $sections)
        <section class="chapter">
            <h2>{{ $chapterName }}</h2>
            @foreach ($sections as $section)
                <h3>{{ $section->key }} {{ $section->title }}</h3>

                @if ($section->content)
                    {!! $section->content !!}
                @else
                    <p class="placeholder">[Bagian ini belum ditulis]</p>
                @endif
            @endforeach
        </section>
    @endforeach

    {{-- Daftar Pustaka --}}
    @if ($references->count())
        <section class="chapter">
            <h2>Daftar Pustaka</h2>
            @foreach ($references as $i => $ref)
                <p class="bibliography">{{ $ref->bibliography('apa', $i + 1) }}</p>
            @endforeach
        </section>
    @endif

</body>
</html>