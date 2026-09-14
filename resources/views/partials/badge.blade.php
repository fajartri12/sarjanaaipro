@php
    $tones = [
        'gray' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-400',
        'green' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400',
        'emerald' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400',
        'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-400',
        'red' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-400',
        'rose' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-400',
        'violet' => 'bg-violet-100 text-violet-800 dark:bg-violet-950/50 dark:text-violet-400',
        'cyan' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950/50 dark:text-cyan-400',
        'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950/50 dark:text-yellow-400',
    ];
@endphp
<span class="ui-chip {{ $tones[$tone ?? 'gray'] ?? $tones['gray'] }}">
    {{ $label }}
</span>
