@php
    // `$tone` mengatur warna angka dan kotak ikon sekaligus.
    $tones = match ($tone ?? 'default') {
        'positive' => [
            'value' => 'text-emerald-600',
            'icon' => 'bg-emerald-100 text-emerald-800',
        ],
        'warning' => [
            'value' => 'text-amber-600',
            'icon' => 'bg-amber-100 text-amber-800',
        ],
        'negative' => [
            'value' => 'text-rose-600',
            'icon' => 'bg-rose-100 text-rose-800',
        ],
        default => [
            'value' => 'text-gray-900',
            'icon' => 'bg-blue-100 text-blue-800',
        ],
    };
@endphp

<div class="ui-card ui-card-hover p-5 sm:p-6">
    <div class="flex items-center justify-between gap-4">
        <div class="min-w-0">
            <p class="text-sm font-medium text-gray-500 truncate">{{ $label }}</p>
            <p class="mt-2 text-2xl font-bold tracking-tight tabular-nums {{ $tones['value'] }}">{{ $value }}</p>
            @isset($hint)
                <p class="mt-1 text-xs text-gray-400">{{ $hint }}</p>
            @endisset
        </div>
        @isset($icon)
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl {{ $tones['icon'] }}">
                @include('partials.icon', ['name' => $icon, 'size' => 'h-6 w-6'])
            </span>
        @endisset
    </div>
</div>
