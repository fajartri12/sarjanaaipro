# PROMPT DEVELOPMENT

## Modul Jurnal & Referensi — Sarjana AI


Bangun modul **Jurnal & Referensi** untuk SaaS **Sarjana AI**.

Modul ini adalah **bagian dari Project Skripsi**, bukan modul global yang berdiri sendiri.

Setiap jurnal, referensi, citation, research gap, dan daftar pustaka harus terhubung dengan:

```text
User
  ↓
Project Skripsi
  ↓
Jurnal & Referensi
```

---

# 1. Tujuan Modul

Modul Jurnal & Referensi digunakan mahasiswa untuk:

* Mencari jurnal yang relevan dengan skripsinya.
* Menyimpan jurnal ke project.
* Upload jurnal PDF.
* Membaca dan melihat metadata jurnal.
* Menganalisis jurnal menggunakan AI.
* Menemukan research gap.
* Membandingkan penelitian terdahulu.
* Membuat matriks penelitian terdahulu.
* Mengelola referensi yang digunakan.
* Membuat citation.
* Memasukkan citation ke draft skripsi.
* Membuat daftar pustaka otomatis.
* Mengecek konsistensi citation dan referensi.

Modul harus terasa seperti **Research Workspace untuk satu project skripsi**.

---

# 2. Struktur Menu

Gunakan menu berikut:

```text
JURNAL & REFERENSI

├── 🔎 Cari Jurnal
├── 📥 Jurnal Tersimpan
├── 📄 Upload Jurnal
├── 🤖 Analisis Jurnal
├── 🧠 Research Gap
├── 📊 Matriks Penelitian
├── 📚 Daftar Referensi
├── 🔗 Citation
├── 📑 Daftar Pustaka
└── ✅ Cek Referensi
```

Jangan menambahkan menu global seperti:

```text
Research Dashboard
AI Chat Global
AI Assistant Global
Collections Global
```

Semua fungsi harus berhubungan dengan **Project Skripsi aktif**.

---

# 3. 🔎 Cari Jurnal

## Tujuan

Membantu mahasiswa menemukan jurnal yang relevan dengan project skripsinya.

Route:

```text
/projects/{project}/journals/search
```

## Input

```text
Keyword
Topik
Variabel
Objek Penelitian
Metode
Tahun Publikasi
Author
```

Contoh:

```text
Topik:
Artificial Intelligence

Objek:
UMKM

Keyword:
AI adoption
SME
digital transformation

Tahun:
2021 - 2026
```

## Filter

```text
Tahun
Jenis publikasi
Metode
Topik
Bahasa
Open Access
```

## Hasil

Setiap hasil jurnal menampilkan:

```text
Judul
Penulis
Tahun
Nama jurnal
Publisher
DOI
Abstract
Source
Relevance
```

Action:

```text
[ Lihat ]
[ Simpan ]
[ Analisis ]
[ Tambahkan ke Referensi ]
```

Jangan mengarang jurnal, author, DOI, publisher, atau metadata.

Jika data berasal dari sumber eksternal, simpan:

```text
source
source_id
doi
url
```

---

# 4. 📥 Jurnal Tersimpan

Route:

```text
/projects/{project}/journals
```

Menampilkan jurnal yang telah disimpan ke project.

Tabs:

```text
Semua
Dianalisis
Belum Dianalisis
Digunakan
Belum Digunakan
Favorit
```

Card jurnal:

```text
Judul
Penulis
Tahun
Journal
Status
```

Status:

```text
Saved
Analyzed
Used
Favorite
```

Action:

```text
[ Detail ]
[ Analisis ]
[ Tambahkan ke Referensi ]
[ Favorite ]
[ Hapus ]
```

---

# 5. 📄 Upload Jurnal

Route:

```text
/projects/{project}/journals/upload
```

Support:

```text
PDF
```

Flow:

```text
Upload PDF
      ↓
Validasi
      ↓
Simpan File
      ↓
Extract Metadata
      ↓
Extract Text
      ↓
Analisis
```

