<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Project;
use App\Models\Reference;
use App\Models\ThesisSection;
use App\Models\User;
use App\Support\SafeHtml;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 1: sanitasi naskah, ekspor PDF/DOCX, dan validasi sitasi.
 */
class ExportAndSafetyTest extends TestCase
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

    public function test_save_strips_script_and_event_attributes(): void
    {
        $section = $this->project->sections()->firstOrFail();

        $this->actingAs($this->user)
            ->patch(route('draft.save', [$this->project->id, $section->id]), [
                'content' => '<p onclick="alert(1)">Aman</p><script>alert(2)</script><iframe src="x"></iframe>',
            ])
            ->assertRedirect();

        $saved = $section->fresh()->content;

        $this->assertStringNotContainsString('<script', $saved);
        $this->assertStringNotContainsString('<iframe', $saved);
        $this->assertStringNotContainsString('onclick', $saved);
        $this->assertStringContainsString('Aman', $saved);
    }

    public function test_safe_html_keeps_allowed_formatting(): void
    {
        $clean = SafeHtml::clean('<p><strong>tebal</strong> dan <em>miring</em></p><ul><li>satu</li></ul>');

        $this->assertStringContainsString('<strong>tebal</strong>', $clean);
        $this->assertStringContainsString('<em>miring</em>', $clean);
        $this->assertStringContainsString('<li>satu</li>', $clean);
    }

    public function test_pdf_export_returns_pdf(): void
    {
        $section = $this->project->sections()->firstOrFail();
        $section->content = '<p>Isi bab pendahuluan untuk uji ekspor.</p>';
        $section->save();

        $response = $this->actingAs($this->user)
            ->get(route('export.pdf', $this->project->id));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_docx_export_returns_docx(): void
    {
        $section = $this->project->sections()->firstOrFail();
        $section->content = '<p>Isi bab pendahuluan untuk uji ekspor.</p>';
        $section->save();

        $response = $this->actingAs($this->user)
            ->get(route('export.docx', $this->project->id));

        $response->assertOk();
        $this->assertStringContainsString(
            'wordprocessingml.document',
            $response->headers->get('Content-Type') ?? ''
        );

        // Header saja tidak cukup: paket OOXML harus benar-benar bisa dibuka Word.
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()) === true);

        $document = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertNotFalse($document);
        $this->assertStringContainsString('Isi bab pendahuluan untuk uji ekspor.', $document);
    }

    public function test_citation_rejects_other_users_reference(): void
    {
        $other = User::factory()->create(['email_verified_at' => now()]);
        $foreign = Reference::create([
            'user_id' => $other->id,
            'type' => 'journal',
            'title' => 'Milik orang lain',
        ]);

        $section = $this->project->sections()->firstOrFail();

        $this->actingAs($this->user)
            ->postJson(route('draft.citations.store', [$this->project->id, $section->id]), [
                'reference_id' => $foreign->id,
                'style' => 'apa',
            ])
            ->assertNotFound();
    }

    public function test_citation_rejects_duplicate_in_same_section(): void
    {
        $reference = Reference::create([
            'user_id' => $this->user->id,
            'type' => 'journal',
            'authors' => 'Suryana, A.',
            'year' => 2024,
            'title' => 'Sistem Informasi Akademik',
        ]);

        $section = $this->project->sections()->firstOrFail();

        $this->actingAs($this->user)
            ->postJson(route('draft.citations.store', [$this->project->id, $section->id]), [
                'reference_id' => $reference->id,
                'style' => 'apa',
            ])
            ->assertOk();

        $this->actingAs($this->user)
            ->postJson(route('draft.citations.store', [$this->project->id, $section->id]), [
                'reference_id' => $reference->id,
                'style' => 'apa',
            ])
            ->assertStatus(422);

        $this->assertSame(1, $section->citations()->count());
    }

    public function test_paragraph_review_returns_json(): void
    {
        $section = $this->project->sections()->firstOrFail();

        $response = $this->actingAs($this->user)
            ->postJson(route('draft.review-paragraph', [$this->project->id, $section->id]), [
                'paragraph' => 'Ini adalah paragraf yang cukup panjang untuk diuji karena memiliki lebih dari sepuluh karakter.',
            ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertArrayHasKey('score', $data);
        $this->assertArrayHasKey('issue', $data);
        $this->assertArrayHasKey('suggestion', $data);
        $this->assertArrayHasKey('reason', $data);
        $this->assertIsInt($data['score']);
        $this->assertGreaterThanOrEqual(0, $data['score']);
        $this->assertLessThanOrEqual(100, $data['score']);
    }

    public function test_paragraph_review_requires_minimum_length(): void
    {
        $section = $this->project->sections()->firstOrFail();

        $response = $this->actingAs($this->user)
            ->postJson(route('draft.review-paragraph', [$this->project->id, $section->id]), [
                'paragraph' => 'Pendek',
            ]);

        $response->assertStatus(422);
    }

    public function test_sources_returns_relevant_documents(): void
    {
        $section = $this->project->sections()->firstOrFail();

        // Tanpa dokumen, harusnya kosong.
        $response = $this->actingAs($this->user)
            ->postJson(route('draft.sources', [$this->project->id, $section->id]), [
                'query' => 'sistem informasi manajemen data',
            ]);

        $response->assertOk();
        $this->assertArrayHasKey('sources', $response->json());
        $this->assertEmpty($response->json('sources'));
    }

    public function test_sources_requires_minimum_length(): void
    {
        $section = $this->project->sections()->firstOrFail();

        $response = $this->actingAs($this->user)
            ->postJson(route('draft.sources', [$this->project->id, $section->id]), [
                'query' => 'pendek',
            ]);

        $response->assertStatus(422);
    }

    public function test_methodology_returns_answer(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('research.methodology'), [
                'question' => 'Apa metode yang tepat untuk meneliti pengaruh AI terhadap kinerja UMKM?',
                'project_id' => $this->project->id,
            ]);

        $response->assertOk();
        $this->assertArrayHasKey('answer', $response->json());
        $this->assertNotEmpty($response->json('answer'));
    }

    public function test_methodology_requires_minimum_length(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('research.methodology'), [
                'question' => 'pendek',
            ]);

        $response->assertStatus(422);
    }

    public function test_consistency_check_returns_json(): void
    {
        // Konsistensi butuh minimal satu bagian berisi dan judul terpilih.
        $this->project->update(['title' => 'Pengaruh AI terhadap Kinerja UMKM']);
        $this->project->sections()->firstOrFail()->update([
            'content' => '<p>Rumusan masalah penelitian ini adalah sejauh mana adopsi AI memengaruhi kinerja UMKM.</p>',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('reviewer.consistency', $this->project->id));

        $response->assertOk();
        $data = $response->json();

        $this->assertArrayHasKey('consistent', $data);
        $this->assertArrayHasKey('issues', $data);
        $this->assertArrayHasKey('suggestions', $data);
        $this->assertArrayHasKey('summary', $data);
    }

    public function test_consistency_requires_draft_content(): void
    {
        // Tanpa isi draft, pemeriksaan harus menolak dengan 422.
        $response = $this->actingAs($this->user)
            ->postJson(route('reviewer.consistency', $this->project->id));

        $response->assertStatus(422);
    }
}
