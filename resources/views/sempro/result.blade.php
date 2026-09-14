@extends('layouts.app')

@section('title', 'Hasil Sempro')

@php
    $aspects = ['concept', 'relevance', 'argumentation', 'methodology', 'clarity', 'confidence'];
    $aspectLabels = [
        'concept' => 'Pemahaman konsep',
        'relevance' => 'Relevansi jawaban',
        'argumentation' => 'Kekuatan argumen',
        'methodology' => 'Metodologi',
        'clarity' => 'Kejelasan',
        'confidence' => 'Kepercayaan diri',
    ];
    $evaluations = $session->evaluations;
    $averages = [];
    foreach ($aspects as $aspect) {
        $values = $evaluations->pluck($aspect)->filter(fn ($v) => $v !== null);
        $averages[$aspect] = $values->count() ? (int) round($values->avg()) : 0;
    }
    $score = (int) ($session->score ?? 0);
    $tone = fn (int $value) => $value >= 75 ? 'green' : ($value >= 50 ? 'amber' : 'red');
    $barColor = fn (string $t) => match ($t) {
        'green' => 'bg-emerald-500',
        'amber' => 'bg-amber-500',
        'red' => 'bg-rose-500',
        default => 'bg-gray-400',
    };
    $scoreTone = $tone($score);
    $verdict = $score >= 80
        ? 'Siap menghadapi sempro. Pertahankan dan latih lagi sekali dua kali.'
        : ($score >= 65
            ? 'Sudah cukup baik. Rapikan bagian yang masih lemah lalu ulangi latihan.'
            : 'Masih perlu banyak perbaikan. Baca ulang proposal dan latih jawaban Anda.');
    $verdictLabel = match ($scoreTone) {
        'green' => 'Baik',
        'amber' => 'Cukup',
        default => 'Perlu latihan',
    };
    $evaluationFor = fn ($questionId) => $evaluations->firstWhere('sempro_question_id', $questionId);
@endphp

@section('header')
    <div>
        <a href="{{ route('sempro.index') }}"
           class="inline-flex items-center gap-1.5 text-[13px] font-medium text-gray-500 transition hover:text-blue-700">
            @include('partials.icon', ['name' => 'arrow-left', 'size' => 'h-4 w-4'])
            Latihan sempro
        </a>
        <h1 class="ui-page-title mt-2">Hasil latihan sempro</h1>
        <p class="ui-page-sub">{{ $session->project?->name ?? 'Project' }}</p>
    </div>
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="ui-card p-5">
            <div class="text-center">
                <p class="ui-eyebrow">Skor keseluruhan</p>
                <p class="mt-3 text-5xl font-semibold leading-none tabular-nums tracking-tight text-gray-900">{{ $score }}</p>
                <div class="mt-3.5">
                    @include('partials.badge', ['tone' => $scoreTone, 'label' => $verdictLabel])
                </div>
                <p class="mt-4 text-[13px] leading-relaxed text-gray-600">{{ $verdict }}</p>
            </div>

            <dl class="mt-6 space-y-4 border-t border-gray-100 pt-5">
                @foreach ($aspects as $aspect)
                    @php $aspectTone = $tone($averages[$aspect]); @endphp
                    <div>
                        <div class="flex items-baseline justify-between gap-3">
                            <dt class="text-[13px] text-gray-600">{{ $aspectLabels[$aspect] }}</dt>
                            <dd class="text-[13px] font-semibold tabular-nums text-gray-900">{{ $averages[$aspect] }}</dd>
                        </div>
                        <div class="ui-progress mt-2">
                            <div class="ui-progress-fill {{ $barColor($aspectTone) }}"
                                 style="width: {{ min(100, max(0, $averages[$aspect])) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </dl>
        </div>

        <div class="ui-card p-5 lg:col-span-2">
            <h2 class="ui-card-title">Ringkasan penguji</h2>
            @if ($session->summary)
                <p class="mt-3 whitespace-pre-line text-[13px] leading-relaxed text-gray-700">{{ $session->summary }}</p>
            @else
                <p class="mt-3 text-[13px] text-gray-500">Belum ada ringkasan.</p>
            @endif
        </div>
    </div>

    <div class="ui-table-wrap">
        <div class="border-b border-gray-100 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="ui-card-title">Pembahasan per pertanyaan</h2>
            <p class="ui-card-sub">{{ \App\Support\Labels::angka($session->questions->count()) }} pertanyaan dinilai oleh penguji AI.</p>
        </div>

        @if ($session->questions->count())
            <div class="overflow-x-auto">
                <table class="ui-table">
                    <thead class="ui-thead">
                        <tr>
                            <th scope="col" class="ui-th w-12 text-center">No</th>
                            <th scope="col" class="ui-th">Pertanyaan Penguji</th>
                            <th scope="col" class="ui-th w-20 text-center">Skor</th>
                            <th scope="col" class="ui-th">Catatan & Saran Perbaikan</th>
                        </tr>
                    </thead>
                    <tbody class="ui-tbody">
                        @foreach ($session->questions as $index => $question)
                            @php $evaluation = $evaluationFor($question->id); @endphp
                            <tr class="ui-tr">
                                <td class="ui-td text-center font-mono text-xs font-semibold text-gray-400 dark:text-gray-500">
                                    {{ $index + 1 }}
                                </td>
                                <th scope="row" class="ui-td font-medium leading-snug text-gray-900 dark:text-white">
                                    {{ $question->question }}
                                </th>
                                <td class="ui-td text-center">
                                    @if ($evaluation)
                                        @include('partials.badge', ['tone' => $tone((int) $evaluation->score), 'label' => $evaluation->score])
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
                                    @endif
                                </td>
                                <td class="ui-td">
                                    @if ($evaluation?->feedback)
                                        <p class="rounded-lg bg-gray-50 px-3.5 py-2.5 text-xs leading-relaxed text-gray-700 dark:bg-gray-700/50 dark:text-gray-300">
                                            {{ $evaluation->feedback }}
                                        </p>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">Belum ada catatan.</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="ui-empty border-0 bg-transparent py-16">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                    @include('partials.icon', ['name' => 'cap', 'size' => 'h-6 w-6'])
                </span>
                <p class="mt-3.5 text-sm font-semibold text-gray-900 dark:text-white">Sesi ini tidak punya pertanyaan</p>
            </div>
        @endif
    </div>
@endsection
