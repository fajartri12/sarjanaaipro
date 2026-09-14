# Sarjana AI — PRD & Struktur SaaS AI Skripsi

> **Tagline:** Dari Ide, Jadi Skripsi, Siap Sempro.

## 1. Konsep Produk

Sarjana AI adalah SaaS pendamping skripsi untuk mahasiswa dengan workflow:

```text
Ide Penelitian
    ↓
Cari & Validasi Judul
    ↓
Susun Draft Skripsi
    ↓
Research Assistant
    ↓
AI Reviewer
    ↓
Persiapan Sempro
    ↓
Simulasi Penguji
    ↓
Siap Sempro
```

Tujuan utama:
- Membantu mahasiswa menemukan ide dan judul penelitian.
- Membantu menyusun struktur dan draft skripsi secara sistematis.
- Membantu memahami jurnal dan menemukan research gap.
- Membantu melakukan review terhadap draft.
- Membantu latihan menghadapi seminar proposal.

> AI harus diposisikan sebagai asisten, bukan pengganti proses akademik mahasiswa. Output AI perlu diverifikasi dan disesuaikan dengan pedoman kampus.

---

# 2. Target Pengguna

## Mahasiswa
Pengguna utama yang membuat project skripsi, mencari judul, menyusun draft, mengelola referensi, dan latihan sempro.

## Admin
Mengelola pengguna, paket, transaksi, penggunaan AI, prompt, dan konfigurasi sistem.

## Pengembangan B2B/B2B2C
Tahap lanjutan dapat menambahkan:
- Universitas
- Fakultas
- Program Studi
- Dosen
- Mahasiswa

---

# 3. Modul Utama

```text
SARJANA AI
│
├── Landing Page
├── Authentication
│
├── Student App
│   ├── Dashboard
│   ├── Project Skripsi
│   ├── Cari Judul
│   ├── Draft Skripsi
│   ├── Research Assistant
│   ├── Referensi
│   ├── AI Reviewer
│   ├── Persiapan Sempro
│   ├── Progress
│   └── Profile
│
├── Subscription
│   ├── Pricing
│   ├── Checkout
│   ├── Billing
│   └── Usage
│
└── Admin Panel
    ├── Dashboard
    ├── Users
    ├── Projects
    ├── Subscription
    ├── Payments
    ├── AI Usage
    ├── Prompt Management
    └── Settings
```

---

# 4. Landing Page

Routes:

```text
/
 /features
 /how-it-works
 /pricing
 /faq
 /about
 /contact
 /login
 /register
```

Hero:

```text
Dari Ide Skripsi
Sampai Siap Sempro.

Asisten AI untuk membantu mahasiswa menemukan ide penelitian,
menyusun draft skripsi, memahami jurnal, dan mempersiapkan sempro.

[ Mulai Gratis ]
[ Lihat Cara Kerja ]
```

Feature utama:
- Cari Judul
- Susun Draft
- Research Assistant
- AI Reviewer
- Simulasi Sempro

---

# 5. Authentication

```text
/login
/register
/forgot-password
/reset-password
/email/verify
```

Fitur:
- Register
- Login
- Logout
- Forgot password
- Email verification
- Session management
- Role-based access

Role awal:

```text
admin
student
```

---

# 6. Dashboard Mahasiswa

Route:

```text
/dashboard
```

Komponen:

```text
- Greeting
- Active Project
- Progress Skripsi
- Quick Actions
- Recent Activities
- AI Recommendations
- Weekly Target
- Usage Quota
```

Quick Action:

```text
[ Cari Judul ]
[ Lanjutkan Draft ]
[ Analisis Jurnal ]
[ Latihan Sempro ]
```

Progress:

```text
Judul       ✓
BAB I       ✓
BAB II      ✓
BAB III     ✓
BAB IV      60%
BAB V       0%
Sempro      70%
```

---

# 7. Project Skripsi

Project menjadi pusat data mahasiswa.

Routes:

```text
/projects
/projects/create
/projects/{project}
/projects/{project}/settings
```

Informasi project:

```text
- Nama Project
- Judul
- Program Studi
- Universitas
- Dosen Pembimbing
- Jenis Penelitian
- Metode
- Status
- Progress
```

Struktur:

```text
Project
├── Information
├── Titles
├── Draft
├── Documents
├── Research
├── References
├── Reviewer
├── Sempro
└── Progress
```

