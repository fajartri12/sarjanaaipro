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
 * Instruksi transfer manual: kanal yang nomornya kosong tidak boleh tampil,
 * dan halaman langganan harus bisa dibuka ulang sebelum admin konfirmasi.
 */
class ManualPaymentInstructionsTest extends TestCase
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
            'limits' => ['projects' => 1],
            'features' => ['Free'],
            'is_active' => true,
            'sort' => 1,
        ]);

        $this->proPlan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 99000,
            'interval' => 'month',
            'limits' => ['projects' => 10],
            'features' => ['Pro'],
            'is_active' => true,
            'sort' => 2,
        ]);

        $this->user = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);
    }

    /** Isi kanal manual, biarkan yang lain kosong. */
    private function setChannels(array $channels, string $contact = ''): void
    {
        Setting::put('payment_channels', $channels);
        Setting::put('payment_contact', $contact);
    }

    private function channel(string $name, string $number, string $holder = ''): array
    {
        return ['name' => $name, 'type' => 'bank', 'number' => $number, 'holder' => $holder];
    }

    private function pendingPayment(): Payment
    {
        $subscription = Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->proPlan->id,
            'status' => 'pending',
        ]);

        return Payment::create([
            'reference' => 'SA-MANUAL01',
            'user_id' => $this->user->id,
            'plan_id' => $this->proPlan->id,
            'subscription_id' => $subscription->id,
            'provider' => 'manual',
            'amount' => 99000,
            'status' => 'pending',
        ]);
    }

    public function test_checkout_creates_pending_payment_and_redirects_to_my(): void
    {
        $this->actingAs($this->user)
            ->post(route('subscription.checkout', $this->proPlan))
            ->assertRedirect(route('subscription.my'));

        $this->assertDatabaseHas('payments', [
            'user_id' => $this->user->id,
            'provider' => 'manual',
            'status' => 'pending',
        ]);
    }

    public function test_page_lists_only_channels_with_a_number(): void
    {
        $this->setChannels([
            $this->channel('Bank Mandiri', '1300012345678', 'PT Sarjana AI'),
            $this->channel('Bank Jatim', ''),
            $this->channel('GoPay', '08123456789'),
        ]);

        $this->pendingPayment();

        $this->actingAs($this->user)
            ->get(route('subscription.my'))
            ->assertOk()
            ->assertSee('1300012345678')
            ->assertSee('PT Sarjana AI')
            ->assertSee('08123456789')
            ->assertDontSee('Bank Jatim');
    }

    public function test_page_shows_amount_and_reference(): void
    {
        $this->setChannels([$this->channel('Bank Jago', '5000123456')]);

        $this->pendingPayment();

        $this->actingAs($this->user)
            ->get(route('subscription.my'))
            ->assertSee('99.000')
            ->assertSee('SA-MANUAL01')
            ->assertSee('untuk paket Pro', false);
    }

    public function test_no_instructions_section_when_every_channel_is_empty(): void
    {
        $this->setChannels([
            $this->channel('Bank Mandiri', ''),
            $this->channel('OVO', ''),
        ]);

        $this->pendingPayment();

        $this->actingAs($this->user)
            ->get(route('subscription.my'))
            ->assertOk()
            ->assertDontSee('Instruksi pembayaran');
    }

    public function test_no_instructions_section_when_payment_is_already_paid(): void
    {
        $this->setChannels([$this->channel('Bank Mandiri', '1300012345678')]);

        $this->pendingPayment()->update(['status' => 'paid']);

        $this->actingAs($this->user)
            ->get(route('subscription.my'))
            ->assertOk()
            ->assertDontSee('1300012345678');
    }

    public function test_whatsapp_contact_is_linked_when_configured(): void
    {
        $this->setChannels(
            [$this->channel('Bank Mandiri', '1300012345678')],
            'https://wa.me/628123456789'
        );

        $this->pendingPayment();

        $this->actingAs($this->user)
            ->get(route('subscription.my'))
            ->assertSee('https://wa.me/628123456789', false);
    }

    public function test_user_can_pick_a_channel_for_their_pending_payment(): void
    {
        $this->setChannels([$this->channel('Bank Mandiri', '1300012345678')]);

        $payment = $this->pendingPayment();

        $this->actingAs($this->user)
            ->patch(route('subscription.payments.channel', $payment), ['channel' => 'Bank Mandiri'])
            ->assertRedirect();

        $this->assertSame('Bank Mandiri', $payment->fresh()->channel);

        $this->actingAs($this->user)
            ->get(route('subscription.my'))
            ->assertSee('Dipilih');
    }

    public function test_user_cannot_pick_a_channel_on_a_paid_payment(): void
    {
        $this->setChannels([$this->channel('Bank Mandiri', '1300012345678')]);

        $payment = $this->pendingPayment();
        $payment->update(['status' => 'paid']);

        $this->actingAs($this->user)
            ->patch(route('subscription.payments.channel', $payment), ['channel' => 'Bank Mandiri'])
            ->assertStatus(422);

        $this->assertNull($payment->fresh()->channel);
    }

    public function test_user_cannot_pick_a_channel_on_someone_elses_payment(): void
    {
        $payment = $this->pendingPayment();

        $other = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($other)
            ->patch(route('subscription.payments.channel', $payment), ['channel' => 'Bank Mandiri'])
            ->assertStatus(403);
    }
}
