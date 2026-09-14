@extends('layouts.app')

@section('title', 'Sesi Sempro')

@php
    $questions = $session->questions;
    $answered = $session->answers->pluck('sempro_question_id')->all();
    $evaluations = $session->evaluations->keyBy('sempro_question_id');
    $aspectLabels = [
        'concept' => 'Konsep',
        'relevance' => 'Relevansi',
        'argumentation' => 'Argumen',
        'methodology' => 'Metode',
        'clarity' => 'Kejelasan',
        'confidence' => 'Percaya diri',
    ];
@endphp

@section('header')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('sempro.index') }}"
               class="inline-flex items-center gap-1.5 text-[13px] font-medium text-gray-500 transition hover:text-blue-700">
                @include('partials.icon', ['name' => 'arrow-left', 'size' => 'h-4 w-4'])
                Latihan sempro
            </a>
            <h1 class="ui-page-title mt-2">{{ $session->project?->name ?? 'Project' }}</h1>
            <p class="ui-page-sub">Jawab semua pertanyaan, lalu akhiri sesi untuk lihat hasil.</p>
        </div>
        @if ($session->status !== 'finished')
            <form method="POST" action="{{ route('sempro.finish', $session->id) }}"
                  onsubmit="return confirm('Akhiri sesi ini dan lihat hasil akhirnya?')">
                @csrf
                <button type="submit" class="ui-btn-primary ui-btn-sm">
                    @include('partials.icon', ['name' => 'check', 'size' => 'h-4 w-4'])
                    Akhiri sesi
                </button>
            </form>
        @else
            <a href="{{ route('sempro.result', $session->id) }}" class="ui-btn-secondary ui-btn-sm">
                Lihat hasil
            </a>
        @endif
    </div>
@endsection

@section('content')
    @php
        $answeredCount = count($answered);
        $totalQuestions = $questions->count();
        $percent = $totalQuestions ? (int) round($answeredCount / $totalQuestions * 100) : 0;
    @endphp

    <div class="ui-card flex flex-wrap items-center gap-4 px-5 py-4">
        <div class="min-w-[140px] flex-1">
            <p class="ui-eyebrow">Kemajuan jawaban</p>
            <p class="mt-1 text-sm font-medium tabular-nums text-gray-900">
                {{ $answeredCount }} dari {{ $totalQuestions }} pertanyaan dijawab
            </p>
        </div>
        <div class="flex w-full items-center gap-3 sm:w-auto">
            <div class="ui-progress w-full sm:w-48">
                <div class="ui-progress-fill {{ $percent >= 100 ? 'bg-emerald-500' : 'bg-blue-600' }}"
                     style="width: {{ min(100, max(0, $percent)) }}%"></div>
            </div>
            <span class="ui-meta shrink-0 font-semibold text-gray-700">{{ $percent }}%</span>
        </div>
    </div>

    @foreach ($questions as $index => $question)
        @php
            $isAnswered = in_array($question->id, $answered, true);
            $evaluation = $evaluations->get($question->id);
        @endphp

        <div class="ui-card p-5">
            <div class="flex items-start gap-3.5">
                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full text-xs font-semibold tabular-nums {{ $isAnswered ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                    {{ $index + 1 }}
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        @include('partials.badge', ['tone' => 'gray', 'label' => $question->category])
                        @if ($question->difficulty)
                            @include('partials.badge', [
                                'tone' => match ($question->difficulty) {
                                    'dasar' => 'green',
                                    'sulit' => 'red',
                                    default => 'amber',
                                },
                                'label' => ucfirst($question->difficulty),
                            ])
                        @endif
                        <p class="text-sm font-medium leading-snug text-gray-900">{{ $question->question }}</p>
                    </div>
                </div>
            </div>

            @if ($evaluation)
                <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50/70 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm font-semibold tabular-nums text-emerald-800">Skor {{ $evaluation->score }}/100</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($aspectLabels as $field => $label)
                                <span class="ui-chip bg-white/80 text-emerald-800">
                                    {{ $label }} {{ $evaluation->{$field} }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @if ($evaluation->feedback)
                        <p class="mt-3 border-t border-emerald-200/70 pt-3 text-[13px] leading-relaxed text-emerald-800">{{ $evaluation->feedback }}</p>
                    @endif

                    {{-- Simulasi adaptif: jawaban lemah → tawarkan 3 pertanyaan lanjutan kategori yang sama. --}}
                    @if ($session->status !== 'finished' && $evaluation->score < 70)
                        <form method="POST" action="{{ route('sempro.follow-up', $session->id) }}" class="mt-3 border-t border-emerald-200/70 pt-3"
                              data-ai-stages='{"title":"Menyiapkan pertanyaan lanjutan","stages":["Meninjau jawaban","Menyusun pertanyaan lanjutan"]}'>
                            @csrf
                            <input type="hidden" name="category" value="{{ $question->category }}">
                            <input type="hidden" name="count" value="3">
                            <div class="flex flex-wrap items-center gap-3">
                                <button type="submit" class="ui-btn-secondary ui-btn-sm">
                                    @include('partials.icon', ['name' => 'sparkles', 'size' => 'h-4 w-4'])
                                    Latih 3 pertanyaan {{ $question->category }} lagi
                                </button>
                                <p class="ui-hint mt-0">Skor di bawah 70 — penguji akan menggali bagian ini.</p>
                            </div>
                        </form>
                    @endif
                </div>
            @elseif ($session->status !== 'finished')
                <details class="group mt-5">
                    <summary class="ui-btn-secondary ui-btn-sm list-none">
                        @include('partials.icon', ['name' => 'plus', 'size' => 'h-4 w-4'])
                        Jawab pertanyaan
                    </summary>
                    <form method="POST" action="{{ route('sempro.answer', $session->id) }}" class="mt-4 border-t border-gray-100 pt-4"
                          data-ai-stages='{"title":"Menilai jawaban","stages":["Membaca jawaban","Menilai argumen","Menyusun umpan balik"]}'>
                        @csrf
                        <input type="hidden" name="question_id" value="{{ $question->id }}">
                        <textarea name="answer" rows="4" required
                                  class="ui-input"
                                  placeholder="Tulis jawaban selengkap dan sejelas mungkin…">{{ old('question_id') == $question->id ? old('answer') : '' }}</textarea>
                        @if (old('question_id') == $question->id)
                            @error('answer') <p class="ui-error">{{ $message }}</p> @enderror
                        @endif
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <button type="submit" class="ui-btn-primary ui-btn-sm">
                                @include('partials.icon', ['name' => 'check', 'size' => 'h-4 w-4'])
                                Kirim jawaban
                            </button>
                            <p class="ui-hint mt-0">Minimal 20 karakter.</p>
                        </div>
                    </form>
                </details>
            @endif
        </div>
    @endforeach
@endsection
