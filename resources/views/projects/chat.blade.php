@extends('layouts.app')

@section('title', 'Chat AI — ' . ($project->title ?: $project->name))

@section('header')
    <div class="flex items-center justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('projects.show', $project->id) }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 transition hover:text-blue-700">
                @include('partials.icon', ['name' => 'arrow-left', 'size' => 'h-3.5 w-3.5'])
                {{ $project->title ?: $project->name }}
            </a>
            <h1 class="mt-2 ui-page-title">Chat AI</h1>
            <p class="ui-page-sub">Asisten yang memahami konteks project Anda.</p>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-4">
        {{-- Sidebar: daftar percakapan --}}
        <div class="ui-card p-0 lg:col-span-1">
            <div class="border-b border-gray-100 p-4">
                <button type="button" id="btn-new-chat"
                        class="ui-btn-primary w-full justify-center gap-2">
                    @include('partials.icon', ['name' => 'plus', 'size' => 'h-4 w-4'])
                    Percakapan baru
                </button>
            </div>
            <div id="chat-list" class="max-h-[60vh] space-y-0.5 overflow-y-auto p-2">
                @forelse ($conversations as $conv)
                    <a href="{{ route('projects.chat.index', ['project' => $project->id, 'c' => $conv->id]) }}"
                       class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition
                              {{ $conversation?->id === $conv->id ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50' }}">
                        <span class="line-clamp-1 flex-1">{{ $conv->title }}</span>
                        @if ($conversation?->id === $conv->id)
                            @include('partials.icon', ['name' => 'chat', 'size' => 'h-4 w-4'])
                        @endif
                    </a>
                @empty
                    <p class="px-3 py-4 text-center text-xs text-gray-400">Belum ada percakapan.</p>
                @endforelse
            </div>
        </div>

        {{-- Area chat --}}
        <div class="ui-card flex flex-col lg:col-span-3 lg:min-h-[60vh]">
            @if ($conversation)
                <div class="flex-1 space-y-4 overflow-y-auto p-4" id="chat-messages">
                    @forelse ($conversation->messages as $msg)
                        <div class="flex gap-3 {{ $msg->role === 'user' ? 'flex-row-reverse' : '' }}">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-white
                                        {{ $msg->role === 'user' ? 'bg-blue-600' : 'bg-emerald-600' }}">
                                @include('partials.icon', ['name' => $msg->role === 'user' ? 'user' : 'sparkles', 'size' => 'h-4 w-4'])
                            </span>
                            <div class="max-w-[80%] rounded-xl px-4 py-2.5 text-sm leading-relaxed
                                        {{ $msg->role === 'user' ? 'bg-blue-50 text-gray-900' : 'bg-gray-50 text-gray-900' }}">
                                {!! nl2br(e($msg->content)) !!}
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-sm text-gray-400">Mulai percakapan…</p>
                    @endforelse
                </div>

                {{-- Input --}}
                <form id="chat-form" class="border-t border-gray-100 p-4">
                    <div class="flex gap-2">
                        <input type="text" id="chat-input" name="message" required minlength="2" maxlength="4000"
                               placeholder="Ketik pertanyaan Anda…"
                               class="ui-input flex-1" autocomplete="off">
                        <button type="submit" id="chat-submit" class="ui-btn-primary px-4">
                            @include('partials.icon', ['name' => 'send', 'size' => 'h-4 w-4'])
                        </button>
                    </div>
                    <p id="chat-quota" class="mt-2 text-xs text-gray-400"></p>
                </form>
            @else
                <div class="flex flex-1 flex-col items-center justify-center p-8 text-center">
                    <span class="mb-4 grid h-16 w-16 place-items-center rounded-2xl bg-gray-100 text-gray-400">
                        @include('partials.icon', ['name' => 'chat', 'size' => 'h-8 w-8'])
                    </span>
                    <h3 class="text-lg font-semibold text-gray-900">Belum ada percakapan</h3>
                    <p class="mt-1 text-sm text-gray-500">Klik tombol di atas untuk memulai.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Ikon avatar disiapkan di sini supaya JS tinggal menyalin markup-nya. --}}
    <template id="tpl-avatar-user">@include('partials.icon', ['name' => 'user', 'size' => 'h-4 w-4'])</template>
    <template id="tpl-avatar-ai">@include('partials.icon', ['name' => 'sparkles', 'size' => 'h-4 w-4'])</template>

    {{-- Modal: buat percakapan baru --}}
    <dialog id="new-chat-modal" class="rounded-xl p-0 shadow-xl backdrop:bg-gray-900/40">
        <form id="new-chat-form" method="POST" action="{{ route('projects.chat.new', $project->id) }}" class="w-80 p-5">
            @csrf
            <h3 class="mb-4 text-base font-semibold text-gray-900">Percakapan Baru</h3>
            <label for="new-chat-title" class="ui-label">Judul</label>
            <input type="text" id="new-chat-title" name="title" required maxlength="120" class="ui-input mb-4"
                   placeholder="Contoh: Metodologi penelitian">
            <div class="flex justify-end gap-2">
                <button type="button" class="ui-btn-secondary" id="new-chat-cancel">Batal</button>
                <button type="submit" class="ui-btn-primary">Buat</button>
            </div>
        </form>
    </dialog>
