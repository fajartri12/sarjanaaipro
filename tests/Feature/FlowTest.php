<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Latihan alur inti: dari bikin project sampai sempro, pakai AI provider "null"
 * supaya tidak butuh API key.
 */
class FlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_full_student_journey(): void
    {
        // 1. Bikin project
        $this->actingAs($this->user)
            ->post('/projects', ['name' => 'Project Sistem Informasi'])
            ->assertRedirect();

        $project = Project::firstOrFail();
        $this->assertSame(20, $project->sections()->count());

        // 2. Generate judul
        $this->actingAs($this->user)
            ->post('/titles/generate', ['topic' => 'sistem rekomendasi'])
            ->assertRedirect();

        $this->assertGreaterThanOrEqual(3, $this->user->titles()->count());

        $title = $this->user->titles()->firstOrFail();

        // 3. Analisis judul
        $this->actingAs($this->user)
            ->post("/titles/{$title->id}/analyze")
            ->assertRedirect();

        $this->assertNotNull($title->fresh()->recommendation);

        // 4. Pilih judul ke project
        $this->actingAs($this->user)
            ->post("/titles/{$title->id}/select", ['project_id' => $project->id])
            ->assertRedirect();

        $this->assertSame($title->title, $project->fresh()->title);

        // 5. Isi + generate draft BAB I
        $section = $project->sections()->where('chapter', 'BAB I')->firstOrFail();

        $this->actingAs($this->user)
            ->patch("/projects/{$project->id}/draft/{$section->id}", [
                'content' => str_repeat('latar belakang penelitian ', 60),
            ])
            ->assertRedirect();

        $this->actingAs($this->user)
            ->post("/projects/{$project->id}/draft/{$section->id}/generate")
            ->assertRedirect();

        $this->assertNotEmpty($section->fresh()->content);

        // 6. Aksi lanjutan pada draft
        $this->actingAs($this->user)
            ->post("/projects/{$project->id}/draft/{$section->id}/action", ['action' => 'improve'])
            ->assertRedirect();

        // 7. Simpan referensi
        $this->actingAs($this->user)
            ->post('/references', [
                'project_id' => $project->id,
                'type' => 'journal',
                'title' => 'Sistem Rekomendasi Berbasis AI',
                'authors' => 'Budi Santoso',
                'year' => 2024,
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('references', 1);

        // 8. Bikin daftar pustaka
        $this->actingAs($this->user)
            ->post('/references/bibliography', ['project_id' => $project->id, 'style' => 'apa'])
            ->assertRedirect();

        // 9. Reviewer
        $this->actingAs($this->user)
            ->post("/reviewer/{$project->id}")
            ->assertRedirect();

        $this->assertDatabaseCount('reviews', 1);

        // 10. Tanya AI soal riset
        $this->actingAs($this->user)
            ->post('/research/ask', ['question' => 'apa itu metode kuantitatif?', 'project_id' => $project->id])
            ->assertRedirect();

        // 11. Cari research gap
        $this->actingAs($this->user)
            ->post('/research/gap', ['topic' => 'sistem rekomendasi', 'project_id' => $project->id])
            ->assertRedirect();

        // 12. Sesi sempro: mulai, jawab, selesai
        $this->actingAs($this->user)
            ->post('/sempro', ['project_id' => $project->id, 'question_count' => 3])
            ->assertRedirect();

        $session = \App\Models\SemproSession::where('user_id', $this->user->id)->firstOrFail();
        $this->assertSame(3, $session->questions()->count());

        $question = $session->questions()->firstOrFail();

        $this->actingAs($this->user)
            ->post("/sempro/{$session->id}/answer", [
                'question_id' => $question->id,
                'answer' => 'Metode kuantitatif adalah pendekatan penelitian yang memakai angka dan statistik untuk menguji hipotesis.',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('sempro_evaluations', 1);

        $this->actingAs($this->user)
            ->post("/sempro/{$session->id}/finish")
            ->assertRedirect();

        $this->assertSame('finished', $session->fresh()->status);

        $this->actingAs($this->user)
            ->get("/sempro/{$session->id}/result")
            ->assertOk();

        // 13. Tiap pemanggilan AI tercatat di ai_usage
        $this->assertGreaterThan(0, \App\Models\AiUsage::count());
    }

    public function test_student_cannot_touch_other_users_project(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_STUDENT, 'email_verified_at' => now()]);
        $project = Project::create(['user_id' => $owner->id, 'name' => 'Punya orang lain']);

        $this->actingAs($this->user)->get("/projects/{$project->id}")->assertForbidden();
    }
}
