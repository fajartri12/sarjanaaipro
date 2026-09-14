import '../css/app.css';
import 'flowbite';

// Tema gelap. Status awal sudah dipasang partials/theme-head.blade.php.
const root = document.documentElement;

// `theme-color` mengikuti tema supaya bilah alamat mobile tidak nyala.
const themeColor = (dark) => {
    document.querySelector('meta[name="theme-color"]')
        ?.setAttribute('content', dark ? '#111827' : '#ffffff');
};

themeColor(root.dataset.theme === 'dark');

document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const dark = root.dataset.theme !== 'dark';
        root.dataset.theme = dark ? 'dark' : 'light';
        localStorage.theme = dark ? 'dark' : 'light';
        themeColor(dark);
    });
});

// Sidebar ringkas, hanya dipakai mulai breakpoint lg.
document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
    button.setAttribute('aria-expanded', String(root.dataset.sidebar !== 'collapsed'));

    button.addEventListener('click', () => {
        const collapsed = root.dataset.sidebar === 'collapsed';
        root.dataset.sidebar = collapsed ? 'expanded' : 'collapsed';
        localStorage.sidebar = root.dataset.sidebar;
        button.setAttribute('aria-expanded', String(collapsed));
    });
});

/*
 * Progres "AI sedang bekerja" + kirim tanpa muat ulang.
 *
 * Form dengan `data-ai-stages` dikirim lewat fetch(). Overlay progress hidup
 * selama server bekerja, lalu isi halaman ditukar di tempat: tidak ada reload,
 * tidak ada kedip putih. Kalau server mengalihkan ke halaman lain (misalnya
 * bikin sesi sempro), barulah navigasi penuh dijalankan — karena memang
 * halamannya berbeda.
 *
 * data-ai-stages: JSON {"title": "...", "stages": ["...", "..."]}
 * data-ajax:      tanpa overlay, hanya kirim tanpa muat ulang
 *
 * Cara kerja: server tetap membalas HTML utuh seperti biasa. Bagian
 * [data-page-content] dari balasan itu yang menggantikan yang lama. Jadi
 * controller tidak perlu tahu-menahu soal AJAX.
 */

const STEP_MS = 6500;
const TICK_MS = 500;
const CEILING = 95;

const run = document.getElementById('ai-run');

