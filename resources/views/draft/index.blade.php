@extends('layouts.app')

@section('title', 'Draft · '.$project['name'])

@section('header')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('projects.show', $project['id']) }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 transition hover:text-blue-700">
                @include('partials.icon', ['name' => 'arrow-left', 'size' => 'h-3.5 w-3.5'])
                {{ $project['name'] }}
            </a>
            <h1 class="mt-2 ui-page-title">Draft penelitian</h1>
            <p class="ui-page-sub">{{ \App\Support\Labels::angka($wordCount) }} kata tersusun dari kerangka BAB I–V.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($canExportDocx)
                <a href="{{ route('export.docx', $project['id']) }}" class="ui-btn-secondary ui-btn-sm">
                    @include('partials.icon', ['name' => 'download', 'size' => 'h-4 w-4'])
                    Export DOCX
                </a>
            @else
                <a href="{{ route('subscription.prices') }}" class="ui-btn-secondary ui-btn-sm"
                   title="Export DOCX tersedia di paket Student ke atas">
                    @include('partials.icon', ['name' => 'lock', 'size' => 'h-4 w-4'])
                    Export DOCX
                </a>
            @endif
            <a href="{{ route('export.pdf', $project['id']) }}" class="ui-btn-secondary ui-btn-sm">
                @include('partials.icon', ['name' => 'download', 'size' => 'h-4 w-4'])
                Export PDF
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="space-y-6">
        @foreach ($chapters as $chapter)
            @php $percent = $chapter['total'] > 0 ? (int) round($chapter['done'] / $chapter['total'] * 100) : 0; @endphp
            <div class="ui-table-wrap">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                    <div class="min-w-0 flex-1">
                        <h2 class="ui-card-title">{{ $chapter['chapter'] }}</h2>
                        <p class="ui-card-sub">
                            {{ $chapter['done'] }} dari {{ $chapter['total'] }} bagian selesai ·
                            {{ \App\Support\Labels::angka($chapter['wordCount']) }} kata
                        </p>
                        <div class="mt-2.5 ui-progress w-full max-w-xs">
                            <div class="ui-progress-fill {{ $percent >= 100 ? 'bg-emerald-500' : 'bg-blue-600' }}" style="width: {{ $percent }}%"></div>
                        </div>
                    </div>
                    <span class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-1 text-sm font-semibold tabular-nums text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                        {{ $percent }}%
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="ui-table">
                        <thead class="ui-thead">
                            <tr>
                                <th scope="col" class="ui-th w-20">Kode</th>
                                <th scope="col" class="ui-th">Sub-bab</th>
                                <th scope="col" class="ui-th">Jumlah Kata</th>
                                <th scope="col" class="ui-th">Status</th>
                                <th scope="col" class="ui-th text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="ui-tbody">
                            @foreach ($chapter['sections'] as $section)
                                @php $meta = \App\Support\Labels::meta(\App\Support\Labels::SECTION_STATUS, $section->status); @endphp
                                <tr class="ui-tr">
                                    <td class="ui-td font-mono text-xs font-semibold tabular-nums text-gray-500 dark:text-gray-400">
                                        {{ $section->key }}
                                    </td>
                                    <th scope="row" class="ui-td font-medium text-gray-900 dark:text-white">
                                        <a href="{{ route('draft.show', [$project['id'], $section->id]) }}" class="transition hover:text-blue-600 dark:hover:text-blue-400">
                                            {{ $section->title }}
                                        </a>
                                    </th>
                                    <td class="ui-td font-mono text-xs tabular-nums text-gray-500 dark:text-gray-400">
                                        {{ \App\Support\Labels::angka($section->word_count) }} kata
                                    </td>
                                    <td class="ui-td">
                                        @include('partials.badge', ['tone' => $meta['tone'], 'label' => $meta['label']])
                                    </td>
                                    <td class="ui-td text-right">
                                        <a href="{{ route('draft.show', [$project['id'], $section->id]) }}"
                                           class="ui-btn-table">
                                            Tulis
                                            @include('partials.icon', ['name' => 'arrow-right', 'size' => 'h-3.5 w-3.5'])
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
@endsection
