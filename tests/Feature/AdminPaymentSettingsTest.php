<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rekening tujuan transfer manual diatur admin lewat panel, bukan lewat kode.
 * Perubahan itu harus langsung terbaca pengguna dan tercatat di pembayaran.
 */
class AdminPaymentSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $student;

    private Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 99000,
            'interval' => 'month',
            'limits' => ['projects' => 10],
            'features' => ['Pro'],
            'is_active' => true,
            'sort' => 1,
        ]);

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);

        $subscription = Subscription::create([
            'user_id' => $this->student->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
        ]);

        $this->payment = Payment::create([
            'reference' => 'SA-ADMIN01',
            'user_id' => $this->student->id,
            'plan_id' => $plan->id,
            'subscription_id' => $subscription->id,
            'provider' => 'manual',
            'amount' => 99000,
            'status' => 'pending',
        ]);
    }

    private function channel(string $name, string $number, string $holder = ''): array
    {
        return ['name' => $name, 'type' => 'bank', 'number' => $number, 'holder' => $holder];
    }

    public function test_admin_can_open_the_rekening_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('Rekening tujuan');
    }

    public function test_student_cannot_open_the_rekening_page(): void
    {
        $this->actingAs($this->student)
            ->get(route('admin.settings'))
            ->assertForbidden();
    }

    public function test_admin_can_replace_the_channel_list(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.settings.update'), [
                'contact' => 'https://wa.me/628111111111',
                'channels' => [
                    $this->channel('Bank BCA', '1234567890', 'Yayasan Sarjana AI'),
                    $this->channel('Dana', '081298765432', ''),
                ],
            ])
            ->assertRedirect();

        $this->assertSame('https://wa.me/628111111111', Setting::get('payment_contact'));

        $this->actingAs($this->student)
            ->get(route('subscription.my'))
            ->assertSee('1234567890')
            ->assertSee('081298765432')
            ->assertSee('https://wa.me/628111111111', false);
    }

    public function test_channel_without_a_number_is_hidden_from_users(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.settings.update'), [
                'channels' => [
                    $this->channel('Bank Mandiri', '1370001234567'),
                    $this->channel('Bank Jatim', ''),
                ],
            ])
            ->assertRedirect();

        $this->actingAs($this->student)
            ->get(route('subscription.my'))
            ->assertSee('1370001234567')
            ->assertDontSee('Bank Jatim');
    }

    public function test_channel_name_is_required(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.settings.update'), [
                'channels' => [
                    ['name' => '', 'type' => 'bank', 'number' => '123', 'holder' => ''],
                ],
            ])
            ->assertSessionHasErrors('channels.0.name');
    }

    public function test_confirming_payment_records_the_chosen_channel(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.payments.confirm', $this->payment->id), ['channel' => 'Bank Mandiri'])
            ->assertRedirect();

        $payment = $this->payment->fresh();

        $this->assertSame('paid', $payment->status);
        $this->assertSame('Bank Mandiri', $payment->channel);
    }

    public function test_confirming_without_a_channel_still_works(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.payments.confirm', $this->payment->id))
            ->assertRedirect();

        $payment = $this->payment->fresh();

        $this->assertSame('paid', $payment->status);
        $this->assertNull($payment->channel);
    }

    public function test_payments_list_shows_the_recorded_channel(): void
    {
        $this->payment->update(['channel' => 'GoPay']);

        $this->actingAs($this->admin)
            ->get(route('admin.payments'))
            ->assertOk()
            ->assertSee('Sumber')
            ->assertSee('GoPay');
    }
}
