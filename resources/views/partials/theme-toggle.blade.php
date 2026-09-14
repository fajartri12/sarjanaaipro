{{--
    Dua ikon ditumpuk; CSS menyembunyikan salah satunya sesuai `data-theme`.
    Kirim `simple` => true kalau tombolnya harus jadi ikon saja.
--}}
@php $simple = $simple ?? false; @endphp

<button type="button" data-theme-toggle
        class="inline-flex items-center gap-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-blue-700 {{ $simple ? 'justify-center' : '' }} {{ $class ?? 'rounded-lg p-2' }}"
        aria-label="Ganti tema terang atau gelap">
    @include('partials.icon', ['name' => 'sun', 'size' => $size ?? 'h-5 w-5', 'class' => 'theme-sun shrink-0'])
    @include('partials.icon', ['name' => 'moon', 'size' => $size ?? 'h-5 w-5', 'class' => 'theme-moon shrink-0'])
    @unless ($simple)
        <span class="sidebar-label">Tema</span>
    @endunless
</button>