if (run) {
    const panel = run.querySelector('[data-ai-panel]');
    const title = run.querySelector('[data-ai-title]');
    const note = run.querySelector('[data-ai-note]');
    const barWrap = run.querySelector('[data-ai-bar-wrap]');
    const bar = run.querySelector('[data-ai-bar]');
    const stageList = run.querySelector('[data-ai-stages]');
    const elapsed = run.querySelector('[data-ai-elapsed]');

    let stages = [];
    let step = 0;
    let progress = 8;
    let startedAt = 0;
    let timers = [];
    let lastActive = null;
    let busyButtons = [];

    const stop = () => {
        busyButtons.forEach((b) => (b.disabled = false));
        busyButtons = [];

        if (run.classList.contains('hidden')) {
            return;
        }

        timers.forEach(clearInterval);
        timers = [];
        run.classList.add('hidden');
        document.body.style.overflow = '';
        lastActive?.focus();
    };

    // Satu tahap diberi denyut, yang sudah lewat menjadi hijau.
    const paint = () => {
        stageList.querySelectorAll('li').forEach((li, index) => {
            const done = index < step;
            const live = index === step;

            li.firstElementChild.className = done ? 'ai-dot-done' : live ? 'ai-dot-live' : 'ai-dot';
            li.className = done || !live
                ? 'flex items-center gap-1.5 text-[13px] text-gray-400'
                : 'flex items-center gap-1.5 text-[13px] font-medium text-gray-900';
        });
    };

    const tick = () => {
        const total = Math.floor((performance.now() - startedAt) / 1000);

        elapsed.textContent = total < 5 ? 'baru saja' : `${total} detik`;

        if (total === 15) {
            note.textContent = 'Masih berjalan. Bagian panjang memang butuh waktu.';
        }

        progress += (CEILING - progress) * 0.035;
        bar.style.width = `${progress.toFixed(1)}%`;
        barWrap.setAttribute('aria-valuenow', String(Math.round(progress)));
    };

    const start = (config) => {
        stages = config.stages ?? [];
        step = 0;
        progress = 8;
        startedAt = performance.now();

        title.textContent = config.title ?? 'AI sedang bekerja';
        note.textContent = 'Jangan tutup atau muat ulang halaman ini.';

        stageList.replaceChildren(
            ...stages.map((label) => {
                const li = document.createElement('li');
                const dot = document.createElement('span');
                dot.className = 'ai-dot';
                const text = document.createElement('span');
                text.textContent = label;
                li.append(dot, text);
                return li;
            }),
        );

        paint();
        bar.style.width = '8%';

        run.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        panel.focus();

        timers = [
            setInterval(tick, TICK_MS),
            setInterval(() => {
                if (step < stages.length - 1) {
                    step += 1;
                    paint();
                }
            }, STEP_MS),
        ];
    };

    // Tampilkan flash message sementara di [data-flash-container]
    const showFlash = (type, message) => {
        const container = document.querySelector('[data-flash-container]');
        if (!container) return;

        const colors = {
            success: 'border-emerald-200 bg-emerald-50 text-emerald-900 [&_svg]:text-emerald-600',
            error: 'border-rose-200 bg-rose-50 text-rose-900 [&_svg]:text-rose-600',
        };
        const icons = {
            success: '<path d="m4.5 12.75 6 6 9-13.5" />',
            error: '<path d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />',
        };

        // Semua pesan singkat berkumpul di satu tumpukan melayang.
        let stack = container.querySelector('[data-toast-stack]');
        if (!stack) {
            stack = document.createElement('div');
            stack.setAttribute('data-toast-stack', '');
            stack.setAttribute('aria-live', 'polite');
            stack.className = 'pointer-events-none fixed inset-x-4 top-4 z-[80] flex flex-col items-end gap-3 sm:left-auto sm:w-full sm:max-w-sm';
            container.append(stack);
        }

        const alert = document.createElement('div');
        alert.setAttribute('data-alert', '');
        alert.setAttribute('data-toast', '');
        alert.setAttribute('role', type === 'error' ? 'alert' : 'status');
        alert.className = `ui-toast ${colors[type] || colors.error}`;
        alert.innerHTML = `
            <span class="mt-0.5 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                    ${icons[type] || icons.error}
                </svg>
            </span>
            <p class="flex-1">${message}</p>
        `;
        stack.prepend(alert);
        dismissToast(alert);
    };

    // Toast otomatis hilang setelah 5 detik.
    const dismissToast = (el) => {
        setTimeout(() => {
            el.style.transition = 'opacity 0.2s ease-out, transform 0.2s ease-out';
            el.style.opacity = '0';
            el.style.transform = 'translateX(1rem)';
            setTimeout(() => el.remove(), 200);
        }, 5000);
    };

    // Pasang auto-dismiss untuk toast yang sudah ada di halaman (refresh).
    document.querySelectorAll('[data-alert][data-toast]').forEach(dismissToast);

    // Tukar konten halaman dengan HTML dari server tanpa reload.
    const swapPage = (html) => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        // Ganti judul tab
        const newTitle = doc.querySelector('title');
        if (newTitle) document.title = newTitle.textContent;

        // Ganti konten utama
        const newMain = doc.querySelector('[data-page-content]');
        const oldMain = document.querySelector('[data-page-content]');
        if (newMain && oldMain) {
            oldMain.innerHTML = newMain.innerHTML;
        }

        // Ganti flash messages
        const newFlash = doc.querySelector('[data-flash-container]');
        const oldFlash = document.querySelector('[data-flash-container]');
        if (newFlash && oldFlash) {
            oldFlash.innerHTML = newFlash.innerHTML;
        }

        // Ganti header
        const newHeader = doc.querySelector('[data-page-header]');
        const oldHeader = document.querySelector('[data-page-header]');
        if (newHeader && oldHeader) {
            oldHeader.innerHTML = newHeader.innerHTML;
        }

        // Alihkan fokus ke konten baru
        oldMain?.querySelector('h1, h2, [tabindex="-1"]')?.focus();
    };

    // Kirim form via fetch, swap konten tanpa reload.
    const submitAjax = async (form, config) => {
        start(config);

        const formData = new FormData(form);
        // `form.action` bisa kembali elemen <input name="action"> — pakai
        // getAttribute supaya selalu dapat string URL.
        const action = form.getAttribute('action');
        const method = form.getAttribute('method') || 'POST';

        try {
            const res = await fetch(action, {
                method: method,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            const html = await res.text();

            stop();

            // Response error: coba ekstrak pesan dari JSON, kalau bukan JSON baru tampilkan generic.
            if (!res.ok) {
                const contentType = res.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    try {
                        const json = JSON.parse(html);
                        showFlash('error', json.message || json.error || `Gagal (${res.status}). Coba lagi nanti.`);
                    } catch {
                        showFlash('error', `Gagal (${res.status}). Coba lagi nanti.`);
                    }
                } else {
                    showFlash('error', `Gagal (${res.status}). Coba lagi nanti.`);
                }
                return;
            }

            /*
             * `back()` pun berupa redirect, jadi `res.redirected` tidak bisa
             * dipakai untuk membedakan "halaman sama" dari "halaman lain".
             * Patokannya alamat akhir: kalau path-nya beda, memang pindah
             * halaman dan navigasi penuh yang benar.
             */
            const finalUrl = new URL(res.url, window.location.origin);

            if (finalUrl.pathname !== window.location.pathname) {
                window.location.href = res.url;
                return;
            }

            swapPage(html);

            // Re-init komponen Flowbite di konten baru
            if (typeof initFlowbite === 'function') {
                initFlowbite();
            }
        } catch (err) {
            stop();
            // Fallback: submit biasa kalau fetch gagal total
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = '_fallback';
            hidden.value = '1';
            form.appendChild(hidden);
            HTMLFormElement.prototype.submit.call(form);
        }
    };

    // Tombol Kembali memakai cache: halaman muncul lagi, lapisan harus hilang.
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) stop();
    });

    // Jalan keluar kalau jawaban menggantung.
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') stop();
    });

    // Tangkap submit form AI
    document.addEventListener('submit', (event) => {
        const form = event.target;
        const raw = form.dataset?.aiStages;
        const hasOverlay = !!raw;
        const hasAjax = form.hasAttribute('data-ajax') || hasOverlay;

        // Bukan form AJAX: biarkan default
        if (!hasAjax) return;

        // Cegah submit default
        event.preventDefault();

        // Kunci tombol supaya klik ganda tidak mengirim dua permintaan.
        lastActive = event.submitter || document.activeElement;
        busyButtons = [...form.querySelectorAll('button[type="submit"], input[type="submit"]')];
        busyButtons.forEach((b) => (b.disabled = true));

        try {
            const config = hasOverlay ? JSON.parse(raw) : {};
            submitAjax(form, config);
        } catch {
            // JSON rusak: fallback ke submit biasa
            form.submit();
        }
    });
}