---

# 8. Cari Judul

Routes:

```text
/titles
/titles/create
/titles/{title}
```

Input:

```text
Program Studi
Topik
Objek Penelitian
Lokasi
Metode
Keyword
Preferensi Penelitian
```

Contoh:

```text
Program Studi: Sistem Informasi
Topik: Artificial Intelligence
Objek: UMKM
Lokasi: Surabaya
Metode: Kuantitatif
Keyword: AI, UMKM, Digital
```

AI menghasilkan beberapa alternatif judul.

Setiap judul memiliki:

```text
- Judul
- Deskripsi
- Relevansi
- Kebaruan
- Feasibility
- Tingkat Kompleksitas
- Research Gap
- Variabel
- Metode
- Objek
- Rekomendasi Referensi
```

Action:

```text
[ Analisis ]
[ Simpan ]
[ Jadikan Judul Project ]
```

---

# 9. Validasi Judul

Analisis:

```text
Relevansi       92%
Kebaruan        81%
Feasibility     88%
Kompleksitas    72%
Research Gap    84%
```

Output:

```text
- Research Gap
- Rumusan Masalah
- Variabel
- Metode yang cocok
- Objek penelitian
- Saran perbaikan judul
- Risiko/keterbatasan penelitian
```

---

# 10. Draft Skripsi

Routes:

```text
/projects/{project}/draft
/projects/{project}/draft/{section}
```

Struktur:

```text
BAB I — PENDAHULUAN
├── 1.1 Latar Belakang
├── 1.2 Identifikasi Masalah
├── 1.3 Batasan Masalah
├── 1.4 Rumusan Masalah
├── 1.5 Tujuan Penelitian
└── 1.6 Manfaat Penelitian

BAB II — TINJAUAN PUSTAKA
├── 2.1 Landasan Teori
├── 2.2 Penelitian Terdahulu
├── 2.3 Kerangka Konseptual
└── 2.4 Hipotesis

BAB III — METODOLOGI
├── 3.1 Jenis Penelitian
├── 3.2 Objek Penelitian
├── 3.3 Populasi & Sampel
├── 3.4 Teknik Pengumpulan Data
├── 3.5 Variabel
└── 3.6 Teknik Analisis

BAB IV — HASIL & PEMBAHASAN
├── 4.1 Hasil
└── 4.2 Pembahasan

BAB V — PENUTUP
├── 5.1 Kesimpulan
└── 5.2 Saran
```

Editor membutuhkan:
- Autosave
- Word count
- Heading
- Bold/italic
- Lists
- Undo/redo
- AI actions
- Reference insertion
- Version history

AI actions:

```text
[ Generate ]
[ Improve ]
[ Expand ]
[ Summarize ]
[ Formalize ]
[ Continue ]
[ Explain ]
```

---

# 11. Research Assistant

Route:

```text
/research
```

Menu:

```text
Research
├── Search References
├── Upload Journal
├── Journal Library
├── Analyze Journal
├── Research Gap
├── Previous Research
└── Citation
```

Upload PDF:

```text
- Upload PDF
- Extract text
- Metadata detection
- Chunking
- Optional embedding
- Save document
```

Analisis jurnal:

```text
- Judul
- Author
- Tahun
- Tujuan
- Metode
- Dataset
- Hasil
- Keterbatasan
- Research Gap
- Relevansi
```

---

# 12. RAG Architecture

Untuk fitur jurnal yang lebih akurat, gunakan Retrieval-Augmented Generation.

```text
Upload PDF
    ↓
Text Extraction
    ↓
Chunking
    ↓
Embedding
    ↓
Vector Database
    ↓
User Question
    ↓
Semantic Search
    ↓
Relevant Chunks
    ↓
LLM
    ↓
Answer + Source Reference
```

Tujuan:
- Jawaban berbasis jurnal yang diupload.
- Mengurangi jawaban yang tidak berdasarkan sumber.
- Menampilkan sumber/chunk yang digunakan.

---

# 13. Reference Manager

Route:

```text
/references
```

Fitur:
- Add reference
- Edit reference
- Delete reference
- Import metadata
- Search reference
- Citation generator
- Bibliography generator

Format:

```text
APA
IEEE
Harvard
```

Contoh:

```text
[1] Nama Penulis. (2025). Judul Penelitian...
```

