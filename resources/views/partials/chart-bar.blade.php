@php
    // Data: [['label' => 'Free', 'value' => 5], ...]
    // Render horizontal bar chart SVG inline.
    $data = $data ?? [];
    $colors = $colors ?? ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444'];
    $format = $format ?? fn ($v) => number_format($v, 0, ',', '.');

    $max = max(array_map(fn ($d) => $d['value'], $data) ?: [1]);
    $max = $max > 0 ? $max : 1;
    $barH = 28;
    $gap = 8;
    $labelW = 80;
    $height = count($data) * ($barH + $gap) + 4;
@endphp

<div class="space-y-2">
    @foreach ($data as $i => $d)
        @php
            $pct = $d['value'] / $max * 100;
            $color = $colors[$i % count($colors)];
        @endphp
        <div class="flex items-center gap-3">
            <span class="w-20 text-right text-xs font-medium text-gray-600 dark:text-gray-400 truncate">{{ $d['label'] }}</span>
            <div class="flex-1">
                <div class="h-3 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                    <div class="h-full rounded-full transition-all duration-500"
                         style="width: {{ max($pct, 2) }}%; background-color: {{ $color }};"></div>
                </div>
            </div>
            <span class="w-12 text-right text-xs font-semibold tabular-nums text-gray-900 dark:text-white">{{ $format($d['value']) }}</span>
        </div>
    @endforeach
</div>