// Toggle show/hide password — respects data-target, fallback ke #password
document.querySelectorAll('[data-show-password]').forEach((button) => {
    button.addEventListener('click', () => {
        const targetId = button.dataset.target;
        const input = targetId ? document.getElementById(targetId) : button.closest('form')?.querySelector('#password');

        if (!input) return;

        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');

        const svg = button.querySelector('svg');

        if (svg) {
            svg.innerHTML = show
                ? '<path d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />'
                : '<path d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />';
        }
    });
});

// ── Mobile drawer ──────────────────────────────────────────────────────
(() => {
    const drawer = document.getElementById('mobile-menu');
    if (!drawer) return;

    const backdrop = document.getElementById('mobile-menu-backdrop');
    const panel = document.getElementById('mobile-menu-panel-inner');
    const btn = document.getElementById('mobile-menu-btn');
    const menuIcon = btn?.querySelector('.menu-icon');
    const closeIcon = btn?.querySelector('.close-icon');

    let open = false;

    function toggle(forceState) {
        open = forceState ?? !open;

        if (open) {
            drawer.dataset.state = 'open';
            drawer.setAttribute('aria-hidden', 'false');
            requestAnimationFrame(() => {
                backdrop?.classList.remove('opacity-0');
                backdrop?.classList.add('opacity-100');
                panel?.classList.remove('translate-x-full');
                panel?.classList.add('translate-x-0');
            });
            menuIcon?.classList.add('hidden');
            closeIcon?.classList.remove('hidden');
            closeIcon?.classList.add('block');
            btn?.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        } else {
            backdrop?.classList.add('opacity-0');
            backdrop?.classList.remove('opacity-100');
            panel?.classList.add('translate-x-full');
            panel?.classList.remove('translate-x-0');
            menuIcon?.classList.remove('hidden');
            closeIcon?.classList.add('hidden');
            closeIcon?.classList.remove('block');
            btn?.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
            setTimeout(() => {
                if (!open) {
                    drawer.dataset.state = 'closed';
                    drawer.setAttribute('aria-hidden', 'true');
                }
            }, 220);
        }
    }

    btn?.addEventListener('click', () => toggle());
    backdrop?.addEventListener('click', () => toggle(false));

    document.addEventListener('keydown', (e) => {
        if (open && e.key === 'Escape') toggle(false);
    });

    drawer.querySelectorAll('a, button[type="submit"]').forEach((link) => {
        link.addEventListener('click', () => toggle(false));
    });
})();