---

# 14. AI Reviewer

Route:

```text
/reviewer
/projects/{project}/review
```

AI menilai:

```text
Clarity
Academic Writing
Research Problem
Research Gap
Consistency
Methodology
Citation Quality
Structure
```

Contoh:

```text
AI REVIEW

BAB I

Clarity              87
Academic Writing     91
Research Problem     72
Research Gap         68
Consistency          84

Recommendation:
Rumusan masalah belum sepenuhnya
sesuai dengan tujuan penelitian.
```

Action:

```text
[ Perbaiki dengan AI ]
[ Lihat Penjelasan ]
```

---

# 15. Persiapan Sempro

Route:

```text
/sempro
/projects/{project}/sempro
```

Menu:

```text
- Overview
- Generate Questions
- Simulation
- Question Bank
- Answer Evaluation
- Predicted Questions
- Presentation Material
```

---

# 16. Simulasi Sempro

Flow:

```text
Start Session
    ↓
Generate Question
    ↓
Student Answer
    ↓
AI Evaluation
    ↓
Feedback
    ↓
Next Question
    ↓
Final Score
```

Evaluasi:

```text
Pemahaman Konsep
Relevansi Jawaban
Argumentasi
Metodologi
Kejelasan
Confidence / Presentation Quality
```

Contoh:

```text
Score: 82 / 100

✓ Pemahaman
✓ Relevansi
△ Argumentasi
△ Bukti Pendukung

Feedback:
Jawaban sudah relevan, tetapi alasan
pemilihan metode perlu diperkuat.
```

---

# 17. Progress Tracking

Route:

```text
/progress
```

Progress:

```text
Judul
Proposal
BAB I
BAB II
BAB III
BAB IV
BAB V
Sempro
```

Fitur:
- Progress percentage
- Checklist
- Target mingguan
- Deadline
- Activity history
- Completion status

---

# 18. Subscription

Paket awal:

## Free

```text
Rp 0

- 5 Generate Judul
- 10 AI Chat
- 1 Project
- 2 Review AI
- Basic Sempro
- Export PDF
```

## Student

```text
Rp 79.000 / bulan

- Unlimited Generate Judul & AI Chat
- Chat AI di dalam project
- 5 Project
- 10 Review AI
- Cek Kemiripan (Plagiarism)
- Sempro Simulator
- Export PDF & DOCX
```

## Pro

```text
Rp 199.000 / bulan

- Unlimited Project, AI Chat, PDF Analysis & Reviewer
- Chat AI di dalam project
- Cek Kemiripan (Plagiarism)
- Advanced Research & AI Reviewer
- PDF Analysis
- Export PDF & DOCX
- Template Premium Universitas
- Priority AI
```

Catatan:
- Harga dapat dikonfigurasi dari admin.
- Jangan hard-code harga di frontend.
- Sumber kebenaran harga ada di `database/seeders/PlanSeeder.php`.

---

# 19. Payment

Sediakan abstraction payment provider agar mudah mengganti provider.

Contoh:

```text
PaymentService
├── MidtransProvider
└── XenditProvider
```

Flow:

```text
Choose Plan
    ↓
Checkout
    ↓
Create Payment
    ↓
Payment Gateway
    ↓
Webhook
    ↓
Verify Payment
    ↓
Activate Subscription
```

---

# 20. Usage & AI Cost

Setiap request AI dicatat.

```text
ai_usage
- user_id
- project_id
- provider
- model
- feature
- input_tokens
- output_tokens
- total_tokens
- estimated_cost
- response_time
- status
- created_at
```

Admin dapat melihat:

```text
Total AI Requests
Total Tokens
Estimated AI Cost
Cost per User
Cost per Feature
Cost per Model
```

Ini penting untuk menjaga margin SaaS.

---

# 21. Admin Dashboard

Route:

```text
/admin
```

Dashboard:

```text
Users                 12,840
Active Users           8,421
Projects               5,392
AI Requests          128,421
AI Token Usage        84.2M
Revenue               Rp 42.8 jt
AI Cost                Rp 8.4 jt
```

---

# 22. Admin Menu

