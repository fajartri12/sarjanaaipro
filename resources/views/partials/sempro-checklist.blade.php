{{--
    Checklist tahapan menuju sempro. `$checklist` = daftar
    ['label' => ..., 'hint' => ..., 'done' => bool].
    Hanya untuk memberi arah; tidak ada aksi tulis di sini.
--}}
@php $done = collect($checklist)->where('done', true)->count(); @endphp

<div class="ui-card">
    <div class="ui-card-head">
        <div>
            <h2 class="ui-card-title">Langkah menuju sempro</h2>
            <p class="ui-card-sub">{{ $done }} dari {{ count($checklist) }} selesai.</p>
        </div>
        <span class="text-xs font-semibold tabular-nums text-gray-500 dark:text-gray-400">
            {{ $done }}/{{ count($checklist) }}
        </span>
    </div>

    <ul class="ui-card-list">
        @foreach ($checklist as $item)
            <li class="flex items-start gap-3 px-5 py-3">
                <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full {{ $item['done'] ? 'bg-emerald-500 text-white' : 'border border-gray-300 text-transparent dark:border-gray-600' }}">
                    @include('partials.icon', ['name' => 'check', 'size' => 'h-3 w-3', 'stroke' => 3])
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-[13px] font-medium {{ $item['done'] ? 'text-gray-400 line-through dark:text-gray-500' : 'text-gray-900 dark:text-white' }}">
                        {{ $item['label'] }}
                    </span>
                    <span class="mt-0.5 block text-[11px] text-gray-400 dark:text-gray-500">{{ $item['hint'] }}</span>
                </span>
            </li>
        @endforeach
    </ul>
</div>