// ── User dropdown (topbar) ────────────────────────────────────────────
(() => {
    const btn = document.getElementById('user-menu-btn');
    const panel = document.getElementById('user-menu-panel');
    if (!btn || !panel) return;

    let open = false;

    function toggle(forceState) {
        open = forceState ?? !open;
        if (open) {
            panel.classList.remove('invisible', 'opacity-0', 'scale-95');
            panel.classList.add('visible', 'opacity-100', 'scale-100');
            btn.setAttribute('aria-expanded', 'true');
        } else {
            panel.classList.remove('visible', 'opacity-100', 'scale-100');
            panel.classList.add('invisible', 'opacity-0', 'scale-95');
            btn.setAttribute('aria-expanded', 'false');
        }
    }

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        toggle();
    });

    document.addEventListener('click', (e) => {
        if (open && !panel.contains(e.target) && !btn.contains(e.target)) {
            toggle(false);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (open && e.key === 'Escape') toggle(false);
    });
})();

// ── Notifikasi dropdown (topbar) ──────────────────────────────────────
(() => {
    const btn = document.getElementById('notif-btn');
    const panel = document.getElementById('notif-panel');
    const list = document.getElementById('notif-list');
    const badge = document.getElementById('notif-badge');
    const markAll = document.getElementById('notif-mark-all');
    if (!btn || !panel) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const listUrl = panel.dataset.listUrl;
    const readAllUrl = panel.dataset.readAllUrl;
    let open = false;
    let loaded = false;

    function toggle(forceState) {
        open = forceState ?? !open;
        panel.classList.toggle('invisible', !open);
        panel.classList.toggle('opacity-0', !open);
        panel.classList.toggle('scale-95', !open);
        panel.classList.toggle('visible', open);
        panel.classList.toggle('opacity-100', open);
        panel.classList.toggle('scale-100', open);
        btn.setAttribute('aria-expanded', String(open));
    }

    function render(notifications) {
        if (!notifications.length) {
            list.innerHTML = '<p class="px-4 py-6 text-center text-[13px] text-gray-400">Belum ada notifikasi.</p>';
            return;
        }

        list.innerHTML = notifications.map((n) => {
            const tag = n.url ? 'a' : 'div';
            const href = n.url ? ` href="${n.url}"` : '';
            const dot = n.read ? '' : '<span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-blue-500"></span>';
            return `<${tag}${href} class="flex gap-2.5 border-b border-gray-100 px-4 py-3 transition last:border-0 hover:bg-gray-50">
                ${dot}
                <div class="min-w-0 flex-1">
                    <p class="text-[13px] font-medium text-gray-800">${n.title}</p>
                    ${n.body ? `<p class="mt-0.5 text-[13px] leading-relaxed text-gray-500">${n.body}</p>` : ''}
                    <p class="mt-1 text-[11px] text-gray-400">${n.at}</p>
                </div>
            </${tag}>`;
        }).join('');
    }

    /* Kerangka abu-abu selama data belum datang, supaya panel tidak kosong. */
    function showSkeleton() {
        list.innerHTML = `<div class="space-y-3 px-4 py-4" aria-hidden="true">
            <div class="ui-skeleton h-3.5 w-3/4"></div>
            <div class="ui-skeleton h-3 w-1/2"></div>
            <div class="ui-skeleton h-3.5 w-2/3"></div>
            <div class="ui-skeleton h-3 w-2/5"></div>
        </div>`;
    }

    async function load() {
        try {
            const res = await fetch(listUrl, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            render(data.notifications || []);
            if (badge) {
                badge.textContent = data.unread;
                badge.classList.toggle('hidden', !data.unread);
            }
        } catch (e) {
            list.innerHTML = '<p class="px-4 py-6 text-center text-[13px] text-rose-500">Gagal memuat notifikasi.</p>';
        }
    }

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        toggle();
        if (open && !loaded) {
            loaded = true;
            showSkeleton();
            load();
        }
    });

    markAll?.addEventListener('click', async () => {
        await fetch(readAllUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
        });
        badge?.classList.add('hidden');
        load();
    });

    document.addEventListener('click', (e) => {
        if (open && !panel.contains(e.target) && !btn.contains(e.target)) toggle(false);
    });

    document.addEventListener('keydown', (e) => {
        if (open && e.key === 'Escape') toggle(false);
    });
})();

