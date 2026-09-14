<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_wrong_password_shows_indonesian_message(): void
    {
        $user = User::factory()->create();

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertRedirect('/login');

        $page = $this->get('/login');
        $message = trans('auth.failed');

        $this->assertSame('Email atau kata sandi yang Anda masukkan salah.', $message);
        $this->assertSame(2, substr_count($page->getContent(), $message), 'Pesan muncul dua kali: di alert atas dan di bawah kolom email.');
    }

    public function test_unknown_email_shows_the_same_message(): void
    {
        $this->from('/login')->post('/login', [
            'email' => 'tidak-terdaftar@kampus.ac.id',
            'password' => 'apa-saja',
        ])->assertSessionHasErrors(['email' => trans('auth.failed')]);
    }

    public function test_empty_fields_are_labelled_in_indonesian(): void
    {
        $this->from('/login')->post('/login', ['email' => '', 'password' => ''])
            ->assertSessionHasErrors([
                'email' => 'Kolom Email wajib diisi.',
                'password' => 'Kolom Kata sandi wajib diisi.',
            ]);
    }
}
