@php
    $project = $activeProject ?? null;

    $steps = [
        [
            'key' => 'project',
            'title' => 'Buat project pertama',
            'desc' => 'Isi judul, program studi, dan metode penelitian.',
            'url' => $project ? route('projects.show', $project->id) : route('projects.create'),
            'icon' => 'folder',
            'done' => (bool) $project,
        ],
        [
            'key' => 'title',
            'title' => 'Tentukan judul',
            'desc' => 'Gunakan AI untuk menganalisis calon judul, atau isi manual.',
            'url' => $project ? route('titles.index') : null,
            'icon' => 'sparkles',
            'done' => $project && $project->title,
        ],
        [
            'key' => 'references',
            'title' => 'Kumpulkan referensi',
            'desc' => 'Impor dari DOI atau tambah manual. Minimal 5 untuk BAB I.',
            'url' => $project ? route('references.index') : null,
            'icon' => 'link',
            'done' => $project && ($project->references_count ?? 0) >= 5,
        ],
        [
            'key' => 'draft',
            'title' => 'Mulai tulis draft',
            'desc' => 'Kerjakan BAB I dulu. Minta AI mengisi, lalu perbaiki sendiri.',
            'url' => $project ? route('draft.index', $project->id) : null,
            'icon' => 'document',
            'done' => $project && ($project->sections_count ?? 0) > 0,
        ],
    ];

    $doneCount = collect($steps)->where('done', true)->count();
    $total = count($steps);
    $pct = (int) round($doneCount / $total * 100);
@endphp

@if ($showOnboarding)
    <div class="ui-card mb-6" id="onboarding-card">
        <div class="ui-card-head flex items-center justify-between py-3">
            <div>
                <h2 class="ui-card-title">Memulai Sarjana AI</h2>
                <p class="ui-card-sub">{{ $doneCount }} dari {{ $total }} langkah selesai.</p>
            </div>
            <form method="POST" action="{{ route('dashboard.onboarding.dismiss') }}">
                @csrf
                <button type="submit"
                        class="text-xs font-medium text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                    Tutup
                </button>
            </form>
        </div>

        <div class="p-5 sm:p-6">
            <div class="mb-5">
                <div class="ui-progress ui-progress-md">
                    <div class="ui-progress-fill bg-blue-600 transition-all" style="width: {{ $pct }}%"></div>
                </div>
            </div>

            <ul class="space-y-2">
                @foreach ($steps as $step)
                    <li>
                        @if ($step['done'])
                            <div class="flex items-center gap-3 rounded-xl bg-emerald-50/60 px-4 py-3 dark:bg-emerald-950/30">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-400">
                                    @include('partials.icon', ['name' => 'check', 'size' => 'h-4 w-4'])
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-[13px] font-medium text-emerald-800 dark:text-emerald-300 line-through">{{ $step['title'] }}</span>
                                    <span class="block text-xs text-emerald-600/70 dark:text-emerald-400/60">{{ $step['desc'] }}</span>
                                </span>
                            </div>
                        @elseif ($step['url'])
                            <a href="{{ $step['url'] }}"
                               class="group flex items-center gap-3 rounded-xl border border-gray-100 px-4 py-3 transition hover:border-blue-200 hover:bg-blue-50/40 dark:border-gray-800 dark:hover:border-blue-800 dark:hover:bg-blue-950/20">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-blue-50 text-blue-600 transition group-hover:bg-blue-600 group-hover:text-white dark:bg-blue-950/50 dark:text-blue-400">
                                    @include('partials.icon', ['name' => $step['icon'], 'size' => 'h-4 w-4'])
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-[13px] font-medium text-gray-900 dark:text-white">{{ $step['title'] }}</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $step['desc'] }}</span>
                                </span>
                                @include('partials.icon', ['name' => 'arrow-right', 'size' => 'h-4 w-4', 'class' => 'shrink-0 text-gray-300 transition group-hover:text-blue-500 dark:text-gray-700'])
                            </a>
                        @else
                            <div class="flex items-center gap-3 rounded-xl bg-gray-50/80 px-4 py-3 dark:bg-gray-800/40">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500">
                                    @include('partials.icon', ['name' => $step['icon'], 'size' => 'h-4 w-4'])
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-[13px] font-medium text-gray-500 dark:text-gray-400">{{ $step['title'] }}</span>
                                    <span class="block text-xs text-gray-400 dark:text-gray-500">{{ $step['desc'] }}</span>
                                </span>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
