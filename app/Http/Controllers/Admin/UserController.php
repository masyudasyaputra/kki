<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{User, Province, City, Dojo, BeltLevel, AccessAudit};
use App\Services\RoleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash};
use Illuminate\Validation\Rule;
use App\Models\Official;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['province', 'city', 'dojo']);
        if (!RoleAccess::managesUsers($request->user())) {
            $query->where('province_id', $request->user()->province_id ?? 0)
                ->whereNotIn('role', RoleAccess::NATIONAL);
            foreach (RoleAccess::NATIONAL as $role) $query->where(fn ($q) => $q->whereNull('roles')->orWhereJsonDoesntContain('roles', $role));
        }
        if ($request->filled('search')) {
            $term = '%'.trim($request->search).'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
        }
        if ($request->filled('role')) {
            $role = $request->role;
            $query->where(fn ($q) => $q->where('role', $role)->orWhereJsonContains('positions', $role)->orWhereJsonContains('roles', $role));
        }
        if ($request->filled('province_id')) $query->where('province_id', $request->province_id);
        if (in_array($request->query('status'), ['0', '1'], true)) $query->where('is_active', $request->query('status'));
        $userQuery = \App\Support\TableSort::apply($query, $request, [
            'name' => 'name', 'role' => 'role', 'province' => ['province', 'name'], 'status' => 'is_active',
        ]);
        $users = \App\Support\PageSize::paginate($userQuery, $request, 10);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create_admin', $this->formData());
    }

    public function edit(User $user)
    {
        $this->authorizeTarget($user);
        $user->load('official');
        return view('admin.users.edit', $this->formData() + ['user' => $user,
            'appointmentCities' => City::when(!RoleAccess::managesUsers(auth()->user()), fn ($q) => $q->where('province_id', auth()->user()->province_id))->orderBy('name')->get(['id', 'name', 'province_id']),
            'cities' => City::where('province_id', $user->province_id)->orderBy('name')->get(),
            'dojos' => Dojo::where('city_id', $user->city_id)->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($request, $data) {
            $user = User::create($data + ['is_active' => true]);
            AccessAudit::record('user.created', $request->user(), $user, [], $this->accessData($user));
        });
        return redirect()->route('admin.users.index')->with('success', 'Akun, jabatan, dan hak akses disimpan.');
    }

    public function appoint(Request $request, User $user)
    {
        $this->authorizeTarget($user);
        abort_if($user->hasRole(RoleAccess::NATIONAL), 403, 'Gunakan pengaturan akses untuk akun tingkat pusat.');
        $national = RoleAccess::managesUsers($request->user());
        $data = $request->validate([
            'level' => ['required', Rule::in($national ? ['pusat', 'provinsi', 'pengcab'] : ['pengcab'])],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'position' => ['required', 'string', 'max:255'],
            'province_id' => ['exclude_if:level,pusat', 'required', 'integer', 'exists:provinces,id'],
            'city_id' => ['exclude_unless:level,pengcab', 'required', 'integer', Rule::exists('cities', 'id')->where('province_id', $request->province_id)],
            'sk_number' => ['required', 'string', 'max:100'],
            'sk_expiry_date' => ['required', 'date', 'after_or_equal:today'],
            'sk_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        abort_unless($national || (int) $data['province_id'] === (int) $request->user()->province_id, 403);
        $data['province_id'] = $data['level'] === 'pusat' ? null : $data['province_id'];
        $newPath = $request->file('sk_document')?->store('sk-documents/officials', 'local');
        $oldPath = null;
        try {
            DB::transaction(function () use ($request, $user, $data, $newPath, &$oldPath) {
                $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                $this->authorizeTarget($user);
                abort_if($user->hasRole(RoleAccess::NATIONAL), 403);
                $official = $user->official;
                $before = $this->accessData($user) + ['official' => $official?->toArray()];
                $oldPath = $official?->sk_document_path;
                $role = match ($data['level']) { 'pusat' => 'pb', 'provinsi' => 'pengprov', default => 'pengcab' };
                $positions = array_values(array_diff($user->positions ?? [], ['pengprov', 'pengcab', 'admin_dojo']));
                $positions[] = $role;
                if ($user->registration_type === 'official') $positions = [$role];
                $user->update([
                    'name' => $data['name'] ?? $user->name,
                    'role' => $role, 'positions' => array_values(array_unique($positions)),
                    'province_id' => $data['province_id'],
                    'city_id' => $data['level'] === 'pengcab' ? $data['city_id'] : null,
                    'dojo_id' => null, 'is_active' => true,
                ]);
                $official = Official::updateOrCreate(['user_id' => $user->id], [
                    'name' => $user->name, 'phone_number' => $data['phone_number'] ?? $user->whatsapp,
                    'position' => $data['position'], 'level' => $data['level'],
                    'province_id' => $user->province_id, 'city_id' => $user->city_id,
                    'sk_number' => $data['sk_number'], 'sk_expiry_date' => $data['sk_expiry_date'],
                    'sk_document_path' => $newPath ?? $oldPath,
                ]);
                AccessAudit::record('official.appointed', $request->user(), $user, $before,
                    $this->accessData($user) + ['official' => $official->toArray()]);
            });
        } catch (\Throwable $exception) {
            if ($newPath) Storage::disk('local')->delete($newPath);
            throw $exception;
        }
        if ($newPath && $oldPath) Storage::disk('local')->delete($oldPath);
        if ($request->input('return_to') === 'officials') {
            return redirect()->route('admin.officials.index')->with('success', 'Pengurus ditetapkan. Akun aktif, akses wilayah, dan data SK tersimpan sekaligus.');
        }
        return redirect()->route($request->input('return_to') === 'official-verifications' ? 'admin.official-verifications.show' : 'admin.users.edit', $user)->with('success', 'Pengurus ditetapkan. Akun aktif, akses wilayah, dan data SK tersimpan sekaligus.');
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeTarget($user);
        $data = $this->validated($request, $user);
        DB::transaction(function () use ($request, $user, $data) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $before = $this->accessData($user);
            $user->update($data);
            AccessAudit::record('user.access.updated', $request->user(), $user, $before, $this->accessData($user));
        });
        return redirect()->route('admin.users.index')->with('success', 'Jabatan, hak akses, dan wilayah diperbarui.');
    }

    public function destroy(User $user)
    {
        $this->authorizeTarget($user);
        abort_if(auth()->id() === $user->id, 422, 'Anda tidak dapat menghapus akun sendiri.');
        DB::transaction(function () use ($user) {
            AccessAudit::record('user.deleted', auth()->user(), $user, $this->accessData($user), []);
            $user->delete();
        });
        return redirect()->route('admin.users.index')->with('success', 'Akun dihapus.');
    }

    private function formData(): array
    {
        $national = RoleAccess::managesUsers(auth()->user());
        $availablePositions = $national ? RoleAccess::POSITIONS : array_intersect_key(RoleAccess::POSITIONS, array_flip(['pengcab', 'admin_dojo', 'member']));
        $availablePermissions = $national ? RoleAccess::PERMISSIONS : array_intersect_key(RoleAccess::PERMISSIONS,
            array_flip(['verifikator_pengprov', 'verifikator_pengcab', 'panitia_ujian', 'penguji']));
        return ['availablePositions' => $availablePositions, 'availablePermissions' => $availablePermissions,
            'availableRoles' => $availablePositions + $availablePermissions,
            'provinces' => Province::when(!$national, fn ($q) => $q->whereKey(auth()->user()->province_id ?? 0))->orderBy('name')->get(),
            'beltLevels' => BeltLevel::active()->get()];
    }

    private function authorizeTarget(User $target): void
    {
        if (RoleAccess::managesUsers(auth()->user())) return;
        abort_unless(auth()->user()->province_id && (int) $target->province_id === (int) auth()->user()->province_id
            && !$target->hasRole(RoleAccess::NATIONAL), 403);
    }

    private function validated(Request $request, ?User $target = null): array
    {
        $form = $this->formData();
        // Accept old combined submissions while storing positions and permissions separately.
        $submitted = array_values(array_unique(array_merge((array)$request->input('positions', []), (array)$request->input('roles', []))));
        $request->merge(['roles' => $submitted]);
        $data = $request->validate([
            'name' => 'required|string|max:255', 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($target?->id)],
            'password' => [$target ? 'nullable' : 'required', 'string', 'min:8'],
            'roles' => 'required|array|min:1', 'roles.*' => ['required', 'string', Rule::in(array_keys($form['availableRoles']))],
            'positions' => 'sometimes|array', 'positions.*' => ['string', Rule::in(array_keys($form['availablePositions']))],
            'province_id' => 'nullable|integer|exists:provinces,id',
            'city_id' => ['nullable', 'integer', Rule::exists('cities', 'id')->where('province_id', $request->province_id)],
            'dojo_id' => ['nullable', 'integer', Rule::exists('dojos', 'id')->where('city_id', $request->city_id)],
            'first_coach' => 'nullable|string|max:255', 'is_active' => [$target ? 'required' : 'sometimes', 'boolean'],
        ]);
        $positions = array_values(array_intersect(array_keys(RoleAccess::POSITIONS), $submitted));
        $permissions = array_values(array_intersect(array_keys(RoleAccess::PERMISSIONS), $submitted));
        if (!$positions) $positions = ['member'];
        $regional = array_intersect($submitted, ['pengprov', 'pengcab', 'admin_dojo', 'verifikator_pengprov', 'verifikator_pengcab']);
        $needsProvince = (bool) $regional || !array_intersect($submitted, RoleAccess::NATIONAL);
        $needsCity = (bool) array_intersect($submitted, ['pengcab', 'admin_dojo', 'verifikator_pengcab']);
        $request->validate(['province_id' => [Rule::requiredIf($needsProvince)], 'city_id' => [Rule::requiredIf($needsCity)],
            'dojo_id' => [Rule::requiredIf(in_array('admin_dojo', $positions, true))]]);
        if (!RoleAccess::managesUsers($request->user())) {
            abort_unless($request->user()->province_id && (int)$request->province_id === (int)$request->user()->province_id, 403);
        }
        if ($target && $target->id === $request->user()->id && $target->hasRole(['pb', 'super_admin'])) {
            abort_unless(array_intersect($positions, ['pb', 'super_admin']), 422, 'Akun pengelola tidak boleh mencabut akses pengelolaan dirinya sendiri.');
        }
        $result = collect($data)->only(['name', 'email', 'is_active', 'first_coach'])->all() + [
            'role' => RoleAccess::primary($positions), 'positions' => $positions, 'roles' => $permissions,
            'province_id' => $data['province_id'] ?? null, 'city_id' => $data['city_id'] ?? null, 'dojo_id' => $data['dojo_id'] ?? null];
        if (!empty($data['password'])) $result['password'] = Hash::make($data['password']);
        return $result;
    }

    private function accessData(User $user): array
    {
        return $user->only(['name', 'role', 'positions', 'roles', 'province_id', 'city_id', 'dojo_id', 'is_active']);
    }
}
