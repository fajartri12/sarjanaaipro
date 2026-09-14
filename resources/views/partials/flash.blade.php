@php
    $flashSuccess = session('success');
    $flashError = session('error') ?? $errors->first('quota') ?: null;
    $flashStatus = session('status');
    $flashKeys = ['researchAnswer', 'researchGap', 'bibliography', 'reference', 'review', 'evaluation', 'explanation'];
    $flashPanels = collect($flashKeys)->mapWithKeys(fn ($k) => [$k => session($k)])->filter();
@endphp

@if ($flashSuccess || $flashError || $flashStatus)
    {{-- Pesan singkat: melayang di kanan atas, hilang sendiri (lihat app.js). --}}
    <div class="pointer-events-none fixed inset-x-4 top-4 z-[80] flex flex-col items-end gap-3 sm:left-auto sm:w-full sm:max-w-sm"
         aria-live="polite" data-toast-stack>
        @if ($flashSuccess)
            <div data-alert data-toast role="status" class="ui-toast border-emerald-200 bg-emerald-50">
                <span class="mt-0.5 shrink-0 text-emerald-600">
                    @include('partials.icon', ['name' => 'check', 'size' => 'h-4 w-4', 'stroke' => 2.2])
                </span>
                <p class="flex-1 text-emerald-900">{{ $flashSuccess }}</p>
            </div>
        @endif
        @if ($flashStatus)
            <div data-alert data-toast role="status" class="ui-toast border-sky-200 bg-sky-50">
                <span class="mt-0.5 shrink-0 text-sky-600">
                    @include('partials.icon', ['name' => 'clock', 'size' => 'h-4 w-4'])
                </span>
                <p class="flex-1 text-sky-900">{{ $flashStatus }}</p>
            </div>
        @endif
        @if ($flashError)
            <div data-alert data-toast role="alert" class="ui-toast border-rose-200 bg-rose-50">
                <span class="mt-0.5 shrink-0 text-rose-600">
                    @include('partials.icon', ['name' => 'warning', 'size' => 'h-4 w-4'])
                </span>
                <p class="flex-1 text-rose-900">{{ $flashError }}</p>
            </div>
        @endif
    </div>
@endif

@foreach ($flashPanels as $key => $value)
    <div data-alert class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-4 text-[13px] text-indigo-900">
        @if (is_array($value) || $value instanceof \Illuminate\Support\Collection)
            @includeIf('partials.flash-panel', ['key' => $key, 'value' => $value])
        @else
            <pre class="whitespace-pre-wrap font-sans">{{ $value }}</pre>
        @endif
    </div>
@endforeach

@if ($errors->any())
    <div data-alert class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3">
        <span class="mt-0.5 shrink-0 text-rose-600">
            @include('partials.icon', ['name' => 'warning', 'size' => 'h-4 w-4'])
        </span>
        <ul class="flex-1 list-inside list-disc space-y-1 text-[13px] leading-relaxed text-rose-900">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
