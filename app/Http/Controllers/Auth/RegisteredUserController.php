<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\BeltLevel;
use App\Models\Province;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    private function googleIdentity(Request $request): ?array
    {
        $identity = $request->session()->get('google_signup');
        if (! is_array($identity) || ($identity['expires'] ?? 0) < time()) {
            $request->session()->forget('google_signup');

            return null;
        }

        return $identity;
    }

    public function create(Request $request)
    {
        if (! $request->boolean('_auth_modal') && $request->header('Sec-Fetch-Dest') !== 'iframe') {
            return redirect('/?auth=register');
        }
        $provinces = Province::orderBy('name')->get();
        $beltLevels = BeltLevel::orderBy('order')->get();
        $googleIdentity = $this->googleIdentity($request);

        return view('auth.register', compact('provinces', 'beltLevels', 'googleIdentity'));
    }

    public function store(Request $request)
    {
        $identity = $this->googleIdentity($request);
        if ($identity) {
            $request->merge(['email' => $identity['email']]);
        }
        if ($request->filled('email')) {
            $request->merge(['email' => Str::lower(trim($request->email))]);
        }
        if ($request->filled('whatsapp')) {
            $phone = preg_replace('/[^0-9]/', '', $request->whatsapp);
            if (str_starts_with($phone, '62')) {
                $phone = '0'.substr($phone, 2);
            }
            $request->merge(['whatsapp' => $phone]);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'regex:/^[0-9]{8,16}$/', Rule::unique('users', 'whatsapp')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => [$identity ? 'nullable' : 'required', 'confirmed', Rules\Password::defaults()],
            'province_id' => ['nullable', 'required_with:city_id', 'integer', 'exists:provinces,id'],
            'city_id' => ['nullable', 'required_with:dojo_id', 'integer', Rule::exists('cities', 'id')->where('province_id', $request->input('province_id'))],
            'dojo_id' => ['nullable', 'integer', Rule::exists('dojos', 'id')->where('city_id', $request->input('city_id'))],
            'belt_level_id' => ['nullable', 'integer', 'exists:belt_levels,id'],
        ], [
            'province_id.required_with' => 'Pilih provinsi untuk cabang yang dipilih.',
            'city_id.required_with' => 'Pilih kabupaten / kota untuk dojo yang dipilih.',
            'city_id.exists' => 'Cabang harus sesuai dengan provinsi yang dipilih.',
            'dojo_id.exists' => 'Dojo harus sesuai dengan kabupaten / kota yang dipilih.',
        ]);
        $user = DB::transaction(function () use ($data, $identity) {
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'], 'parent_name' => $data['parent_name'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null, 'password' => Hash::make($data['password'] ?? Str::random(64)),
                'province_id' => $data['province_id'] ?? null, 'city_id' => $data['city_id'] ?? null,
                'dojo_id' => $data['dojo_id'] ?? null, 'belt_level_id' => $data['belt_level_id'] ?? null,
                'role' => 'member', 'is_active' => false, 'google_id' => $identity['id'] ?? null,
            ]);
            if ($identity) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            return $user;
        });
        $request->session()->forget('google_signup');
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
