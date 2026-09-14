<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\UniversityTemplate;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 4: invoice, kuota, dan template kampus.
 */
class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Plan $proPlan;

    protected function setUp(): void
    {
        parent::setUp();

        Plan::create([
            'name' => 'Free',
            'slug' => 'free',
            'price' => 0,
            'interval' => 'month',
            'limits' => ['projects' => 1, 'ai_chat' => 50, 'export_docx' => 0],
            'features' => ['Free'],
            'is_active' => true,
            'sort' => 1,
        ]);

        $this->proPlan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 99000,
            'interval' => 'month',
            'limits' => ['projects' => 10, 'ai_chat' => 500, 'export_docx' => -1],
            'features' => ['Pro'],
            'is_active' => true,
            'is_popular' => true,
            'sort' => 2,
        ]);

        $this->user = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);

        UniversityTemplate::seedDefaults();
    }

    /** Payment pending lengkap dengan subscription-nya, siap dikonfirmasi. */
    private function pendingPayment(string $reference = 'SA-TEST'): Payment
    {
        $subscription = Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->proPlan->id,
            'status' => 'pending',
        ]);

        return Payment::create([
            'reference' => $reference,
            'user_id' => $this->user->id,
            'plan_id' => $this->proPlan->id,
            'subscription_id' => $subscription->id,
            'provider' => 'manual',
            'amount' => 99000,
            'status' => 'pending',
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'email_verified_at' => now()]);
    }

    public function test_invoice_number_is_assigned_when_payment_is_confirmed(): void
    {
        $payment = $this->pendingPayment();

        $this->assertNull($payment->invoice_number);

        app(SubscriptionService::class)->markAsPaid($payment);

        $invoice = $payment->fresh()->invoice_number;

        $this->assertNotNull($invoice);
        $this->assertStringStartsWith('INV/' . now()->format('Y') . '/', $invoice);
    }

    public function test_invoice_number_increments_across_payments(): void
    {
        $first = $this->pendingPayment('SA-01');
        $second = $this->pendingPayment('SA-02');

        $service = app(SubscriptionService::class);
        $service->markAsPaid($first);
        $service->markAsPaid($second);

        $a = (int) substr($first->fresh()->invoice_number, -4);
        $b = (int) substr($second->fresh()->invoice_number, -4);

        $this->assertSame(1, $b - $a);
    }

    public function test_confirming_twice_keeps_the_original_invoice_number(): void
    {
        $payment = $this->pendingPayment();
        $service = app(SubscriptionService::class);

        $service->markAsPaid($payment);
        $original = $payment->fresh()->invoice_number;

        $service->markAsPaid($payment->fresh());

        $this->assertSame($original, $payment->fresh()->invoice_number);
    }

    public function test_user_can_list_invoices(): void
    {
        $payment = $this->pendingPayment();
        app(SubscriptionService::class)->markAsPaid($payment);

        $this->actingAs($this->user)
            ->get(route('subscription.invoices'))
            ->assertOk()
            ->assertSee($payment->fresh()->invoice_number);
    }

    public function test_user_can_download_own_invoice(): void
    {
        $payment = $this->pendingPayment();
        app(SubscriptionService::class)->markAsPaid($payment);

        $response = $this->actingAs($this->user)->get(route('invoices.download', $payment));

        $response->assertOk();
        $this->assertStringContainsString(
            str_replace('/', '-', $payment->fresh()->invoice_number) . '.pdf',
            $response->headers->get('content-disposition')
        );
    }

    public function test_invoice_download_is_404_before_confirmation(): void
    {
        $payment = $this->pendingPayment();

        $this->actingAs($this->user)
            ->get(route('invoices.download', $payment))
            ->assertNotFound();
    }

    public function test_user_cannot_download_someone_elses_invoice(): void
    {
        $payment = $this->pendingPayment();
        app(SubscriptionService::class)->markAsPaid($payment);

        $other = User::factory()->create(['role' => User::ROLE_STUDENT, 'email_verified_at' => now()]);

        $this->actingAs($other)
            ->get(route('invoices.download', $payment))
            ->assertForbidden();
    }

    public function test_quota_page_renders(): void
    {
        $this->actingAs($this->user)
            ->get(route('quota'))
            ->assertOk()
            ->assertSee('Kuota');
    }

    public function test_dashboard_links_to_quota_detail(): void
    {
        $this->actingAs($this->user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(route('quota'));
    }

    public function test_guide_page_renders_active_plans(): void
    {
        Plan::create([
            'name' => 'Paket Panduan',
            'slug' => 'paket-panduan',
            'price' => 42000,
            'interval' => 'month',
            'limits' => [],
            'features' => ['Fitur panduan'],
            'is_active' => true,
            'sort' => 3,
        ]);

        $this->actingAs($this->user)
            ->get(route('guide.index'))
            ->assertOk()
            ->assertSee('Buku panduan')
            ->assertSee('Paket Panduan')
            ->assertSee('42.000');
    }

    public function test_guide_page_hides_inactive_plans(): void
    {
        Plan::create([
            'name' => 'Paket Nonaktif',
            'slug' => 'paket-nonaktif',
            'price' => 10000,
            'interval' => 'month',
            'limits' => [],
            'features' => [],
            'is_active' => false,
            'sort' => 4,
        ]);

        $this->actingAs($this->user)
            ->get(route('guide.index'))
            ->assertOk()
            ->assertDontSee('Paket Nonaktif');
    }

    public function test_guide_is_not_public(): void
    {
        $this->get(route('guide.index'))->assertRedirect(route('login'));
    }

    public function test_university_templates_are_seeded(): void
    {
        UniversityTemplate::seedDefaults();

        foreach (['umum', 'ui', 'ugm', 'itb', 'apa-premium'] as $slug) {
            $this->assertNotNull(
                UniversityTemplate::where('slug', $slug)->first(),
                "Template {$slug} tidak ditemukan."
            );
        }
    }

    public function test_seeding_templates_twice_does_not_duplicate(): void
    {
        UniversityTemplate::seedDefaults();
        $before = UniversityTemplate::count();

        UniversityTemplate::seedDefaults();

        $this->assertSame($before, UniversityTemplate::count());
    }

    public function test_template_config_falls_back_to_defaults(): void
    {
        $template = UniversityTemplate::create([
            'name' => 'Kampus Uji',
            'slug' => 'kampus-uji',
            'price' => 0,
            'is_active' => true,
            'config' => ['line_height' => 2.0],
        ]);

        $config = $template->resolvedConfig();

        $this->assertSame(2.0, (float) $config['line_height']);
        $this->assertSame(config('template.defaults.font_family'), $config['font_family']);
        $this->assertSame(config('template.defaults.font_size'), $config['font_size']);
    }

    public function test_template_renders_css(): void
    {
        UniversityTemplate::seedDefaults();
        $css = UniversityTemplate::where('slug', 'umum')->firstOrFail()->toCss();

        $this->assertStringContainsString('@page', $css);
        $this->assertStringContainsString('font-family', $css);
    }

    public function test_project_inherits_its_template_format(): void
    {
        UniversityTemplate::seedDefaults();
        $template = UniversityTemplate::where('slug', 'ui')->firstOrFail();

        $project = Project::create([
            'user_id' => $this->user->id,
            'name' => 'Skripsi dengan template',
            'title' => 'Analisis Sistem',
            'status' => 'aktif',
            'template_id' => $template->id,
        ]);

        $this->assertSame($template->id, $project->fresh()->template_id);
        $this->assertSame(
            (float) $template->resolvedConfig()['line_height'],
            (float) $project->fresh()->format()['line_height']
        );
    }

    public function test_project_without_template_uses_default_format(): void
    {
        $project = Project::create([
            'user_id' => $this->user->id,
            'name' => 'Tanpa template',
            'title' => 'Analisis Sistem',
            'status' => 'aktif',
        ]);

        $this->assertSame(config('template.defaults'), $project->format());
    }

    public function test_deleting_template_keeps_the_project(): void
    {
        UniversityTemplate::seedDefaults();
        $template = UniversityTemplate::where('slug', 'umum')->firstOrFail();

        $project = Project::create([
            'user_id' => $this->user->id,
            'name' => 'Project menyusul',
            'title' => 'Analisis Sistem',
            'status' => 'aktif',
            'template_id' => $template->id,
        ]);

        $template->delete();

        $this->assertNotNull($project->fresh());
        $this->assertNull($project->fresh()->template_id);
    }

    public function test_admin_can_see_ai_cost_page(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.ai'))
            ->assertOk()
            ->assertSee('Biaya');
    }

    public function test_admin_can_expire_a_pending_payment(): void
    {
        $payment = $this->pendingPayment();

        $this->actingAs($this->admin())
            ->post(route('admin.payments.expire', $payment))
            ->assertRedirect();

        $this->assertSame('expired', $payment->fresh()->status);
        $this->assertSame('expired', $payment->subscription->fresh()->status);
    }

    public function test_paid_payment_cannot_be_expired(): void
    {
        $payment = $this->pendingPayment();
        app(SubscriptionService::class)->markAsPaid($payment);

        $this->actingAs($this->admin())
            ->post(route('admin.payments.expire', $payment))
            ->assertStatus(422);

        $this->assertSame('paid', $payment->fresh()->status);
    }

    public function test_premium_template_hidden_without_subscription(): void
    {
        $this->actingAs($this->user)
            ->get(route('projects.create'))
            ->assertOk()
            ->assertSee('Format Umum')
            ->assertDontSee('APA 7th');
    }

    public function test_premium_template_shown_with_subscription(): void
    {
        Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->proPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($this->user)
            ->get(route('projects.create'))
            ->assertOk()
            ->assertSee('APA 7th');
    }

    public function test_template_picker_shows_in_edit_form(): void
    {
        $project = Project::create([
            'user_id' => $this->user->id,
            'name' => 'Uji',
            'title' => 'Judul',
            'status' => 'aktif',
        ]);

        $this->actingAs($this->user)
            ->get(route('projects.edit', $project))
            ->assertOk()
            ->assertSee('Format kampus');
    }

    public function test_free_plan_cannot_export_docx(): void
    {
        $project = Project::create([
            'user_id' => $this->user->id,
            'name' => 'Uji DOCX',
            'title' => 'Judul DOCX',
            'status' => 'aktif',
        ]);
        $project->seedSections();

        $section = $project->sections()->first();
        $section->content = '<p>Isi untuk uji DOCX.</p>';
        $section->save();

        $this->actingAs($this->user)
            ->get(route('export.docx', $project))
            ->assertStatus(403)
            ->assertSee('Export DOCX tersedia untuk paket Student ke atas');
    }

    public function test_paid_plan_can_export_docx(): void
    {
        Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->proPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $project = Project::create([
            'user_id' => $this->user->id,
            'name' => 'Uji DOCX',
            'title' => 'Judul DOCX',
            'status' => 'aktif',
        ]);
        $project->seedSections();

        $section = $project->sections()->first();
        $section->content = '<p>Isi untuk uji DOCX.</p>';
        $section->save();

        $this->actingAs($this->user)
            ->get(route('export.docx', $project))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }
}
