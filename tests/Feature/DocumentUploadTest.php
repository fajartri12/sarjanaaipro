<?php

namespace Tests\Feature;

use App\Jobs\ProcessDocument;
use App\Models\AiUsage;
use App\Models\Document;
use App\Models\Plan;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Upload dokumen penelitian: validasi file, penyimpanan private,
 * kepemilikan project, dan kuota plan.
 */
class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Queue::fake();

        Plan::create([
            'name' => 'Free',
            'slug' => 'free',
            'price' => 0,
            'interval' => 'month',
            'limits' => ['projects' => 1, 'generate_titles' => 50, 'ai_chat' => 50, 'pdf_analysis' => 2, 'ai_reviewer' => 5],
            'features' => ['Free'],
            'is_active' => true,
            'is_popular' => false,
            'sort' => 1,
        ]);

        $this->user = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);
    }

    private function pdf(string $name = 'jurnal.pdf', int $kilobytes = 10): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kilobytes, 'application/pdf');
    }

    public function test_guest_cannot_upload(): void
    {
        $this->post('/research/upload', ['file' => $this->pdf()])
            ->assertRedirect('/login');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_user_can_upload_pdf(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/research/upload', [
                'file' => $this->pdf('skripsi-bab1.pdf'),
                'title' => 'Analisis Sistem Informasi',
                'author' => 'Budi Santoso',
                'year' => 2024,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHasNoErrors();

        $document = Document::firstOrFail();
        $this->assertSame($this->user->id, $document->user_id);
        $this->assertSame('skripsi-bab1.pdf', $document->original_name);
        $this->assertSame('Analisis Sistem Informasi', $document->title);
        $this->assertSame('Budi Santoso', $document->author);
        $this->assertSame(2024, $document->year);
        $this->assertSame('pending', $document->status);
        $this->assertSame('local', $document->disk);

        // File fisik tersimpan di disk private, bukan disk public.
        Storage::disk('local')->assertExists($document->path);
        Storage::disk('local')->assertMissing('public/'.$document->path);
    }

    public function test_stored_path_is_scoped_to_the_uploading_user(): void
    {
        $this->actingAs($this->user)
            ->post('/research/upload', ['file' => $this->pdf()])
            ->assertSessionHasNoErrors();

        $document = Document::firstOrFail();

        $this->assertStringStartsWith("documents/{$this->user->id}/", $document->path);
    }

    public function test_process_document_job_is_dispatched(): void
    {
        $this->actingAs($this->user)
            ->post('/research/upload', ['file' => $this->pdf()]);

        Queue::assertPushed(ProcessDocument::class, function ($job) {
            return $job->document->is(Document::firstOrFail());
        });
    }

    public function test_non_pdf_file_is_rejected(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/research/upload', [
                'file' => UploadedFile::fake()->create('malware.exe', 10, 'application/x-msdownload'),
            ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('documents', 0);
        Queue::assertNothingPushed();
    }

    public function test_file_larger_than_20mb_is_rejected(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/research/upload', [
                // 20481 KB = 1 KB di atas batas 20 MB.
                'file' => $this->pdf('besar.pdf', 20481),
            ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_upload_without_file_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->post('/research/upload', ['title' => 'Tanpa file'])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_user_cannot_attach_document_to_someone_elses_project(): void
    {
        $other = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);

        $foreignProject = Project::create([
            'user_id' => $other->id,
            'name' => 'Project Orang Lain',
            'status' => 'aktif',
        ]);

        $this->actingAs($this->user)
            ->post('/research/upload', [
                'file' => $this->pdf(),
                'project_id' => $foreignProject->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_upload_with_own_project_succeeds(): void
    {
        $project = Project::create([
            'user_id' => $this->user->id,
            'name' => 'Project Saya',
            'status' => 'aktif',
        ]);

        $this->actingAs($this->user)
            ->post('/research/upload', [
                'file' => $this->pdf(),
                'project_id' => $project->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($project->id, Document::firstOrFail()->project_id);
    }

    public function test_quota_blocks_upload_after_limit_reached(): void
    {
        // Plan Free: pdf_analysis = 2. Middleware hanya menghitung kuota yang
        // sudah dibukukan di ai_usage; baris pending maupun success ikut
        // dihitung. Kita simulasikan dua penggunaan yang sudah selesai.
        foreach (range(1, 2) as $i) {
            AiUsage::create([
                'user_id' => $this->user->id,
                'feature' => 'pdf_analysis',
                'provider' => 'null',
                'model' => 'null',
                'status' => 'success',
                'input_tokens' => 1000,
                'output_tokens' => 100,
                'total_tokens' => 1100,
            ]);
        }

        $this->actingAs($this->user)
            ->post('/research/upload', ['file' => $this->pdf('jurnal-3.pdf')])
            ->assertSessionHasErrors('quota');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_user_cannot_download_someone_elses_document(): void
    {
        $other = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);

        $document = Document::create([
            'user_id' => $other->id,
            'disk' => 'local',
            'path' => 'documents/'.$other->id.'/rahasia.pdf',
            'original_name' => 'rahasia.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
            'status' => 'ready',
        ]);

        Storage::disk('local')->put($document->path, 'isi rahasia');

        $this->actingAs($this->user)
            ->get("/research/{$document->id}/download")
            ->assertForbidden();
    }

    public function test_owner_can_download_own_document(): void
    {
        $this->actingAs($this->user)->post('/research/upload', ['file' => $this->pdf('milik-saya.pdf')]);
        $document = Document::firstOrFail();

        $this->actingAs($this->user)
            ->get("/research/{$document->id}/download")
            ->assertOk()
            ->assertDownload('milik-saya.pdf');
    }
}
