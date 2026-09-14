<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors(['email' => 'Gagal masuk dengan Google. Coba lagi.']);
        }

        $email = strtolower($google->getEmail());

        if (! $email) {
            return redirect()->route('login')->withErrors(['email' => 'Akun Google tidak memberikan alamat email.']);
        }

        $user = User::where('email', $email)->first()
            ?? User::create([
                'name' => $google->getName() ?: $email,
                'email' => $email,
                'email_verified_at' => now(),
                'password' => Str::random(32),
                'avatar' => $google->getAvatar(),
            ]);

        if (! $user->is_active) {
            return redirect()->route('login')->withErrors(['email' => 'Akun Anda dinonaktifkan.']);
        }

        Auth::login($user, remember: true);
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