```text
Admin
│
├── Dashboard
│
├── Users
│   ├── All Users
│   ├── Students
│   └── Admins
│
├── Thesis
│   ├── Projects
│   ├── Titles
│   └── Documents
│
├── AI Management
│   ├── Usage
│   ├── Models
│   ├── Prompts
│   └── Costs
│
├── Subscription
│   ├── Plans
│   ├── Subscriptions
│   └── Transactions
│
├── Content
│   ├── FAQ
│   ├── Articles
│   └── Tutorials
│
└── Settings
```

---

# 23. Tech Stack

Recommended:

```text
Backend
- Laravel
- PHP 8.3+
- MySQL

Frontend
- Inertia.js
- Vue 3
- Tailwind CSS

Infrastructure
- Redis
- Queue
- Laravel Horizon
- Object Storage

AI
- AI Provider abstraction
- LLM API
- Embedding API
- Vector Database

Payment
- Midtrans / Xendit

Authentication
- Laravel starter/authentication stack
```

Kenapa Inertia + Vue:
- Tetap nyaman menggunakan Laravel.
- UI terasa seperti SPA.
- Cocok untuk editor dan dashboard interaktif.
- Tidak perlu membangun backend API terpisah untuk seluruh halaman.

---

# 24. Struktur Folder Laravel

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── DashboardController.php
│   │   ├── ProjectController.php
│   │   ├── TitleController.php
│   │   ├── DraftController.php
│   │   ├── ResearchController.php
│   │   ├── ReferenceController.php
│   │   ├── ReviewerController.php
│   │   ├── SemproController.php
│   │   └── SubscriptionController.php
│   │
│   └── Middleware/
│
├── Models/
│   ├── User.php
│   ├── Project.php
│   ├── ThesisTitle.php
│   ├── ThesisSection.php
│   ├── Document.php
│   ├── DocumentChunk.php
│   ├── Reference.php
│   ├── Citation.php
│   ├── Conversation.php
│   ├── Message.php
│   ├── SemproSession.php
│   ├── SemproQuestion.php
│   ├── SemproAnswer.php
│   ├── SemproEvaluation.php
│   ├── Plan.php
│   ├── Subscription.php
│   ├── Payment.php
│   └── AiUsage.php
│
├── Services/
│   ├── AI/
│   │   ├── AIService.php
│   │   ├── TitleService.php
│   │   ├── DraftService.php
│   │   ├── ResearchService.php
│   │   ├── ReviewerService.php
│   │   └── SemproService.php
│   │
│   ├── ResearchService.php
│   ├── SubscriptionService.php
│   └── PaymentService.php
│
├── Jobs/
│   ├── AnalyzeDocument.php
│   ├── GenerateDraft.php
│   ├── GenerateEmbedding.php
│   └── AnalyzeResearch.php
│
└── Policies/
```

---

# 25. Struktur Frontend

```text
resources/
├── js/
│   ├── Pages/
│   │   ├── Auth/
│   │   ├── Dashboard.vue
│   │   ├── Projects/
│   │   ├── Titles/
│   │   ├── Draft/
│   │   ├── Research/
│   │   ├── References/
│   │   ├── Reviewer/
│   │   ├── Sempro/
│   │   ├── Subscription/
│   │   └── Admin/
│   │
│   ├── Components/
│   │   ├── Layout/
│   │   ├── UI/
│   │   ├── AI/
│   │   ├── Thesis/
│   │   ├── Research/
│   │   └── Sempro/
│   │
│   └── composables/
│
└── css/
    └── app.css
```

---

# 26. Database Schema

Core tables:

```text
users
plans
subscriptions
payments

projects
thesis_titles
thesis_sections

documents
document_chunks

references
citations

ai_conversations
ai_messages
ai_usage

sempro_sessions
sempro_questions
sempro_answers
sempro_evaluations

notifications
activity_logs
```

Relasi:

```text
User
│
├── subscriptions
├── projects
│     │
│     ├── thesis_titles
│     ├── thesis_sections
│     ├── documents
│     ├── references
│     ├── conversations
│     └── sempro_sessions
│
├── ai_usage
└── activity_logs
```

---

# 27. AI Service Architecture

Jangan menaruh prompt dan logic AI langsung di Controller.

Gunakan abstraction:

```text
AIService
│
├── generateTitle()
├── generateDraft()
├── analyzeResearch()
├── reviewDraft()
├── generateSemproQuestions()
└── evaluateSemproAnswer()
```

Provider:

```text
AIService
    ↓
AI Provider Interface
    ├── Provider A
    ├── Provider B
    └── Provider C
