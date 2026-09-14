{{-- Dijalankan sebelum cat pertama supaya tema gelap tidak berkedip putih. --}}
<script>
    (() => {
        // Terang adalah bawaan: hanya pilihan tersimpan yang menggelapkan
        // halaman. Setelan OS sengaja tidak dibaca, sudah pernah terbukti
        // bikin pengguna yang OS-nya gelap terkejut saat pertama membuka.
        const dark = localStorage.theme === 'dark';

        document.documentElement.dataset.theme = dark ? 'dark' : 'light';

        // Lebar sidebar juga ditentukan di sini agar tidak berkedip melebar.
        if (localStorage.sidebar === 'collapsed') {
            document.documentElement.dataset.sidebar = 'collapsed';
        }
    })();
</script>
