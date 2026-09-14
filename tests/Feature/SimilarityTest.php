<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\PlagiarismReport;
use App\Models\Plan;
use App\Models\Project;
use App\Models\ThesisSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cek kemiripan: copy-paste dari dokumen yang diunggah, dan paragraf
 * yang dipakai ulang di bagian lain dalam draft sendiri.
 */
class SimilarityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        Plan::create([
            'name' => 'Free',
            'slug' => 'free',
            'price' => 0,
            'interval' => 'month',
            'limits' => ['projects' => 1, 'ai_chat' => 50, 'ai_reviewer' => 50],
            'features' => ['Free'],
            'is_active' => true,
            'is_popular' => false,
            'sort' => 1,
        ]);

        $this->user = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);

        $this->project = Project::create([
            'user_id' => $this->user->id,
            'name' => 'Project uji',
            'title' => 'Analisis Sistem Informasi',
            'status' => 'aktif',
        ]);
        $this->project->seedSections();
    }

    private function section(string $key): ThesisSection
    {
        return $this->project->sections()->where('key', $key)->firstOrFail();
    }

    /** Dokumen yang sudah selesai diproses, lengkap dengan satu chunk. */
    private function documentWith(string $text, string $title = 'Jurnal acuan'): Document
    {
        $document = Document::create([
            'user_id' => $this->user->id,
            'title' => $title,
            'disk' => 'local',
            'path' => 'documents/uji.pdf',
            'original_name' => 'uji.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
            'status' => 'ready',
        ]);

        DocumentChunk::create([
            'document_id' => $document->id,
            'position' => 0,
            'content' => $text,
            'tokens' => 200,
        ]);

        return $document;
    }

    public function test_index_renders(): void
    {
        $this->actingAs($this->user)
            ->get(route('similarity.index'))
            ->assertOk()
            ->assertSee('Cek Kemiripan');
    }

    public function test_show_renders_project_sections(): void
    {
        $this->actingAs($this->user)
            ->get(route('similarity.show', $this->project->id))
            ->assertOk()
            ->assertSee($this->project->name);
    }

    public function test_foreign_project_is_forbidden(): void
    {
        $other = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);
        $theirProject = Project::create(['user_id' => $other->id, 'name' => 'Punya orang lain']);

        $this->actingAs($this->user)
            ->get(route('similarity.show', $theirProject->id))
            ->assertForbidden();
    }

    public function test_copy_paste_from_uploaded_document_is_flagged(): void
    {
        $paragraph = 'Sistem informasi akademik di perguruan tinggi memiliki peran penting dalam mengelola data mahasiswa.';

        $this->documentWith($paragraph.' Pengelolaan yang manual menimbulkan banyak kesalahan pencatatan data.');

        $section = $this->section('1.1');
        $section->update(['content' => "<p>{$paragraph} Hal ini membuat proses pelaporan menjadi lambat.</p>"]);

        $this->actingAs($this->user)
            ->post(route('similarity.section', $this->project->id), ['section_id' => $section->id])
            ->assertRedirect();

        $report = PlagiarismReport::where('thesis_section_id', $section->id)->firstOrFail();

        $this->assertGreaterThanOrEqual(50, $report->similarity_score);
        $this->assertNotEmpty($report->matches);
        $this->assertSame('document', $report->matches[0]['source']);
    }

    public function test_reused_paragraph_between_sections_is_flagged(): void
    {
        $paragraph = 'Metode penelitian ini menggunakan pendekatan kuantitatif dengan teknik pengumpulan data melalui kuesioner.';

        $this->section('1.2')->update(['content' => "<p>{$paragraph} Responden dipilih secara acak dari populasi.</p>"]);

        $target = $this->section('3.1');
        $target->update(['content' => "<p>{$paragraph} Analisis data memakai regresi linier berganda.</p>"]);

        $this->actingAs($this->user)
            ->post(route('similarity.section', $this->project->id), ['section_id' => $target->id])
            ->assertRedirect();

        $report = PlagiarismReport::where('thesis_section_id', $target->id)->firstOrFail();

        $this->assertGreaterThanOrEqual(30, $report->similarity_score);
        $this->assertSame('section', $report->matches[0]['source']);
        $this->assertStringContainsString('Identifikasi Masalah', $report->matches[0]['label']);
    }

    public function test_original_writing_is_not_flagged(): void
    {
        $this->documentWith('Sistem informasi akademik di perguruan tinggi memiliki peran penting dalam mengelola data mahasiswa.');

        $section = $this->section('1.1');
        $section->update([
            'content' => '<p>Penelitian ini menelaah pola komunikasi organisasi pada koperasi desa di Kabupaten Sleman.</p>',
        ]);

        $this->actingAs($this->user)
            ->post(route('similarity.section', $this->project->id), ['section_id' => $section->id])
            ->assertRedirect();

        $report = PlagiarismReport::where('thesis_section_id', $section->id)->firstOrFail();

        $this->assertLessThan(20, $report->similarity_score);
        $this->assertSame([], $report->matches);
    }

    /**
     * Penggantian satu kata pada kalimat panjang masih terbaca sebagai
     * kecocokan. Dua kata atau lebih tidak tertangkap — lihat catatan ambang
     * di SimilarityService: batasnya jatuh di bawah frasa baku yang sah.
     */
    public function test_single_word_substitution_is_flagged(): void
    {
        $paragraph = 'Sistem informasi akademik memiliki peran penting dalam mengelola data mahasiswa secara terpusat.';

        $this->section('1.2')->update(['content' => "<p>{$paragraph} Pengelolaan manual menimbulkan banyak kesalahan.</p>"]);

        $target = $this->section('3.1');
        $target->update([
            'content' => '<p>Sistem informasi akademik memiliki peran penting dalam mengelola data mahasiswa secara terpadu.</p>',
        ]);

        $this->actingAs($this->user)
            ->post(route('similarity.section', $this->project->id), ['section_id' => $target->id])
            ->assertRedirect();

        $report = PlagiarismReport::where('thesis_section_id', $target->id)->firstOrFail();

        $this->assertGreaterThanOrEqual(75, $report->similarity_score);
        $this->assertSame('section', $report->matches[0]['source']);
        $this->assertStringContainsString('terpadu', $report->matches[0]['snippet']);
    }

    public function test_two_word_substitution_is_not_flagged(): void
    {
        $paragraph = 'Sistem informasi akademik memiliki peran penting dalam mengelola data mahasiswa secara terpusat.';

        $this->section('1.2')->update(['content' => "<p>{$paragraph} Pengelolaan manual menimbulkan banyak kesalahan.</p>"]);

        $target = $this->section('3.1');
        $target->update([
            'content' => '<p>Sistem informasi akademik memiliki peran krusial dalam mengelola data mahasiswa secara terpadu.</p>',
        ]);

        $this->actingAs($this->user)
            ->post(route('similarity.section', $this->project->id), ['section_id' => $target->id])
            ->assertRedirect();

        $report = PlagiarismReport::where('thesis_section_id', $target->id)->firstOrFail();

        $this->assertLessThan(75, $report->similarity_score);
    }

    public function test_boilerplate_phrasing_is_not_flagged(): void
    {
        $this->documentWith(
            'Metode penelitian ini menggunakan pendekatan kuantitatif dengan teknik pengumpulan data melalui kuesioner tertutup.'
        );

        $section = $this->section('3.1');
        $section->update([
            'content' => '<p>Metode penelitian ini menggunakan pendekatan kualitatif dengan teknik pengumpulan data melalui wawancara mendalam.</p>',
        ]);

        $this->actingAs($this->user)
            ->post(route('similarity.section', $this->project->id), ['section_id' => $section->id])
            ->assertRedirect();

        $report = PlagiarismReport::where('thesis_section_id', $section->id)->firstOrFail();

        $this->assertLessThan(50, $report->similarity_score);
    }

    public function test_empty_section_is_rejected(): void
    {
        $section = $this->section('1.1');

        $this->actingAs($this->user)
            ->post(route('similarity.section', $this->project->id), ['section_id' => $section->id])
            ->assertSessionHasErrors('section_id');

        $this->assertDatabaseCount('plagiarism_reports', 0);
    }

    public function test_section_from_another_project_is_rejected(): void
    {
        $otherProject = Project::create([
            'user_id' => $this->user->id,
            'name' => 'Project lain',
            'status' => 'aktif',
        ]);
        $otherProject->seedSections();
        $foreignSection = $otherProject->sections()->firstOrFail();

        $this->actingAs($this->user)
            ->post(route('similarity.section', $this->project->id), ['section_id' => $foreignSection->id])
            ->assertNotFound();
    }

    public function test_full_project_check_stores_every_section(): void
    {
        $paragraph = 'Sistem informasi akademik di perguruan tinggi memiliki peran penting dalam mengelola data mahasiswa.';

        $this->documentWith($paragraph);
        $this->section('1.1')->update(['content' => "<p>{$paragraph} Laporan menjadi lebih rumit.</p>"]);

        $this->actingAs($this->user)
            ->post(route('similarity.full', $this->project->id))
            ->assertRedirect();

        $report = PlagiarismReport::where('type', 'full_project')->firstOrFail();

        $this->assertGreaterThan(0, $report->similarity_score);
        $this->assertNotEmpty($report->matches);
        $this->assertArrayHasKey('section_id', $report->matches[0]);
    }

    public function test_reports_are_scoped_to_the_owner(): void
    {
        $other = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);

        PlagiarismReport::create([
            'user_id' => $other->id,
            'type' => 'section',
            'similarity_score' => 90,
            'summary' => 'Punya orang lain',
        ]);

        $this->actingAs($this->user)
            ->get(route('similarity.index'))
            ->assertOk()
            ->assertDontSee('Punya orang lain');
    }

    public function test_report_export_returns_pdf(): void
    {
        $this->section('1.2')->update(['content' => '<p>Paragraf asli untuk laporan.</p>']);

        $this->actingAs($this->user)
            ->post(route('similarity.section', $this->project->id), ['section_id' => $this->section('1.2')->id]);

        $response = $this->actingAs($this->user)
            ->get(route('export.similarity', $this->project->id));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename="kemiripan-Analisis_Sistem_Informasi.pdf"');
    }

    public function test_report_export_without_any_report_still_renders(): void
    {
        $this->section('1.2')->update(['content' => '<p>Belum pernah diperiksa.</p>']);

        $this->actingAs($this->user)
            ->get(route('export.similarity', $this->project->id))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_report_export_of_foreign_project_is_forbidden(): void
    {
        $other = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);
        $theirProject = Project::create(['user_id' => $other->id, 'name' => 'Punya orang lain']);

        $this->actingAs($this->user)
            ->get(route('export.similarity', $theirProject->id))
            ->assertForbidden();
    }

    public function test_show_prefers_the_newest_section_report(): void
    {
        $section = $this->section('1.2');
        $section->update(['content' => '<p>Tulisan yang diperiksa dua kali.</p>']);

        PlagiarismReport::create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'thesis_section_id' => $section->id,
            'type' => 'section',
            'similarity_score' => 88,
            'matches' => [['source' => 'document', 'label' => 'Jurnal lama', 'snippet' => 'Lama', 'score' => 88]],
        ]);

        PlagiarismReport::create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'thesis_section_id' => $section->id,
            'type' => 'section',
            'similarity_score' => 12,
            'matches' => [['source' => 'document', 'label' => 'Jurnal baru', 'snippet' => 'Baru', 'score' => 12]],
        ]);

        $this->actingAs($this->user)
            ->get(route('similarity.show', $this->project->id))
            ->assertOk()
            ->assertSee('12% · Aman')
            ->assertSee('Jurnal baru')
            ->assertDontSee('Jurnal lama');
    }
}
