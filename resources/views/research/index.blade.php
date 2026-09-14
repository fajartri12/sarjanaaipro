@extends('layouts.app')

@section('title', 'Bahan Riset')

@php
    $input = 'ui-input mt-1.5';
    // Dokumen yang bisa dipakai untuk tanya-jawab & analisis gap.
    $readyDocs = $documents->where('status', 'ready');
@endphp

@section('header')
    <div>
        <h1 class="ui-page-title">Bahan riset</h1>
        <p class="ui-page-sub">Unggah PDF, lalu tanya isinya atau cari celah penelitian dari dokumen.</p>
    </div>
@endsection

@section('content')
    <div class="ui-card p-5 sm:p-6">
        <div class="mb-5 flex items-start gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-sky-50 text-sky-600">
                @include('partials.icon', ['name' => 'inbox', 'size' => 'h-[18px] w-[18px]'])
            </span>
            <div>
                <h2 class="ui-card-title">Unggah dokumen</h2>
                <p class="ui-card-sub">Format PDF, maksimal 20 MB. Teks diekstrak untuk dianalisa AI.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('research.upload') }}" enctype="multipart/form-data"
              class="grid gap-5 sm:grid-cols-2">
            @csrf
            <div class="sm:col-span-2">
                <label for="file" class="ui-label">File PDF</label>
                <input id="file" name="file" type="file" accept="application/pdf" required
                       class="ui-input mt-1.5 cursor-pointer p-2 text-[13px] file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-blue-600 file:px-3.5 file:py-2 file:text-xs file:font-medium file:text-white hover:file:bg-blue-700">
                @error('file') <p class="ui-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="title" class="ui-label">Judul dokumen</label>
                <input id="title" name="title" type="text" value="{{ old('title') }}" class="{{ $input }}">
            </div>

            <div>
                <label for="author" class="ui-label">Penulis</label>
                <input id="author" name="author" type="text" value="{{ old('author') }}" class="{{ $input }}">
            </div>

            <div>
                <label for="year" class="ui-label">Tahun</label>
                <input id="year" name="year" type="number" min="1900" max="2100"
                       value="{{ old('year', now()->year) }}" class="{{ $input }}">
            </div>

            <div>
                <label for="upload_project_id" class="ui-label">Project (opsional)</label>
                <select id="upload_project_id" name="project_id" class="{{ $input }}">
                    <option value="">Tidak untuk project</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2 border-t border-gray-100 pt-5">
                <button type="submit" class="ui-btn-primary">
                    @include('partials.icon', ['name' => 'plus', 'size' => 'h-4 w-4'])
                    Unggah dokumen
                </button>
            </div>
        </form>
    </div>

    {{-- Asisten metodologi --}}
    <div class="ui-card p-5 sm:p-6">
        <div class="mb-4 flex items-start gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-violet-50 text-violet-600">
                @include('partials.icon', ['name' => 'cap', 'size' => 'h-[18px] w-[18px]'])
            </span>
            <div>
                <h2 class="ui-card-title">Asisten metodologi</h2>
                <p class="ui-card-sub">Tanya tentang metode penelitian, teknik sampling, atau analisis data.</p>
            </div>
        </div>

        <form id="methodology-form" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="methodology-question" class="ui-label">Pertanyaan Anda</label>
                <textarea id="methodology-question" name="question" rows="3" required minlength="10"
                          class="ui-input mt-1.5" placeholder="Contoh: Apa metode yang tepat untuk meneliti pengaruh AI terhadap kinerja UMKM?"></textarea>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" id="methodology-submit" class="ui-btn-primary">
                    @include('partials.icon', ['name' => 'sparkles', 'size' => 'h-4 w-4'])
                    Tanya asisten
                </button>
                <span id="methodology-loading" class="hidden text-sm text-gray-500">
                    Memproses...
                </span>
            </div>
        </form>

        <div id="methodology-answer" class="mt-5 hidden rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
            <p class="whitespace-pre-wrap text-sm leading-relaxed text-gray-800 dark:text-gray-200"></p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('methodology-form');
            const submitBtn = document.getElementById('methodology-submit');
            const loading = document.getElementById('methodology-loading');
            const answerDiv = document.getElementById('methodology-answer');
            const answerText = answerDiv.querySelector('p');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                const question = document.getElementById('methodology-question').value.trim();
                if (question.length < 10) return;

                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-60');
                loading.classList.remove('hidden');
                answerDiv.classList.add('hidden');

                try {
                    const response = await fetch('{{ route('research.methodology') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({ question }),
                    });

                    if (!response.ok) {
                        const err = await response.json().catch(() => ({}));
                        alert(err.message || 'Gagal mendapatkan jawaban. Coba lagi.');
                        return;
                    }

                    const data = await response.json();
                    answerText.textContent = data.answer || 'Tidak ada jawaban.';
                    answerDiv.classList.remove('hidden');
                } catch (e) {
                    alert('Koneksi gagal. Periksa jaringan.');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-60');
                    loading.classList.add('hidden');
                }
            });
        });
    </script>

    <div class="ui-card">
        <div class="ui-card-head">
            <div class="flex items-start gap-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-violet-50 text-violet-600">
                    @include('partials.icon', ['name' => 'clipboard-check', 'size' => 'h-[18px] w-[18px]'])
                </span>
                <div>
                    <h2 class="ui-card-title">Tanya dokumen</h2>
                    <p class="ui-card-sub">Pilih dokumen lalu tanyakan isi atau kutipannya.</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('research.ask') }}" class="space-y-4 p-5"
              data-ai-stages='{"title":"Menelusuri dokumen","stages":["Mencari kutipan relevan","Menyusun jawaban"]}'>
            @csrf
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="ask_project_id" class="ui-label">Batasi ke project</label>
                    <select id="ask_project_id" name="project_id" class="{{ $input }}">
                        <option value="">Semua project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="question" class="ui-label">Pertanyaan</label>
                    <input id="question" name="question" type="text" required
                           placeholder="mis. Apa metode yang dipakai di penelitian ini?" class="{{ $input }}">
                </div>
            </div>

            @if ($readyDocs->count())
                <fieldset>
                    <legend class="ui-label">Dokumen sumber <span class="font-normal text-gray-400">(boleh lebih dari satu)</span></legend>
                    <div class="mt-2.5 grid gap-2 sm:grid-cols-2">
                        @foreach ($readyDocs as $doc)
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-gray-200 px-3 py-2 text-[13px] text-gray-700 transition hover:border-gray-300 hover:bg-gray-50 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/60">
                                <input type="checkbox" name="document_ids[]" value="{{ $doc->id }}"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                                <span class="truncate">{{ $doc->title ?: $doc->original_name }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @else
                <p class="text-xs text-gray-500">Belum ada dokumen berstatus <em>siap</em> untuk ditanyai.</p>
            @endif

            <div class="border-t border-gray-100 pt-5">
                <button type="submit" class="ui-btn-primary">
                    @include('partials.icon', ['name' => 'sparkles', 'size' => 'h-4 w-4'])
                    Tanya dokumen
                </button>
            </div>
        </form>

        <div class="border-t border-gray-200 bg-gray-50/70 px-5 py-3">
            <p class="ui-eyebrow">Cari celah penelitian</p>
        </div>

        <form method="POST" action="{{ route('research.gap') }}" class="space-y-4 p-5"
              data-ai-stages='{"title":"Mencari celah penelitian","stages":["Membandingkan dokumen","Memetakan yang belum diteliti","Menyusun rekomendasi"]}'>
            @csrf
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="topic" class="ui-label">Topik penelitian Anda</label>
                    <input id="topic" name="topic" type="text" required
                           placeholder="mis. mi instan di pasar digital" class="{{ $input }}">
                </div>
                <div>
                    <label for="gap_project_id" class="ui-label">Batasi ke project</label>
                    <select id="gap_project_id" name="project_id" class="{{ $input }}">
                        <option value="">Semua project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if ($readyDocs->count())
                <fieldset>
                    <legend class="ui-label">Dokumen yang dibandingkan <span class="font-normal text-gray-400">(minimal satu)</span></legend>
                    <div class="mt-2.5 grid gap-2 sm:grid-cols-2">
                        @foreach ($readyDocs as $doc)
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-gray-200 px-3 py-2 text-[13px] text-gray-700 transition hover:border-gray-300 hover:bg-gray-50 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/60">
                                <input type="checkbox" name="document_ids[]" value="{{ $doc->id }}"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                                <span class="truncate">{{ $doc->title ?: $doc->original_name }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endif

            <div class="border-t border-gray-100 pt-5">
                <button type="submit" class="ui-btn-secondary">
                    @include('partials.icon', ['name' => 'chart', 'size' => 'h-4 w-4'])
                    Cari research gap
                </button>
            </div>
        </form>
    </div>

    <div class="ui-table-wrap">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <div>
                <h2 class="ui-card-title">Kumpulan dokumen</h2>
                <p class="ui-card-sub">{{ \App\Support\Labels::angka($documents->count()) }} dokumen tersimpan di perpustakaan Anda.</p>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400">
                @include('partials.icon', ['name' => 'sparkles', 'size' => 'h-3.5 w-3.5'])
                {{ $readyDocs->count() }} siap dianalisa
            </span>
        </div>

        @if ($documents->count())
            <div class="overflow-x-auto">
                <table class="ui-table">
                    <thead class="ui-thead">
                        <tr>
                            <th scope="col" class="ui-th">Judul Dokumen</th>
                            <th scope="col" class="ui-th">Metadata</th>
                            <th scope="col" class="ui-th">Project</th>
                            <th scope="col" class="ui-th">Status</th>
                            <th scope="col" class="ui-th text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="ui-tbody">
                        @foreach ($documents as $doc)
                            @php $meta = \App\Support\Labels::meta(\App\Support\Labels::DOCUMENT_STATUS, $doc->status); @endphp
                            <tr class="ui-tr">
                                <th scope="row" class="ui-td font-normal">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-rose-50 text-rose-500 dark:bg-rose-950/40 dark:text-rose-400">
                                            @include('partials.icon', ['name' => 'document', 'size' => 'h-[18px] w-[18px]'])
                                        </span>
                                        <a href="{{ route('research.show', $doc->id) }}"
                                           class="font-medium text-gray-900 transition hover:text-blue-700 dark:text-white dark:hover:text-blue-400">
                                            {{ $doc->title ?: $doc->original_name }}
                                        </a>
                                    </div>
                                </th>
                                <td class="ui-td">
                                    <p class="text-xs text-gray-600 dark:text-gray-300">
                                        {{ $doc->author ?: 'Tanpa penulis' }} · {{ $doc->year ?: '—' }}
                                    </p>
                                    <p class="mt-0.5 font-mono text-[11px] text-gray-400 dark:text-gray-500">
                                        {{ $doc->pages ? $doc->pages.' hlm · ' : '' }}{{ \App\Support\Labels::angka($doc->size / 1024) }} KB
                                    </p>
                                </td>
                                <td class="ui-td">
                                    @if ($doc->project)
                                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-950/60 dark:text-blue-300">
                                            {{ $doc->project->name }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
                                    @endif
                                </td>
                                <td class="ui-td">
                                    @include('partials.badge', ['tone' => $meta['tone'], 'label' => $meta['label']])
                                </td>
                                <td class="ui-td">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('research.download', $doc->id) }}"
                                           class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
                                            @include('partials.icon', ['name' => 'download', 'size' => 'h-3.5 w-3.5'])
                                            Unduh
                                        </a>
                                        <form method="POST" action="{{ route('research.destroy', $doc->id) }}"
                                              onsubmit="return confirm('Hapus dokumen ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-medium text-rose-600 transition hover:bg-rose-50 hover:text-rose-700 dark:text-rose-400 dark:hover:bg-rose-950/40">
                                                @include('partials.icon', ['name' => 'trash', 'size' => 'h-3.5 w-3.5'])
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="ui-empty border-0 bg-transparent py-16">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                    @include('partials.icon', ['name' => 'inbox', 'size' => 'h-6 w-6'])
                </span>
                <p class="mt-3.5 text-sm font-semibold text-gray-900 dark:text-white">Belum ada dokumen</p>
                <p class="mt-1 max-w-sm text-[13px] leading-relaxed text-gray-500">
                    Unggah PDF jurnal, laporan, atau artikel yang ingin dianalisa.
                </p>
            </div>
        @endif
    </div>
@endsection