// ── Salin nomor rekening ───────────────────────────────────────────────
// Delegasi supaya tetap hidup setelah [data-page-content] ditukar lewat AJAX.
document.addEventListener('click', async (e) => {
    const button = e.target.closest('[data-copy]');
    if (!button) return;

    try {
        await navigator.clipboard.writeText(button.dataset.copy);
    } catch {
        // Clipboard ditolak (izin / bukan HTTPS): teksnya sudah `select-all`,
        // jadi user masih bisa menyalin manual. Umpan balik tetap diberikan.
    }

    /*
     * Warna dipasang lewat inline style, bukan kelas: selama kursor masih di
     * atas tombol, `hover:text-*` menang atas `text-emerald-*` sehingga
     * umpan baliknya tidak pernah terlihat. Ikon ditukar ke centang karena
     * warna saja tidak cukup jelas.
     */
    const svg = button.querySelector('svg');
    button._copyIcon ??= svg?.innerHTML;
    button.style.color = document.documentElement.dataset.theme === 'dark' ? '#34d399' : '#059669';
    if (svg) {
        svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />';
    }

    clearTimeout(button._copyTimer);
    button._copyTimer = setTimeout(() => {
        button.style.color = '';
        if (svg) svg.innerHTML = button._copyIcon;
    }, 1400);
});