@endsection

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const chatSubmit = document.getElementById('chat-submit');
    const chatMessages = document.getElementById('chat-messages');
    const chatQuota = document.getElementById('chat-quota');
    const conversationId = {{ $conversation?->id ?? 'null' }};
    const projectId = {{ $project->id }};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Modal
    const newChatBtn = document.getElementById('btn-new-chat');
    const newChatModal = document.getElementById('new-chat-modal');
    const newChatCancel = document.getElementById('new-chat-cancel');

    newChatBtn?.addEventListener('click', () => newChatModal.showModal());
    newChatCancel?.addEventListener('click', () => newChatModal.close());
    newChatModal?.addEventListener('click', (e) => {
        if (e.target === newChatModal) newChatModal.close();
    });

    if (!chatForm) return;

    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const message = chatInput.value.trim();
        if (!message || !conversationId) return;

        chatSubmit.disabled = true;
        chatInput.disabled = true;

        // Optimistic: tampilkan pesan user
        const tplUser = document.getElementById('tpl-avatar-user').innerHTML;
        const tplAi = document.getElementById('tpl-avatar-ai').innerHTML;
        const userHtml = `<div class="flex gap-3 flex-row-reverse">
            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-blue-600 text-white">
                ${tplUser}
            </span>
            <div class="max-w-[80%] rounded-xl bg-blue-50 px-4 py-2.5 text-sm leading-relaxed text-gray-900">
                ${escapeHtml(message)}
            </div>
        </div>`;
        chatMessages.insertAdjacentHTML('beforeend', userHtml);
        const userBubble = chatMessages.lastElementChild;
        chatMessages.scrollTop = chatMessages.scrollHeight;
        chatInput.value = '';

        try {
            const res = await fetch(`/projects/${projectId}/chat/${conversationId}/send`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: JSON.stringify({ message }),
            });

            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                userBubble.remove();
                chatInput.value = message;
                alert(err.message || 'Gagal mengirim pesan.');
                return;
            }

            const data = await res.json();
            // Pesan user sudah tampil optimistik di atas; cukup tambahkan balasan AI.
            data.messages.forEach(msg => {
                if (msg.role !== 'assistant') return;
                const html = `<div class="flex gap-3">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-emerald-600 text-white">
                        ${tplAi}
                    </span>
                    <div class="max-w-[80%] rounded-xl bg-gray-50 px-4 py-2.5 text-sm leading-relaxed text-gray-900">
                        ${escapeHtml(msg.content).replace(/\n/g, '<br>')}
                    </div>
                </div>`;
                chatMessages.insertAdjacentHTML('beforeend', html);
            });

            chatMessages.scrollTop = chatMessages.scrollHeight;

            if (data.quota_left !== null && data.quota_left !== undefined) {
                chatQuota.textContent = `Sisa kuota: ${data.quota_left} pesan bulan ini`;
            }
        } catch (err) {
            userBubble.remove();
            chatInput.value = message;
            alert('Terjadi kesalahan. Silakan coba lagi.');
        } finally {
            chatSubmit.disabled = false;
            chatInput.disabled = false;
            chatInput.focus();
        }
    });

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>