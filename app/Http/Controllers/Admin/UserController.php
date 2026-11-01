<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Province;
use App\Models\City;
use App\Models\Dojo;
use App\Models\BeltLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = User::with(['province', 'city', 'dojo']);

        // --- FILTER KEAMANAN ROLE ---
        if (!$user->hasRole('pb') && $user->hasRole(['pengprov', 'admin_pengprov'])) {
            $query->where('province_id', $user->province_id);
        } elseif ($user->role === 'pengcab') {
            $query->where('city_id', $user->city_id);
        }

        // --- FILTER PENCARIAN (SEARCH) ---
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // --- FILTER ROLE ---
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // --- FILTER WILAYAH (PROVINSI) ---
        if ($request->filled('province_id')) {
            $query->where('province_id', $request->province_id);
        }

        if (in_array($request->query('status'), ['0', '1'], true)) {
            $query->where('is_active', $request->query('status'));
        }

        $users = \App\Support\TableSort::apply($query, $request, [
            'name' => 'name', 'role' => 'role', 'province' => ['province', 'name'], 'status' => 'is_active',
        ])->paginate(10)->appends($request->query());

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $admin = auth()->user();
        $provinces = Province::orderBy('name')->get();
        $beltLevels = BeltLevel::all();

        // Daftar role yang tersedia berdasarkan siapa yang login
        $availableRoles = $this->getAvailableRoles($admin);

        return view('admin.users.create_admin', compact('provinces', 'beltLevels', 'availableRoles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'roles' => 'required|array|min:1',
            'province_id' => [Rule::requiredIf(!array_intersect(['pb', 'verifikator_pb', 'verifikator_dewan_guru'], $request->input('roles', []))), 'nullable', 'exists:provinces,id'],
            'city_id' => 'nullable|exists:cities,id',
            'dojo_id' => 'nullable|exists:dojos,id',
        ]);

        // Role utama diambil dari pilihan pertama array roles
        $this->validateVerifierAssignment($request);
        $primaryRole = \App\Http\Middleware\RoleManager::highestRole($request->roles);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $primaryRole,      // String (untuk middleware/legacy)
            'roles' => $request->roles,   // Array/JSON (untuk multi-role)
            'province_id' => in_array($primaryRole, ['verifikator_pb', 'verifikator_dewan_guru'], true) ? null : $request->province_id,
            'city_id' => in_array($primaryRole, ['verifikator_pb', 'verifikator_dewan_guru'], true) ? null : $request->city_id,
            'dojo_id' => in_array($primaryRole, ['verifikator_pb', 'verifikator_dewan_guru'], true) ? null : $request->dojo_id,
            'is_active' => true,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Admin berhasil dibuat.');
    }

    public function edit(User $user)
    {
        $this->authorizeVerifierTarget($user);
        $admin = auth()->user();
        $provinces = Province::orderBy('name')->get();

        // Diperbaiki: Load data wilayah agar dropdown terisi saat edit dibuka
        $cities = $user->province_id ? City::where('province_id', $user->province_id)->orderBy('name')->get() : collect();
        $dojos = $user->city_id ? Dojo::where('city_id', $user->city_id)->orderBy('name')->get() : collect();

        $availableRoles = $this->getAvailableRoles($admin);

        if (is_null($user->roles)) {
            $user->roles = [$user->role];
        }

        return view('admin.users.edit', compact('user', 'provinces', 'cities', 'dojos', 'availableRoles'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeVerifierTarget($user);
        if ($user->hasRole('pb')) {
            $preserved = array_intersect($user->allRoles(), ['pb', 'super_admin']);
            $request->merge(['roles' => array_values(array_unique(array_merge((array) $request->input('roles', []), $preserved)))]);
        }
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'roles' => ['required', 'array', 'min:1'],
            'is_active' => ['required', 'boolean'],
            'province_id' => [Rule::requiredIf(!array_intersect(['pb', 'verifikator_pb', 'verifikator_dewan_guru'], $request->input('roles', []))), 'nullable', 'exists:provinces,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'dojo_id' => ['nullable', 'exists:dojos,id'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $allRoles = $request->roles;
        $this->validateVerifierAssignment($request);
        $primaryRole = \App\Http\Middleware\RoleManager::highestRole($allRoles);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $primaryRole,
            'roles' => $allRoles,
            'is_active' => $request->is_active,
            'province_id' => in_array($primaryRole, ['verifikator_pb', 'verifikator_dewan_guru'], true) ? null : $request->province_id,
            'city_id' => $request->city_id,
            'dojo_id' => $request->dojo_id, // Default ambil dari request
        ];

        // --- Logika Sanitasi Wilayah yang Diperbaiki ---
        // Kita cek apakah user memiliki role yang membutuhkan Dojo. 
        // Jika TIDAK ADA role dojo/penguji dalam array roles, baru kita set null.
        $needsDojo = count(array_intersect(['admin_dojo', 'penguji', 'member'], $allRoles)) > 0;
        $needsCity = count(array_intersect(['pengcab', 'admin_dojo', 'penguji', 'member', 'verifikator_pengcab'], $allRoles)) > 0;

        if (in_array($primaryRole, ['pb', 'verifikator_pb', 'verifikator_dewan_guru'], true)) {
            $data['province_id'] = null;
            $data['city_id'] = null;
            $data['dojo_id'] = null;
        } else {
            if (!$needsCity) {
                $data['city_id'] = null;
            }
            if (!$needsDojo) {
                $data['dojo_id'] = null;
            }
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'Data pengurus ' . $user->name . ' berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $this->authorizeVerifierTarget($user);
        // Cegah hapus diri sendiri
        if (auth()->id() === $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();
        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil dihapus.');
    }

    /**
     * Helper untuk mendapatkan daftar role yang boleh dibuat/diedit
     */
    private function getAvailableRoles($admin)
    {
        if ($admin->hasRole('pb')) {
            return [
                'pb' => 'PB / Super Admin',
                'super_admin' => 'Super Admin (akses sistem)',
                ...\App\Services\MembershipReview::ROLES,
                'pengprov' => 'Admin Provinsi (Pengprov)',
                'pengcab' => 'Admin Kota/Kab (Pengcab)',
                'admin_dojo' => 'Admin Dojo (Sensei)',
                'penguji' => 'Penguji Ujian Sabuk',
            ];
        }

        if (in_array($admin->role, ['pengprov', 'admin_pengprov'], true)) {
            return [
                'verifikator_pengprov' => 'Tim Verifikasi Pengprov',
                'verifikator_pengcab' => 'Tim Verifikasi Pengcab',
                'pengcab' => 'Admin Kota/Kab (Pengcab)',
                'admin_dojo' => 'Admin Dojo (Sensei)',
                'penguji' => 'Penguji Ujian Sabuk',
            ];
        }

        return [];
    }

    private function authorizeVerifierTarget(User $target): void
    {
        abort_if($target->hasRole('pb') && !auth()->user()->hasRole('pb'), 403);
        if ($target->hasRole(array_keys(\App\Services\MembershipReview::ROLES)) && !auth()->user()->hasRole('pb')) {
            abort_unless(! $target->hasRole(['verifikator_pb', 'verifikator_dewan_guru']) && auth()->user()->province_id
                && (int) $target->province_id === (int) auth()->user()->province_id, 403);
        }
    }

    private function validateVerifierAssignment(Request $request): void
    {
        $roles = $request->input('roles', []);
        $verifiers = array_intersect($roles, array_keys(\App\Services\MembershipReview::ROLES));
        if (!$verifiers) return;
        $allowed = array_keys($this->getAvailableRoles($request->user()));
        $request->validate([
            'roles.*' => ['string', Rule::in($allowed)],
            'city_id' => [Rule::requiredIf(in_array('verifikator_pengcab', $roles) && !array_intersect(['pb', 'verifikator_pb', 'verifikator_dewan_guru'], $roles)), 'nullable', Rule::exists('cities', 'id')->where('province_id', $request->province_id)],
        ]);
        if (!$request->user()->hasRole('pb')) {
            abort_unless($request->user()->province_id && (int) $request->province_id === (int) $request->user()->province_id, 403);
        }
    }
}
