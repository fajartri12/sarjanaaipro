<?php

namespace Tests\Feature;

use App\Models\AiConversation;
use App\Models\Plan;
use App\Models\Project;
use App\Models\ThesisSectionVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 3 — Retensi pengguna: autosave + riwayat versi, deadline + notifikasi,
 * dan chat AI berbasis project.
 */
class RetentionTest extends TestCase
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

        $this->project = Project::create([
            'user_id' => $this->user->id,
            'name' => 'Project Retensi',
            'status' => 'aktif',
        ]);

        $this->project->seedSections();
    }

    public function test_autosave_creates_version_history(): void
    {
        $section = $this->project->sections()->firstOrFail();

        // Isi awal: autosave pertama menyimpan ini sebagai versi, bukan isi kosong.
        $section->update(['content' => 'Isi awal sebelum ada autosave.']);

        $this->actingAs($this->user)
            ->postJson("/projects/{$this->project->id}/draft/{$section->id}/autosave", [
                'content' => 'Isi pertama yang disimpan otomatis.',
            ])
            ->assertOk()
            ->assertJsonStructure(['saved_at', 'word_count', 'status']);

        $this->actingAs($this->user)
            ->postJson("/projects/{$this->project->id}/draft/{$section->id}/autosave", [
                'content' => 'Isi kedua setelah revisi.',
            ])
            ->assertOk();

        $this->assertSame(2, ThesisSectionVersion::where('section_id', $section->id)->count());

        $this->actingAs($this->user)
            ->getJson("/projects/{$this->project->id}/draft/{$section->id}/versions")
            ->assertOk()
            ->assertJsonCount(2, 'versions');
    }

    public function test_restore_returns_previous_version(): void
    {
        $section = $this->project->sections()->firstOrFail();

        $this->actingAs($this->user)
            ->postJson("/projects/{$this->project->id}/draft/{$section->id}/autosave", [
                'content' => 'Versi asli.',
            ]);

        $this->actingAs($this->user)
            ->postJson("/projects/{$this->project->id}/draft/{$section->id}/autosave", [
                'content' => 'Versi baru yang salah.',
            ]);

        $original = ThesisSectionVersion::where('section_id', $section->id)
            ->where('content', 'Versi asli.')
            ->firstOrFail();

        $this->actingAs($this->user)
            ->post("/projects/{$this->project->id}/draft/{$section->id}/versions/{$original->id}/restore")
            ->assertRedirect();

        $this->assertSame('Versi asli.', $section->fresh()->content);
    }

    public function test_project_chat_persists_messages(): void
    {
        $conversation = AiConversation::create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'title' => 'Diskusi metode',
            'feature' => 'chat',
        ]);

        $this->actingAs($this->user)
            ->postJson("/projects/{$this->project->id}/chat/{$conversation->id}/send", [
                'message' => 'Bagaimana cara memilih metode penelitian?',
            ])
            ->assertOk()
            ->assertJsonCount(2, 'messages');

        $this->assertSame(2, $conversation->messages()->count());
        $this->assertSame('user', $conversation->messages()->first()->role);
        $this->assertSame('assistant', $conversation->messages()->reorder()->latest('id')->first()->role);
    }

    public function test_project_chat_page_renders(): void
    {
        $this->actingAs($this->user)
            ->get("/projects/{$this->project->id}/chat")
            ->assertOk()
            ->assertSee('Chat AI');
    }

    public function test_deadline_reminder_creates_notification(): void
    {
        $this->project->update([
            'deadline' => now()->addDays(3)->toDateString(),
        ]);

        $this->artisan('notifications:deadlines')->assertSuccessful();

        $this->assertSame(1, $this->user->appNotifications()->count());
        $this->assertNotNull($this->user->appNotifications()->first()->read_at === null);

        $this->actingAs($this->user)
            ->getJson('/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('unread', 1);
    }
}
