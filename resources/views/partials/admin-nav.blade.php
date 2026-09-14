@php
    $adminNav = [
        ['route' => 'admin.dashboard', 'pattern' => 'admin.dashboard', 'label' => 'Ringkasan', 'icon' => 'home'],
        ['route' => 'admin.users', 'pattern' => 'admin.users', 'label' => 'Pengguna', 'icon' => 'user'],
        ['route' => 'admin.usage', 'pattern' => 'admin.usage', 'label' => 'Pemakaian AI', 'icon' => 'chart'],
        ['route' => 'admin.ai', 'pattern' => 'admin.ai', 'label' => 'Biaya & Performa', 'icon' => 'trending-up'],
        ['route' => 'admin.payments', 'pattern' => 'admin.payments', 'label' => 'Pembayaran', 'icon' => 'card'],
        ['route' => 'admin.plans', 'pattern' => 'admin.plans', 'label' => 'Paket', 'icon' => 'tag'],
        ['route' => 'admin.settings', 'pattern' => 'admin.settings', 'label' => 'Rekening', 'icon' => 'card'],
        ['route' => 'admin.projects', 'pattern' => 'admin.projects', 'label' => 'Project', 'icon' => 'folder'],
        ['route' => 'admin.activity', 'pattern' => 'admin.activity', 'label' => 'Aktivitas', 'icon' => 'clock'],
    ];
@endphp

<nav class="-mb-px flex gap-1 overflow-x-auto border-b border-gray-200 sm:flex-wrap sm:overflow-visible" aria-label="Navigasi admin">
    @foreach ($adminNav as $item)
        @php $active = request()->routeIs($item['pattern']); @endphp
        <a href="{{ route($item['route']) }}"
           @if ($active) aria-current="page" @endif
           class="inline-flex items-center gap-2 whitespace-nowrap rounded-t-lg border-b-2 px-3.5 py-2.5 text-[13px] font-medium transition
                  {{ $active
                        ? 'border-blue-600 text-blue-600'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:bg-blue-50/60 hover:text-blue-700' }}">
            @include('partials.icon', ['name' => $item['icon'], 'size' => 'h-4 w-4'])
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
