{{--
    Lapisan "AI sedang bekerja".

    Dipasang sekali di layout, lalu dimunculkan oleh app.js untuk form apa pun
    yang punya atribut data-ai-stages. Sekarang dikirim lewat fetch() dan
    konten ditukar di tempat — tidak ada reload halaman.
--}}
<div id="ai-run" class="fixed inset-0 z-[90] hidden"
     role="dialog" aria-modal="true" aria-labelledby="ai-run-title">
    <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-[2px]"></div>

    <div class="relative flex min-h-full items-center justify-center p-4">
        <div data-ai-panel tabindex="-1" aria-busy="true"
             class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-card-lg outline-none dark:border-gray-700 dark:bg-gray-800">

            <div class="flex items-start gap-4">
                <span class="relative grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-blue-600 text-white">
                    <span class="ai-halo"></span>
                    @include('partials.icon', ['name' => 'sparkles', 'size' => 'h-[22px] w-[22px]'])
                </span>

                <div class="min-w-0 flex-1">
                    <h2 id="ai-run-title" data-ai-title
                        class="text-base font-semibold tracking-tight text-gray-900 dark:text-gray-100">
                        AI sedang bekerja
                    </h2>
                    <p data-ai-note class="mt-0.5 text-[13px] leading-relaxed text-gray-500 dark:text-gray-400">
                        Jangan tutup atau muat ulang halaman ini.
                    </p>
                </div>
            </div>

            <div class="mt-5" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                 aria-valuenow="0" aria-label="Kemajuan proses" data-ai-bar-wrap>
                <div class="ui-progress ui-progress-md">
                    <div class="ui-progress-fill ai-bar" style="width: 0%" data-ai-bar></div>
                </div>
            </div>

            <ol class="mt-5 space-y-2.5" data-ai-stages></ol>

            <div class="mt-5 flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 pt-4 dark:border-gray-700">
                <p class="flex items-center gap-1.5 text-[13px] tabular-nums text-gray-500 dark:text-gray-400">
                    @include('partials.icon', ['name' => 'clock', 'size' => 'h-4 w-4 text-gray-400'])
                    <span data-ai-elapsed>baru saja</span>
                </p>
                <p data-ai-tip class="text-[11px] text-gray-400 dark:text-gray-500">Biasanya 10–30 detik</p>
            </div>
        </div>
    </div>
</div>
