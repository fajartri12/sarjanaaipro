import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import flowbite from 'flowbite/plugin';

/** @type {import('tailwindcss').Config} */
export default {
    // Tombol tema mengubah atribut `data-theme` di <html>, bukan prefers-color-scheme.
    // Tanpa ini, varian `dark:` di view hanya ikut setelan OS.
    darkMode: ['selector', '[data-theme="dark"]'],

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        /*
         * app.js menyusun beberapa kelas saat halaman berjalan (titik tahap
         * pada lapisan progres AI). Tanpa berkas ini, kelas tersebut ikut
         * terhapus pada build produksi.
         */
        './resources/js/**/*.js',
        './node_modules/flowbite/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                // Figtree dimuat lewat <link> di kedua layout; font sistem jadi
                // cadangan kalau perangkat sedang offline.
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                serif: ['Lora', 'Georgia', 'Cambria', ...defaultTheme.fontFamily.serif],
            },
            boxShadow: {
                // Tiga lapis: kontak rapat, badan, lalu sebaran panjang yang lembut.
                // Kartu tidak lagi memakai border, jadi bayangan ini yang
                // memisahkannya dari latar halaman.
                card: '0 1px 2px 0 rgb(16 24 40 / 0.05), 0 2px 5px -1px rgb(16 24 40 / 0.06), 0 10px 20px -12px rgb(16 24 40 / 0.10)',
                'card-md': '0 2px 4px -1px rgb(16 24 40 / 0.07), 0 6px 12px -4px rgb(16 24 40 / 0.09), 0 16px 28px -16px rgb(16 24 40 / 0.14)',
                'card-lg': '0 8px 20px -6px rgb(16 24 40 / 0.12), 0 20px 36px -18px rgb(16 24 40 / 0.16)',
            },
            keyframes: {
                'fade-up': {
                    from: { opacity: '0', transform: 'translateY(4px)' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
            },
            animation: {
                'fade-up': 'fade-up 220ms ease-out both',
            },
        },
    },

    plugins: [forms, flowbite],
};
