{{--
    Lencana milestone. `$milestones` hanya berisi yang sudah tercapai.
    Kalau kosong, blok ini menampilkan ajakan singkat, bukan kartu kosong.
--}}
<div class="ui-card">
    <div class="ui-card-head">
        <div>
            <h2 class="ui-card-title">Milestone</h2>
            <p class="ui-card-sub">Pencapaian Anda sejauh ini.</p>
        </div>
    </div>

    @if (count($milestones))
        <div class="flex flex-wrap gap-2 p-5 sm:p-6">
            @foreach ($milestones as $m)
                <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[11px] font-semibold text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-400">
                    @include('partials.icon', ['name' => $m['icon'], 'size' => 'h-3.5 w-3.5', 'stroke' => 2])
                    {{ $m['label'] }}
                </span>
            @endforeach
        </div>
    @else
        <div class="p-5 sm:p-6">
            <p class="text-[13px] leading-relaxed text-gray-500 dark:text-gray-400">
                Selesaikan satu bab atau kumpulkan 10 referensi untuk membuka lencana pertama.
            </p>
        </div>
    @endif
</div>