Metadata yang dicoba diambil:

```text
Title
Author
Year
Journal
Volume
Issue
Pages
DOI
Publisher
Abstract
Keywords
```

File harus private.

Mahasiswa hanya boleh mengakses file miliknya sendiri dan file yang terhubung dengan project miliknya.

---

# 6. Detail Jurnal

Route:

```text
/projects/{project}/journals/{journal}
```

Tampilkan:

```text
Informasi Jurnal
Abstract
Keywords
Authors
Publication
DOI
Source
```

Action:

```text
[ Baca PDF ]
[ Analisis dengan AI ]
[ Tambahkan ke Referensi ]
[ Tambahkan ke Matriks ]
[ Favorite ]
```

Jika jurnal belum mempunyai PDF:

```text
[ Upload PDF ]
```

---

# 7. 🤖 Analisis Jurnal

Route:

```text
/projects/{project}/journals/{journal}/analysis
```

Analisis harus khusus untuk kebutuhan skripsi.

AI harus menghasilkan:

```text
1. Ringkasan Penelitian

2. Latar Belakang

3. Masalah Penelitian

4. Tujuan Penelitian

5. Rumusan Masalah

6. Landasan Teori

7. Metode Penelitian

8. Populasi

9. Sampel

10. Variabel

11. Teknik Pengumpulan Data

12. Teknik Analisis

13. Hasil Penelitian

14. Kesimpulan

15. Keterbatasan

16. Research Gap

17. Novelty

18. Relevansi dengan Project Skripsi
```

Gunakan output terstruktur.

Simpan hasil analisis ke database.

Jangan menjalankan AI ulang setiap kali halaman dibuka.

---

# 8. 🧠 Research Gap

Route:

```text
/projects/{project}/research-gap
```

User memilih beberapa jurnal:

```text
☑ Jurnal A
☑ Jurnal B
☑ Jurnal C
☑ Jurnal D
```

Klik:

```text
[ Analisis Research Gap ]
```

AI membandingkan jurnal.

Output:

```text
Topik yang sudah diteliti
Variabel yang sudah digunakan
Objek yang sudah digunakan
Metode yang sudah digunakan
Keterbatasan penelitian sebelumnya
Area yang belum banyak diteliti
Potensi Research Gap
Potensi Novelty
```

Tambahkan:

```text
Rekomendasi:
- Variabel
- Objek
- Metode
- Populasi
- Research Question
```

Gunakan disclaimer akademik yang sesuai:

> "Research gap ini disusun berdasarkan jurnal yang dipilih dan dianalisis dalam project."

Jangan menyatakan bahwa AI telah membuktikan seluruh literatur dunia.

---

# 9. 📊 Matriks Penelitian

Route:

```text
/projects/{project}/previous-studies
```

Digunakan untuk membantu BAB II.

Kolom:

```text
No
Peneliti
Tahun
Judul
Metode
Variabel
Objek
Populasi
Sampel
Hasil
Keterbatasan
Research Gap
```

Fitur:

```text
[ Tambah Penelitian ]
[ Ambil dari Jurnal ]
[ Generate dengan AI ]
[ Edit ]
[ Hapus ]
```

AI hanya mengisi berdasarkan jurnal yang dipilih.

Jangan membuat data penelitian yang tidak ditemukan di sumber.

---

# 10. 📚 Daftar Referensi

Route:

```text
/projects/{project}/references
```

Referensi berbeda dengan jurnal tersimpan.

Jurnal tersimpan:

```text
Sumber yang dikumpulkan mahasiswa
```

Referensi:

```text
Sumber yang digunakan dalam skripsi
```

Tampilkan:

```text
Total Referensi
Digunakan
Belum Digunakan
Metadata Tidak Lengkap
```

Informasi:

```text
Author
Year
Title
Journal
Publisher
DOI
URL
Type
Status
```

Status:

```text
Used
Unused
Incomplete
```

---

# 11. 🔗 Citation

Route:

```text
/projects/{project}/references/citations
```

Support:

```text
APA 7
IEEE
Harvard
Vancouver
```