```

Dengan demikian provider/model dapat diganti tanpa mengubah business logic.

---

# 28. Queue & Background Processing

Proses berat jangan dijalankan langsung pada request HTTP.

Gunakan queue untuk:

```text
- PDF extraction
- PDF chunking
- Embedding
- Research analysis
- Long draft generation
- Document processing
- Export document
```

Flow:

```text
User
 ↓
Laravel
 ↓
Create Job
 ↓
Redis Queue
 ↓
Worker
 ↓
AI / Processing
 ↓
Save Result
 ↓
Notify User
```

---

# 29. Security

Wajib:

```text
- Authentication
- Authorization
- Policies
- CSRF protection
- Rate limiting
- Input validation
- File validation
- Private document storage
- Signed download URLs
- AI usage limits
- Subscription authorization
- Audit logs
```

Dokumen mahasiswa harus private secara default.

---

# 30. MVP Roadmap

## Phase 1 — MVP

```text
✓ Authentication
✓ Dashboard
✓ Project Skripsi
✓ Generate Judul
✓ Validasi Judul
✓ AI Chat
✓ Generate BAB I
✓ Generate BAB II
✓ Generate BAB III
✓ Basic Sempro
✓ Subscription
```

## Phase 2

```text
✓ Upload Jurnal
✓ PDF Analysis
✓ Research Gap
✓ Reference Manager
✓ Citation
✓ AI Reviewer
✓ DOCX/PDF Export
```

## Phase 3

```text
✓ RAG
✓ Vector Database
✓ Advanced Sempro
✓ Presentation Generator
✓ Multi-model AI
✓ AI Cost Dashboard
```

## Phase 4 — B2B

```text
✓ University
✓ Faculty
✓ Study Program
✓ Lecturer
✓ Student Management
✓ Institution Billing
✓ Institution Analytics
```

---

# 31. Prioritas Development

Urutan implementasi:

```text
01. Setup Laravel
02. Setup Inertia + Vue
03. Setup Tailwind
04. Authentication
05. Role & Permission
06. Database & Migration
07. Dashboard
08. Project Skripsi
09. Cari Judul
10. AI Service
11. Draft Skripsi
12. Research Assistant
13. Reference Manager
14. AI Reviewer
15. Sempro
16. Subscription
17. Payment
18. Admin Dashboard
19. AI Usage Monitoring
20. RAG
```

---

# 32. Prinsip Produk

Sarjana AI bukan sekadar chatbot.

Produk harus terasa seperti:

```text
"Workspace Skripsi Digital"
```

Bukan:

```text
"Chatbot AI untuk Skripsi"
```

Semua fitur harus terhubung ke:

```text
1 User
   ↓
1 Project Skripsi
   ↓
Judul
Draft
Referensi
Research
Reviewer
Sempro
Progress
```

Dengan konsep tersebut, pengguna mempunyai alasan untuk terus menggunakan aplikasi sampai skripsinya selesai.

---

# 33. Definition of Done MVP

MVP dianggap siap jika:

```text
✓ User dapat register/login
✓ User dapat membuat project
✓ User dapat generate judul
✓ User dapat memvalidasi judul
✓ User dapat menyimpan judul
✓ User dapat membuat draft
✓ User dapat menggunakan AI assistant
✓ User dapat menyimpan perubahan
✓ User dapat upload dokumen
✓ User dapat melakukan basic research analysis
✓ User dapat latihan sempro
✓ Sistem membatasi usage berdasarkan plan
✓ Admin dapat melihat user
✓ Admin dapat melihat AI usage
✓ Admin dapat mengatur plan
✓ Payment webhook berjalan
✓ Dokumen user aman
✓ Error handling tersedia
✓ Queue berjalan untuk proses berat
```

---

# 34. Branding

Nama:

**Sarjana AI**

Positioning:

> **AI Academic Workspace untuk Mahasiswa**

Alternatif tagline:

1. **Dari Ide, Jadi Skripsi, Siap Sempro.**
2. **Teman Cerdas Menyelesaikan Skripsi.**
3. **Riset Lebih Terarah, Skripsi Lebih Siap.**
4. **Satu Workspace untuk Perjalanan Skripsimu.**

Rekomendasi utama:

> **Sarjana AI — Dari Ide, Jadi Skripsi, Siap Sempro.**
