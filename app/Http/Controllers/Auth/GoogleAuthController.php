<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $link = $request->routeIs('google.connect');
        $mode = $link ? 'link' : ($request->query('mode') === 'register' ? 'register' : 'login');
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return $this->failure($mode, 'Login Google belum tersedia. Silakan gunakan email dan kata sandi.');
        }
        $state = Str::random(64);
        $verifier = Str::random(96);
        $request->session()->forget('google_signup');
        $request->session()->put('google_oauth', ['state' => $state, 'verifier' => $verifier, 'mode' => $mode, 'actor_id' => $link ? Auth::id() : null, 'expires' => time() + 600]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google.client_id'), 'redirect_uri' => $this->callbackUrl(),
            'response_type' => 'code', 'scope' => 'openid email profile', 'state' => $state,
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256', 'prompt' => 'select_account',
        ]));
    }

    public function callback(Request $request)
    {
        $flow = $request->session()->pull('google_oauth');
        if (! is_array($flow) || ($flow['expires'] ?? 0) < time() || ! is_string($request->query('state')) || ! hash_equals($flow['state'], $request->query('state'))) {
            return $this->failure('login', 'Sesi Google tidak valid atau sudah berakhir. Silakan coba lagi.');
        }
        $mode = $flow['mode'];
        if ($request->filled('error') || ! $request->filled('code')) {
            return $this->failure($mode, 'Proses Google dibatalkan. Silakan pilih cara masuk lainnya.');
        }
        try {
            $token = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
                'code' => $request->string('code')->toString(), 'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'), 'redirect_uri' => $this->callbackUrl(),
                'grant_type' => 'authorization_code', 'code_verifier' => $flow['verifier'],
            ])->throw()->json();
            if (empty($token['access_token']) || ! is_string($token['access_token'])) {
                throw new \RuntimeException('Missing access token');
            }
            $identity = Http::withToken($token['access_token'])->timeout(15)->get('https://openidconnect.googleapis.com/v1/userinfo')->throw()->json();
            if (! is_string($identity['sub'] ?? null) || $identity['sub'] === '' || strlen($identity['sub']) > 255 || ! filter_var($identity['email'] ?? '', FILTER_VALIDATE_EMAIL) || ($identity['email_verified'] ?? false) !== true) {
                throw new \RuntimeException('Unverified identity');
            }
        } catch (\Throwable $exception) {
            // Do not log OAuth responses or tokens.
            return $this->failure($mode, 'Identitas Google belum dapat diverifikasi. Silakan coba lagi.');
        }
        $email = Str::lower($identity['email']);
        $linked = User::where('google_id', $identity['sub'])->first();
        if ($mode === 'link') {
            if (! Auth::check() || (int) $flow['actor_id'] !== (int) Auth::id() || Str::lower(Auth::user()->email ?? '') !== $email || ($linked && $linked->id !== Auth::id())) {
                return $this->failure('link', 'Gunakan akun Google dengan email yang sama dengan akun Anda.');
            }
            Auth::user()->forceFill(['google_id' => $identity['sub'], 'email_verified_at' => now()])->save();

            return redirect()->route('profile.edit')->with('status', 'google-connected');
        }
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        if ($linked) {
            Auth::login($linked);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }
        if (User::where('email', $email)->exists()) {
            return $this->failure('login', 'Email ini sudah terdaftar. Masuk dengan kata sandi, lalu hubungkan Google melalui profil akun.');
        }
        $request->session()->regenerate();
        $request->session()->put('google_signup', ['id' => $identity['sub'], 'email' => $email, 'name' => $identity['name'] ?? '', 'expires' => time() + 1800]);

        return redirect('/?auth=register')->with('status', 'Google berhasil diverifikasi. Lengkapi data anggota untuk menyelesaikan pendaftaran.');
    }

    private function callbackUrl(): string
    {
        return config('services.google.redirect_uri') ?: route('google.callback');
    }

    private function failure(string $mode, string $message)
    {
        return $mode === 'link' ? redirect()->route('profile.edit')->withErrors(['google' => $message]) : redirect('/?auth='.($mode === 'register' ? 'register' : 'login'))->withErrors(['google' => $message]);
    }
}
