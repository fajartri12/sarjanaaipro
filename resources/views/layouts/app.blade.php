<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ffffff">
    <title>@yield('title', 'Sarjana AI') · Sarjana AI</title>
    @include('partials.fonts')
    @include('partials.theme-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    @php
        $user = auth()->user();
        $initial = mb_strtoupper(mb_substr($user->name ?? '?', 0, 1));
        // `icon` merujuk ke nama ikon di partials/icon.blade.php.
        $groups = [
            ['label' => 'Kerja Penelitian', 'items' => [
                ['route' => 'projects.index', 'pattern' => 'projects.*', 'label' => 'Project', 'icon' => 'folder'],
                ['route' => 'titles.index', 'pattern' => 'titles.*', 'label' => 'Judul', 'icon' => 'sparkles'],
            ]],
            ['label' => 'Bahan & Riset', 'items' => [
                // Pola dipersempit ke satu rute: `references.search` dan `references.index`
                // sama-sama diawali `references.`, jadi `references.*` akan menyalakan
                // kedua menu sekaligus.
                ['route' => 'references.search', 'pattern' => 'references.search', 'label' => 'Cari Jurnal', 'icon' => 'search'],
                ['route' => 'research.index', 'pattern' => 'research.*', 'label' => 'Jurnal Tersimpan', 'icon' => 'book'],
                ['route' => 'references.index', 'pattern' => 'references.index', 'label' => 'Referensi', 'icon' => 'link'],
            ]],
            ['label' => 'Latihan & Nilai', 'items' => [
                ['route' => 'reviewer.index', 'pattern' => 'reviewer.*', 'label' => 'Reviewer', 'icon' => 'clipboard-check'],
                ['route' => 'similarity.index', 'pattern' => 'similarity.*', 'label' => 'Cek Kemiripan', 'icon' => 'search'],
                ['route' => 'sempro.index', 'pattern' => 'sempro.*', 'label' => 'Simulasi Sempro', 'icon' => 'cap'],
            ]],
            ['label' => 'Akun', 'items' => [
                ['route' => 'subscription.my', 'pattern' => 'subscription.my', 'label' => 'Langganan', 'icon' => 'card'],
                ['route' => 'subscription.prices', 'pattern' => 'subscription.prices', 'label' => 'Harga', 'icon' => 'tag'],
                ['route' => 'guide.index', 'pattern' => 'guide.*', 'label' => 'Buku Panduan', 'icon' => 'book-open'],
            ]],
        ];
    @endphp

    <div class="min-h-screen lg:flex">
        <!-- Sidebar desktop -->
        <aside class="app-sidebar fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-gray-200 bg-white transition-[width] duration-200 lg:flex">
            <div class="app-sidebar-head flex h-16 shrink-0 items-center justify-between gap-2 px-5 transition-[height,padding] duration-200">
                <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-2.5">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-blue-600 text-[13px] font-semibold tracking-tight text-white">SA</span>
                    <span class="sidebar-label min-w-0">
                        <span class="block truncate text-sm font-semibold tracking-tight text-gray-900">Sarjana AI</span>
                        <span class="block truncate text-[11px] text-gray-500">Pendamping penelitian</span>
                    </span>
                </a>
                <button type="button" data-sidebar-toggle
                        class="sidebar-toggle shrink-0 rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-blue-700"
                        aria-label="Ringkas atau bentangkan sidebar">
                    @include('partials.icon', ['name' => 'chevron-right', 'size' => 'h-4 w-4', 'class' => 'sidebar-toggle-icon'])
                </button>
            </div>

            <nav class="flex-1 space-y-5 overflow-y-auto px-3 pb-4">
                <a href="{{ route('dashboard') }}" title="Dashboard"
                   class="ui-nav-link {{ request()->routeIs('dashboard') ? 'ui-nav-link-active' : '' }}">
                    @include('partials.icon', ['name' => 'home', 'size' => 'h-[18px] w-[18px]'])
                    <span class="sidebar-label">Dashboard</span>
                </a>

                @foreach ($groups as $group)
                    <div class="space-y-0.5">
                        <p class="ui-eyebrow px-3 pb-1">{{ $group['label'] }}</p>
                        @foreach ($group['items'] as $item)
                            <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}"
                               class="ui-nav-link {{ request()->routeIs($item['pattern']) ? 'ui-nav-link-active' : '' }}">
                                @include('partials.icon', ['name' => $item['icon'], 'size' => 'h-[18px] w-[18px]'])
                                <span class="sidebar-label">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @endforeach

                @if ($user->isAdmin())
                    <div class="space-y-0.5">
                        <p class="ui-eyebrow px-3 pb-1">Admin</p>
                        <a href="{{ route('admin.dashboard') }}" title="Panel Admin"
                           class="ui-nav-link {{ request()->routeIs('admin.*') ? 'ui-nav-link-active' : '' }}">
                            @include('partials.icon', ['name' => 'shield', 'size' => 'h-[18px] w-[18px]'])
                            <span class="sidebar-label">Panel Admin</span>
                        </a>
                    </div>
                @endif
            </nav>
        </aside>

        <div class="app-shell flex min-h-screen w-full flex-col lg:pl-64">
            <!-- Topbar -->
            <header class="sticky top-0 z-20 border-b border-gray-200 bg-white/90 backdrop-blur-sm transition-shadow" id="app-topbar">
                <div class="flex h-14 items-center gap-2 px-4 sm:gap-3 sm:px-6">
                    <!-- Mobile brand + hamburger -->
                    <div class="flex items-center gap-1 lg:hidden">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 rounded-lg p-1">
                            <span class="grid h-7 w-7 place-items-center rounded-lg bg-blue-600 text-[11px] font-bold text-white">SA</span>
                            <span class="text-sm font-semibold tracking-tight text-gray-900">Sarjana AI</span>
                        </a>
                    </div>



                    <!-- Right actions -->
                    <div class="ml-auto flex shrink-0 items-center gap-1">
                        @include('partials.theme-toggle', ['simple' => true])

                        <!-- Notifikasi -->
                        @php $unreadCount = $user->unreadNotificationsCount(); @endphp
                        <div class="relative">
                            <button type="button" id="notif-btn"
                                    class="relative rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-900"
                                    aria-haspopup="true" aria-expanded="false" aria-label="Notifikasi">
                                @include('partials.icon', ['name' => 'bell', 'size' => 'h-5 w-5'])
                                <span id="notif-badge"
                                      class="absolute right-0.5 top-0.5 grid h-4 min-w-4 place-items-center rounded-full bg-rose-500 px-1 text-[11px] font-semibold text-white {{ $unreadCount ? '' : 'hidden' }}">{{ $unreadCount }}</span>
                            </button>
                            <div id="notif-panel"
                                 data-list-url="{{ route('notifications.index') }}"
                                 data-read-all-url="{{ route('notifications.markAllRead') }}"
                                 class="invisible absolute right-0 z-50 mt-2 w-80 origin-top-right scale-95 rounded-xl border border-gray-200 bg-white opacity-0 shadow-lg transition-all duration-150">
                                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-2.5">
                                    <p class="text-sm font-semibold text-gray-900">Notifikasi</p>
                                    <button type="button" id="notif-mark-all" class="text-[11px] font-medium text-blue-600 hover:underline">Tandai semua dibaca</button>
                                </div>
                                <div id="notif-list" class="max-h-80 overflow-y-auto">
                                    <div class="space-y-3 px-4 py-4" aria-hidden="true">
                                        <div class="ui-skeleton h-3.5 w-3/4"></div>
                                        <div class="ui-skeleton h-3 w-1/2"></div>
                                        <div class="ui-skeleton h-3.5 w-2/3"></div>
                                        <div class="ui-skeleton h-3 w-2/5"></div>
                                    </div>
                                    <span class="sr-only" role="status">Memuat notifikasi…</span>
                                </div>
                            </div>
                        </div>

                        <!-- User menu -->
                        <div class="relative">
                            <button type="button" id="user-menu-btn"
                                    class="flex items-center gap-2 rounded-lg p-1.5 text-gray-600 transition hover:bg-gray-100 hover:text-gray-900"
                                    aria-haspopup="true" aria-expanded="false">
                                <span class="grid h-7 w-7 place-items-center rounded-full bg-blue-600 text-[11px] font-semibold text-white">{{ $initial }}</span>
                                <span class="hidden text-[13px] font-medium text-gray-700 lg:inline">{{ \Illuminate\Support\Str::limit(explode(' ', $user->name)[0], 12) }}</span>
                                <svg class="h-3.5 w-3.5 text-gray-400 transition-transform" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div id="user-menu-panel"
                                 class="invisible absolute right-0 z-50 mt-2 w-56 origin-top-right scale-95 rounded-xl border border-gray-200 bg-white py-1.5 opacity-0 shadow-lg transition-all duration-150"
                                 role="menu" aria-orientation="vertical" aria-labelledby="user-menu-btn">
                                <div class="border-b border-gray-100 px-4 py-2.5">
                                    <p class="truncate text-sm font-semibold text-gray-900">{{ $user->name }}</p>
                                    <p class="truncate text-[11px] text-gray-500">{{ $user->email }}</p>
                                </div>
                                <div class="py-1">
                                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2 text-[13px] text-gray-700 transition hover:bg-gray-50" role="menuitem">
                                        @include('partials.icon', ['name' => 'user', 'size' => 'h-4 w-4 text-gray-400'])
                                        Profil &amp; Jenjang
                                    </a>
                                    <a href="{{ route('subscription.my') }}" class="flex items-center gap-2.5 px-4 py-2 text-[13px] text-gray-700 transition hover:bg-gray-50" role="menuitem">
                                        @include('partials.icon', ['name' => 'card', 'size' => 'h-4 w-4 text-gray-400'])
                                        Langganan
                                    </a>
                                </div>
                                <div class="border-t border-gray-100 pt-1">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="flex w-full items-center gap-2.5 px-4 py-2 text-[13px] text-gray-700 transition hover:bg-gray-50" role="menuitem">
                                            @include('partials.icon', ['name' => 'logout', 'size' => 'h-4 w-4 text-gray-400'])
                                            Keluar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile hamburger -->
                        <button type="button" id="mobile-menu-btn"
                                class="inline-flex items-center justify-center rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-blue-600 lg:hidden"
                                aria-label="Buka menu" aria-expanded="false">
                            <svg class="h-5 w-5 menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                            <svg class="hidden h-5 w-5 close-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Mobile drawer -->
            <div id="mobile-menu"
                 class="fixed inset-0 z-40 flex lg:hidden"
                 data-state="closed" aria-hidden="true">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-gray-900/40 opacity-0 transition-opacity duration-200"
                     id="mobile-menu-backdrop"></div>
                <!-- Panel -->
                <div class="relative ml-auto flex w-72 max-w-[85%] flex-col overflow-y-auto border-l border-gray-200 bg-white pt-2 shadow-xl transition-transform duration-200 translate-x-full"
                     id="mobile-menu-panel-inner">
                    <!-- User header -->
                    <div class="flex items-center gap-3 border-b border-gray-100 px-4 py-3">
                        <span class="grid h-9 w-9 place-items-center rounded-full bg-blue-600 text-xs font-bold text-white">{{ $initial }}</span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ $user->name }}</p>
                            <p class="truncate text-[11px] text-gray-500">{{ $user->email }}</p>
                        </div>
                    </div>

                    <!-- Nav links -->
                    <nav class="flex-1 space-y-4 overflow-y-auto px-3 py-4">
                        <a href="{{ route('dashboard') }}"
                           class="ui-nav-link {{ request()->routeIs('dashboard') ? 'ui-nav-link-active' : '' }}">
                            @include('partials.icon', ['name' => 'home', 'size' => 'h-[18px] w-[18px]'])
                            Dashboard
                        </a>
                        @foreach ($groups as $group)
                            <p class="ui-eyebrow px-3 pb-1 pt-2">{{ $group['label'] }}</p>
                            @foreach ($group['items'] as $item)
                                <a href="{{ route($item['route']) }}"
                                   class="ui-nav-link {{ request()->routeIs($item['pattern']) ? 'ui-nav-link-active' : '' }}">
                                    @include('partials.icon', ['name' => $item['icon'], 'size' => 'h-[18px] w-[18px]'])
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        @endforeach
                        @if ($user->isAdmin())
                            <p class="ui-eyebrow px-3 pb-1 pt-2">Admin</p>
                            <a href="{{ route('admin.dashboard') }}"
                               class="ui-nav-link {{ request()->routeIs('admin.*') ? 'ui-nav-link-active' : '' }}">
                                @include('partials.icon', ['name' => 'shield', 'size' => 'h-[18px] w-[18px]'])
                                Panel Admin
                            </a>
                        @endif
                    </nav>

                    <!-- Bottom actions -->
                    <div class="border-t border-gray-100 px-3 py-3 space-y-0.5">
                        <a href="{{ route('profile.edit') }}" class="ui-nav-link text-[13px]">
                            @include('partials.icon', ['name' => 'user', 'size' => 'h-[18px] w-[18px]'])
                            Profil
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="ui-nav-link w-full text-[13px] text-rose-600 hover:bg-rose-50 hover:text-rose-700">
                                @include('partials.icon', ['name' => 'logout', 'size' => 'h-[18px] w-[18px]'])
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            @hasSection('header')
                <header class="border-b border-gray-200 bg-white" data-page-header>
                    <div class="mx-auto w-full max-w-6xl px-4 py-5 sm:px-6 lg:px-8">
                        @yield('header')
                    </div>
                </header>
            @endif

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8" data-page-content>
                <div class="mx-auto w-full max-w-6xl space-y-6">
                    <div data-flash-container>
                        @include('partials.flash')
                    </div>
                    @yield('content')
                </div>
            </main>

            <footer class="px-4 pb-8 sm:px-6 lg:px-8">
                <p class="mx-auto w-full max-w-6xl text-[11px] leading-relaxed text-gray-400">
                    Keluaran AI bisa keliru. Selalu periksa ulang ke dosen pembimbing dan sumber aslinya.
                </p>
            </footer>
        </div>
    </div>

    @include('partials.ai-progress')
</body>
</html>
