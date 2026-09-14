@php
    $project = $project ?? null;
    $researchTypes = ['Kuantitatif', 'Kualitatif', 'Mixed Method', 'R&D', 'Studi Kasus'];
    $methods = ['Regresi Linear', 'PLS-SEM', 'Analisis Deskriptif', 'Studi Literatur', 'Eksperimen'];
    $degreeLevels = \App\Support\Labels::DEGREE_LEVEL;
    $docLabel = $project ? \App\Support\Labels::document($project['degree_level'] ?? null) : 'penelitian';
    $input = 'ui-input mt-1.5';

    $freeTemplates = $templates->where('is_premium', false);
    $premiumTemplates = $templates->where('is_premium', true);
@endphp

<form method="POST"
      action="{{ $project ? route('projects.update', $project['id']) : route('projects.store') }}"
      class="space-y-6">
    @csrf
    @if ($project)
        @method('PUT')
    @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="name" class="ui-label">Nama project</label>
            <input id="name" name="name" type="text" required
                   value="{{ old('name', $project['name'] ?? '') }}"
                   placeholder="Draft Bab 1-5" class="{{ $input }}">
            <p class="ui-hint">Nama internal untuk membedakan project. Tidak muncul di dokumen.</p>
            @error('name') <p class="ui-error">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
            <label for="title" class="ui-label">Judul {{ $docLabel }}</label>
            <input id="title" name="title" type="text"
                   value="{{ old('title', $project['title'] ?? '') }}"
                   placeholder="Pengaruh ... terhadap ..." class="{{ $input }}">
            @error('title') <p class="ui-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="study_program" class="ui-label">Program studi</label>
            <input id="study_program" name="study_program" type="text"
                   value="{{ old('study_program', $project['study_program'] ?? '') }}" class="{{ $input }}">
            @error('study_program') <p class="ui-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="university" class="ui-label">Universitas</label>
            <input id="university" name="university" type="text"
                   value="{{ old('university', $project['university'] ?? '') }}" class="{{ $input }}">
            @error('university') <p class="ui-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="advisor" class="ui-label">Dosen pembimbing</label>
            <input id="advisor" name="advisor" type="text"
                   value="{{ old('advisor', $project['advisor'] ?? '') }}" class="{{ $input }}">
            @error('advisor') <p class="ui-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="research_type" class="ui-label">Jenis penelitian</label>
            <select id="research_type" name="research_type" class="{{ $input }}">
                <option value="">Pilih jenis</option>
                @foreach ($researchTypes as $type)
                    <option value="{{ $type }}" @selected(old('research_type', $project['research_type'] ?? '') === $type)>{{ $type }}</option>
                @endforeach
            </select>
            @error('research_type') <p class="ui-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="method" class="ui-label">Metode analisis</label>
            <select id="method" name="method" class="{{ $input }}">
                <option value="">Pilih metode</option>
                @foreach ($methods as $item)
                    <option value="{{ $item }}" @selected(old('method', $project['method'] ?? '') === $item)>{{ $item }}</option>
                @endforeach
            </select>
            @error('method') <p class="ui-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="degree_level" class="ui-label">Jenjang</label>
            <select id="degree_level" name="degree_level" class="{{ $input }}">
                @foreach ($degreeLevels as $key => $meta)
                    <option value="{{ $key }}" @selected(old('degree_level', $project['degree_level'] ?? 'S1') === $key)>{{ $meta['label'] }}</option>
                @endforeach
            </select>
            <p class="ui-hint">Menentukan kerangka BAB, istilah dokumen (skripsi/tesis/disertasi), dan gelar di cover export.</p>
            @error('degree_level') <p class="ui-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="deadline" class="ui-label">Target selesai</label>
            <input id="deadline" name="deadline" type="date"
                   value="{{ old('deadline', isset($project['deadline']) && $project['deadline'] ? \Illuminate\Support\Carbon::parse($project['deadline'])->format('Y-m-d') : '') }}"
                   class="{{ $input }}">
            <p class="ui-hint">Dipakai untuk pengingat saat tenggat mendekat.</p>
            @error('deadline') <p class="ui-error">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
            <label for="description" class="ui-label">Catatan awal</label>
            <textarea id="description" name="description" rows="4" class="{{ $input }}"
                      placeholder="Konteks, objek penelitian, atau arahan dari pembimbing.">{{ old('description', $project['description'] ?? '') }}</textarea>
            @error('description') <p class="ui-error">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
            <label for="template_id" class="ui-label">Format kampus</label>
            <select id="template_id" name="template_id" class="{{ $input }}">
                <option value="">Format umum (Times New Roman 12, spasi 1,5)</option>
                @foreach ($freeTemplates as $template)
                    <option value="{{ $template->id }}" @selected((string) old('template_id', $project['template_id'] ?? '') === (string) $template->id)>
                        {{ $template->name }}{{ $template->university ? ' — ' . $template->university : '' }}
                    </option>
                @endforeach
                @if ($premiumTemplates->isNotEmpty())
                    <optgroup label="Premium">
                        @foreach ($premiumTemplates as $template)
                            <option value="{{ $template->id }}" @selected((string) old('template_id', $project['template_id'] ?? '') === (string) $template->id)>
                                {{ $template->name }} · {{ \App\Support\Labels::rupiah($template->price) }}
                            </option>
                        @endforeach
                    </optgroup>
                @endif
            </select>
            <p class="ui-hint">Menentukan ukuran huruf, spasi, margin, dan penomoran halaman saat export PDF/DOCX. Bisa diubah kapan saja.</p>
            @error('template_id') <p class="ui-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3 border-t border-gray-100 pt-5">
        <button type="submit" class="ui-btn-primary">
            {{ $project ? 'Simpan perubahan' : 'Buat project' }}
        </button>
        <a href="{{ route('projects.index') }}" class="ui-btn-ghost">Batal</a>
    </div>
</form>