Contoh APA:

```text
(Smith, 2025)
```

Contoh IEEE:

```text
[12]
```

Fitur:

```text
Copy Citation
Insert into Draft
Edit Citation
```

Citation harus dibuat dari metadata referensi.

Jangan menggunakan AI untuk mengarang informasi bibliografi.

---

# 12. Integrasi Citation dengan Draft

Fitur ini wajib terintegrasi dengan modul Draft Skripsi.

Di editor:

```text
[ + Citation ]
```

Modal:

```text
Cari Referensi

Smith, 2025
Wijaya, 2024
Putra, 2023

[ Insert ]
```

Setelah insert:

```text
Draft
 ↓
Citation
 ↓
Reference
```

Referensi otomatis berubah:

```text
Unused → Used
```

Simpan relasi:

```text
draft_section_id
reference_id
citation
position
```

Jika memungkinkan, citation memiliki posisi/anchor agar tetap terhubung saat draft diedit.

---

# 13. 📑 Daftar Pustaka

Route:

```text
/projects/{project}/references/bibliography
```

Daftar pustaka harus dibuat berdasarkan referensi yang benar-benar digunakan.

Flow:

```text
Citation
   ↓
Used Reference
   ↓
Bibliography
```

Support:

```text
APA 7
IEEE
Harvard
Vancouver
```

Fitur:

```text
[ Generate ]
[ Refresh ]
[ Copy ]
[ Insert ke Draft ]
[ Export DOCX ]
[ Export PDF ]
```

Urutan dan format harus mengikuti style yang dipilih.

---

# 14. ✅ Cek Referensi

Route:

```text
/projects/{project}/references/check
```

Sistem memeriksa:

### Citation → Reference

```text
✓ Semua citation memiliki reference
⚠ 2 citation tidak ditemukan
```

### Reference → Citation

```text
✓ 28 reference digunakan
⚠ 5 reference belum pernah dikutip
```

### Metadata

```text
✓ Author
✓ Year
✓ Title
⚠ DOI missing
```

### Duplicate

```text
⚠ 2 reference kemungkinan duplicate
```

### Konsistensi

```text
Citation:
Smith, 2025

Reference:
Smith, J. (2025)

✓ Match
```

---

# 15. Hubungan dengan Project Skripsi

Semua data harus menggunakan:

```text
project_id
```

Contoh:

```text
Project Skripsi A
├── Jurnal A
├── Jurnal B
├── Reference A
├── Reference B
├── Research Gap
└── Matriks Penelitian

Project Skripsi B
├── Jurnal C
├── Reference C
└── Research Gap
```

User tidak boleh melihat jurnal/reference project lain.

---

# 16. Database

Buat minimal tabel:

```text
journals
journal_authors
documents
journal_analyses

references
citations

research_gaps
research_gap_journals

previous_studies
previous_study_journals

reference_collections
reference_collection_items
```

Relasi utama:

```text
User
 ↓
Project
 ↓
Journal
 ├── Document
 ├── Analysis
 └── Reference

Project
 ├── Research Gap
 ├── Previous Studies
 └── Draft
       ↓
    Citation
       ↓
    Reference
```

---

# 17. AI Service

Buat service khusus:

```text
app/Services/AI/Research/
```

Class:

```text
JournalAnalysisService
ResearchGapService
PreviousStudyService
ReferenceRecommendationService
CitationService
```

Contoh:

```php
$analysis = $journalAnalysisService
    ->analyze($journal);
```

Jangan:

```php
// Controller
OpenAI::chat(...)
```

Semua provider AI harus melalui abstraction.

---

# 18. Queue

Gunakan Laravel Queue untuk:

```text
ProcessJournalPdf
ExtractJournalMetadata
AnalyzeJournal
GenerateResearchGap
GeneratePreviousStudyMatrix
GenerateEmbedding
```

Flow:

```text
User
 ↓
Laravel
 ↓
Queue
 ↓
Worker
 ↓
AI / PDF Processing
 ↓
Database
```

Tampilkan status:

