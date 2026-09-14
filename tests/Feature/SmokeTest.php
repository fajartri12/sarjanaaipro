<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_see_landing_and_auth_pages(): void
    {
        $this->get('/')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_student_pages_render(): void
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);

        foreach ([
            '/dashboard',
            '/projects',
            '/projects/create',
            '/titles',
            '/research',
            '/references',
            '/reviewer',
            '/sempro',
            '/subscription/prices',
            '/subscription/my',
            '/profile',
        ] as $uri) {
            $this->actingAs($student)->get($uri)->assertOk();
        }
    }

    public function test_admin_pages_render(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        foreach ([
            '/admin',
            '/admin/users',
            '/admin/usage',
            '/admin/payments',
            '/admin/plans',
            '/admin/activity',
        ] as $uri) {
            $this->actingAs($admin)->get($uri)->assertOk();
        }
    }

    public function test_student_cannot_open_admin(): void
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($student)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_change_subscription_expiry(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'price' => 50000, 'interval' => 'month', 'sort' => 1]);

        $subscription = Subscription::create([
            'user_id' => $student->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)
            ->patch("/admin/users/{$student->id}/subscription", ['ends_at' => now()->addMonths(3)->format('Y-m-d')])
            ->assertRedirect();

        $this->assertTrue($subscription->refresh()->ends_at->isFuture());
        $this->assertSame('active', $subscription->status);
    }

    public function test_subscription_expiry_requires_a_subscription(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->actingAs($admin)
            ->patch("/admin/users/{$student->id}/subscription", ['ends_at' => now()->addMonth()->format('Y-m-d')])
            ->assertSessionHas('error');
    }

    public function test_student_cannot_change_subscription_expiry(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->actingAs($student)
            ->patch("/admin/users/{$student->id}/subscription", ['ends_at' => now()->addMonth()->format('Y-m-d')])
            ->assertForbidden();
    }
}
