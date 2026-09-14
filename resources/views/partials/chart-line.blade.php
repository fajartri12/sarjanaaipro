@php
    // Data: [['label' => '1 Jan', 'value' => 1000], ...]
    // Render line chart SVG inline. Zero dependency, zero JS.
    $data = $data ?? [];
    $height = $height ?? 160;
    $stroke = $stroke ?? '#2563eb';
    $fill = $fill ?? 'rgba(37, 99, 235, 0.08)';
    $format = $format ?? fn ($v) => number_format($v, 0, ',', '.');

    $max = max(array_map(fn ($d) => $d['value'], $data) ?: [1]);
    $max = $max > 0 ? $max : 1;
    $count = count($data);
    $width = 600;
    $padX = 8;
    $padY = 16;
    $chartW = $width - $padX * 2;
    $chartH = $height - $padY * 2;

    $points = [];
    foreach ($data as $i => $d) {
        $x = $count > 1 ? $padX + ($i / ($count - 1)) * $chartW : $width / 2;
        $y = $padY + (1 - $d['value'] / $max) * $chartH;
        $points[] = ['x' => $x, 'y' => $y, 'value' => $d['value'], 'label' => $d['label']];
    }

    $pathLine = '';
    $pathArea = '';
    foreach ($points as $i => $p) {
        $pathLine .= ($i === 0 ? 'M' : 'L') . round($p['x'], 1) . ',' . round($p['y'], 1) . ' ';
    }
    if (! empty($points)) {
        $pathArea = $pathLine
            . 'L' . round(end($points)['x'], 1) . ',' . ($height - $padY)
            . 'L' . round($points[0]['x'], 1) . ',' . ($height - $padY) . ' Z';
    }

    // Label sumbu X: tampilkan 6 label saja biar tidak padat.
    $labelStep = max(1, intdiv($count, 6));
@endphp

<div class="relative">
    <svg viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="xMidYMid meet" class="w-full" style="height: {{ $height }}px;">
        {{-- Grid horizontal --}}
        @for ($i = 0; $i <= 3; $i++)
            <line x1="{{ $padX }}" y1="{{ $padY + ($chartH / 3) * $i }}"
                  x2="{{ $width - $padX }}" y2="{{ $padY + ($chartH / 3) * $i }}"
                  stroke="currentColor" stroke-width="0.5" stroke-dasharray="2 4"
                  class="text-gray-200 dark:text-gray-700" />
        @endfor

        {{-- Area --}}
        @if ($pathArea)
            <path d="{{ $pathArea }}" fill="{{ $fill }}" />
        @endif

        {{-- Line --}}
        @if ($pathLine)
            <path d="{{ $pathLine }}" fill="none" stroke="{{ $stroke }}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
        @endif

        {{-- Points --}}
        @foreach ($points as $p)
            <circle cx="{{ round($p['x'], 1) }}" cy="{{ round($p['y'], 1) }}" r="2.5" fill="{{ $stroke }}" />
        @endforeach
    </svg>

    {{-- Label sumbu X --}}
    <div class="mt-2 flex justify-between text-[11px] text-gray-400">
        @foreach ($data as $i => $d)
            @if ($i % $labelStep === 0 || $i === $count - 1)
                <span>{{ $d['label'] }}</span>
            @endif
        @endforeach
    </div>
</div>