```text
Pending
Processing
Completed
Failed
```

---

# 19. UI/UX

Gunakan Tailwind CSS.

Design:

```text
Clean
Modern
Academic
Professional
Minimal
Responsive
```

Komponen:

```text
Card
Tabs
Modal
Drawer
Table
Search
Filter
Badge
Progress
Toast
Skeleton
Empty State
Dropdown
Pagination
```

Gunakan warna/status secara konsisten.

Contoh:

```text
✓ Analyzed
✓ Used
⚠ Incomplete
● Processing
```

---

# 20. Loading State AI

Jangan hanya menampilkan spinner.

Gunakan:

```text
Menganalisis jurnal...

✓ Membaca dokumen
✓ Mengidentifikasi metodologi
● Menganalisis hasil
○ Mencari research gap
○ Menentukan relevansi
```

---

# 21. Error Handling

Jika PDF gagal:

```text
Jurnal tidak dapat diproses.

Kemungkinan penyebab:
- File rusak
- PDF tidak memiliki text layer
- File terenkripsi/password protected

[ Upload Ulang ]
```

Jika AI gagal:

```text
Analisis belum berhasil.

[ Coba Lagi ]
```

Jangan kehilangan data yang sudah tersimpan.

---

# 22. Authorization

Gunakan Laravel Policy.

Setiap request harus memvalidasi:

```text
User
 ↓
Project ownership
 ↓
Journal ownership
 ↓
Reference ownership
```

Contoh:

```text
User A
 └── Project A
      └── Journal A ✓

User A
 └── Project B milik User B ✗
```

---

# 23. Acceptance Criteria

Fitur dianggap selesai jika:

```text
✓ User dapat mencari jurnal
✓ User dapat menyimpan jurnal ke project
✓ User dapat upload PDF
✓ Metadata dapat disimpan
✓ PDF dapat diproses
✓ Jurnal dapat dianalisis AI
✓ Hasil analisis tersimpan
✓ User dapat memilih beberapa jurnal
✓ Research gap dapat dibuat
✓ Matriks penelitian dapat dibuat
✓ Referensi dapat dikelola
✓ Citation dapat dibuat
✓ Citation dapat dimasukkan ke draft
✓ Daftar pustaka dapat dibuat
✓ Citation checker berjalan
✓ Duplicate reference dapat dideteksi
✓ File bersifat private
✓ Authorization berjalan
✓ AI processing menggunakan queue
✓ AI usage tercatat
```

---

# 24. Prinsip Utama

Jangan membuat modul ini sebagai:

```text
❌ File Upload
❌ Chatbot AI
❌ PDF Viewer saja
❌ Reference List sederhana
```

Bangun sebagai:

```text
                 PROJECT SKRIPSI
                        │
                        ▼
              ┌───────────────────┐
              │ JURNAL & REFERENSI │
              └───────────────────┘
                        │
       ┌────────────────┼────────────────┐
       ▼                ▼                ▼
   Cari Jurnal      Analisis AI     Upload Jurnal
       │                │                │
       └────────────────┼────────────────┘
                        ▼
                 Research Gap
                        │
                        ▼
              Matriks Penelitian
                        │
                        ▼
                   Referensi
                        │
                        ▼
                    Citation
                        │
                        ▼
                 Draft Skripsi
                        │
                        ▼
                 Daftar Pustaka
                        │
                        ▼
                 Cek Referensi
```

**Prioritas implementasi:**

```text
Phase 1
├── Cari Jurnal
├── Jurnal Tersimpan
├── Upload Jurnal
├── Detail Jurnal
└── Daftar Referensi

Phase 2
├── Analisis Jurnal AI
├── Research Gap
└── Matriks Penelitian

Phase 3
├── Citation
├── Integrasi Draft
├── Daftar Pustaka
└── Cek Referensi

Phase 4
├── RAG Research
├── Compare Papers
└── Recommendation Engine
```

Hasil akhir harus terasa seperti **alat manajemen riset untuk satu skripsi**, bukan fitur AI yang berdiri sendiri.
