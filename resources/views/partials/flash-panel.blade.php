@php
    $data = $value instanceof \Illuminate\Support\Collection ? $value->all() : (array) $value;
@endphp

@switch($key)
    @case('researchAnswer')
        <p class="ui-eyebrow text-indigo-700">Jawaban</p>
        <p class="mt-1.5 whitespace-pre-wrap leading-relaxed">{{ $data['answer'] ?? '' }}</p>
        @if (! empty($data['sources']))
            <p class="ui-eyebrow mt-5 text-indigo-700">Sumber yang dipakai</p>
            <ul class="mt-2 space-y-2">
                @foreach ($data['sources'] as $source)
                    <li class="rounded-lg border border-indigo-200/70 bg-white px-3 py-2.5">
                        <p class="text-[13px] font-semibold text-gray-900">
                            {{ $source['label'] ?? 'Sumber' }}
                            @isset($source['score'])
                                <span class="font-normal tabular-nums text-gray-500">· skor {{ number_format((float) $source['score'], 2) }}</span>
                            @endisset
                        </p>
                        <p class="mt-1 text-xs leading-relaxed text-gray-600">{{ \Illuminate\Support\Str::limit($source['content'] ?? '', 300) }}</p>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="mt-3 text-[13px] text-indigo-700">Tidak ada cuplikan sumber yang cocok. Jawaban murni dari model.</p>
        @endif
        @break

    @case('researchGap')
        @if (! empty($data['summary']))
            <p class="leading-relaxed">{{ $data['summary'] }}</p>
        @endif
        @if (! empty($data['gaps']))
            <p class="ui-eyebrow mt-5 text-indigo-700">Celah penelitian</p>
            <ul class="mt-2 list-inside list-disc space-y-1.5 leading-relaxed">
                @foreach ($data['gaps'] as $gap)
                    <li>{{ is_array($gap) ? implode(' — ', $gap) : $gap }}</li>
                @endforeach
            </ul>
        @endif
        @if (! empty($data['opportunities']))
            <p class="ui-eyebrow mt-5 text-indigo-700">Peluang penelitian</p>
            <ul class="mt-2 list-inside list-disc space-y-1.5 leading-relaxed">
                @foreach ($data['opportunities'] as $item)
                    <li>{{ is_array($item) ? implode(' — ', $item) : $item }}</li>
                @endforeach
            </ul>
        @endif
        @break

    @case('bibliography')
        @php
            $styleNames = ['apa' => 'APA 7', 'ieee' => 'IEEE', 'harvard' => 'Harvard'];
        @endphp
        <p class="ui-eyebrow text-indigo-700">
            Daftar pustaka {{ $styleNames[$data['style'] ?? ''] ?? strtoupper((string) ($data['style'] ?? '')) }}
            · {{ count($data['entries'] ?? []) }} entri
        </p>
        <div class="mt-3 space-y-2">
            @foreach ($data['entries'] ?? [] as $entry)
                <div class="rounded-lg border border-indigo-200/70 bg-white px-3 py-2.5">
                    <p class="text-[13px] leading-relaxed text-gray-900">{{ $entry['bibliography'] ?? '' }}</p>
                    @if (! empty($entry['in_text']))
                        <p class="mt-1 text-[13px] text-gray-600">Sitasi dalam teks: <span class="font-semibold">{{ $entry['in_text'] }}</span></p>
                    @endif
                </div>
            @endforeach
        </div>
        @if (! empty($data['plain']))
            <details class="group mt-3">
                <summary class="ui-btn-secondary ui-btn-xs list-none">Salin teks polos</summary>
                <textarea rows="6" readonly
                          class="ui-input mt-2 font-mono text-[13px]">{{ $data['plain'] }}</textarea>
            </details>
        @endif
        @break

    @case('reference')
        <p class="ui-eyebrow text-indigo-700">Referensi tersimpan</p>
        <p class="mt-1.5 leading-relaxed">
            <span class="font-semibold">
                {{ $data['authors'] ?? 'Tanpa penulis' }}{{ ! empty($data['year']) ? ' ('.$data['year'].')' : '' }}
            </span>
            — {{ $data['title'] ?? '' }}
        </p>
        @break

    @case('review')
        <p class="text-[13px] font-semibold text-indigo-900">Skor rata-rata: <span class="tabular-nums">{{ $data['score'] ?? '—' }}</span></p>
        @if (! empty($data['scores']))
            <div class="mt-2.5 flex flex-wrap gap-1.5">
                @foreach ($data['scores'] as $aspect => $score)
                    <span class="inline-flex items-center gap-1 rounded-lg border border-indigo-200/70 bg-white px-2 py-1 text-[11px]">
                        <span class="capitalize text-gray-500">{{ $aspect }}</span>
                        <span class="font-semibold tabular-nums">{{ $score }}</span>
                    </span>
                @endforeach
            </div>
        @endif
        @if (! empty($data['summary']))
            <p class="mt-3 leading-relaxed">{{ $data['summary'] }}</p>
        @endif
        @if (! empty($data['recommendations']))
            <p class="ui-eyebrow mt-5 text-indigo-700">Yang perlu diperbaiki</p>
            <ul class="mt-2 list-inside list-disc space-y-1.5 leading-relaxed">
                @foreach ($data['recommendations'] as $item)
                    <li>{{ is_array($item) ? implode(' — ', $item) : $item }}</li>
                @endforeach
            </ul>
        @endif
        @break

    @case('explanation')
        @if (! empty($data))
            <p class="ui-eyebrow text-indigo-700">Penjelasan AI</p>
            <p class="mt-1.5 whitespace-pre-wrap leading-relaxed">{{ $data }}</p>
        @endif
        @break

    @case('evaluation')
        <p class="text-[13px] font-semibold text-indigo-900">Skor keseluruhan: <span class="tabular-nums">{{ $data['score'] ?? '—' }}</span></p>
        @php
            $aspectLabels = [
                'concept' => 'Pemahaman konsep',
                'relevance' => 'Relevansi jawaban',
                'argumentation' => 'Kekuatan argumen',
                'methodology' => 'Metodologi',
                'clarity' => 'Kejelasan',
                'confidence' => 'Kepercayaan diri',
            ];
        @endphp
        @if (! empty($data['feedback']))
            <p class="mt-1.5 whitespace-pre-wrap leading-relaxed">{{ $data['feedback'] }}</p>
        @endif
        <div class="mt-4 space-y-2">
            @foreach ($aspectLabels as $field => $label)
                @if (isset($data[$field]))
                    <div class="flex items-center gap-3 text-[13px]">
                        <span class="w-36 shrink-0 text-gray-600">{{ $label }}</span>
                        <span class="ui-progress">
                            <span class="ui-progress-fill bg-indigo-500" style="width: {{ (int) $data[$field] }}%"></span>
                        </span>
                        <span class="w-8 text-right font-semibold tabular-nums text-gray-800">{{ (int) $data[$field] }}</span>
                    </div>
                @endif
            @endforeach
        </div>
        @break
@endswitch

@if (! in_array($key, ['researchAnswer', 'researchGap', 'bibliography', 'reference', 'review', 'evaluation', 'explanation'], true))
    <pre class="whitespace-pre-wrap font-mono text-[13px]">{{ print_r($data, true) }}</pre>
@endif
