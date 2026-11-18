<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\BeltLevel;
use App\Models\Province;
use App\Models\City;
use App\Models\Payment;
use App\Services\Payment\DokuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MemberController extends Controller
{
    public function __construct(
        protected DokuService $doku
    ) {
    }

    public function index(Request $request)
    {
        $actor = $request->user();
        $role = collect(['pb', 'pengprov', 'admin_pengprov', 'pengcab', 'admin_pengcab', 'admin_dojo'])
            ->first(fn ($candidate) => $actor->hasRole($candidate));
        abort_unless($role, 403);
        $role = ['admin_pengprov' => 'pengprov', 'admin_pengcab' => 'pengcab'][$role] ?? $role;
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:0,1'],
            'province_id' => ['nullable', 'integer'], 'city_id' => ['nullable', 'integer'],
            'dojo_id' => ['nullable', 'integer'], 'belt_level_id' => ['nullable', 'integer'],
        ]);
        $base = app(\App\Services\Dashboard\DashboardSummary::class)->users($actor, $role)->members();
        $query = (clone $base)->with(['province', 'city', 'dojo', 'beltLevel']);
        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term)
                ->orWhere('whatsapp', 'like', $term)->orWhere('id', 'like', $term)
                ->orWhere(fn ($numberQuery) => $numberQuery->whereMemberNumber($filters['search'])));
        }
        foreach (['province_id', 'city_id', 'dojo_id', 'belt_level_id'] as $column) {
            if (!empty($filters[$column])) $query->where($column, $filters[$column]);
        }
        if (isset($filters['status'])) $query->where('is_active', $filters['status']);
        $memberQuery = \App\Support\TableSort::apply($query, $request, [
            'name' => 'name', 'belt' => ['beltLevel', 'order'], 'dojo' => ['dojo', 'name'],
            'city' => ['city', 'name'], 'status' => 'is_active',
        ]);
        $members = \App\Support\PageSize::paginate($memberQuery, $request, 10);
        $provinces = Province::whereIn('id', (clone $base)->select('province_id'))->orderBy('name')->get();
        $cities = City::whereIn('id', (clone $base)->select('city_id'))->orderBy('name')->get();
        $dojos = \App\Models\Dojo::whereIn('id', (clone $base)->select('dojo_id'))->orderBy('name')->get();
        $beltLevels = BeltLevel::active()->orderBy('order')->get();

        return view('admin.members.index', compact('members', 'provinces', 'cities', 'dojos', 'beltLevels'));
    }

    public function export(Request $request)
    {
        $actor = $request->user();
        $role = collect(['pb', 'pengprov', 'admin_pengprov', 'pengcab', 'admin_pengcab', 'admin_dojo'])
            ->first(fn ($candidate) => $actor->hasRole($candidate));
        abort_unless($role, 403);
        $role = ['admin_pengprov' => 'pengprov', 'admin_pengcab' => 'pengcab'][$role] ?? $role;
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:0,1'],
            'province_id' => ['nullable', 'integer'], 'city_id' => ['nullable', 'integer'],
            'dojo_id' => ['nullable', 'integer'], 'belt_level_id' => ['nullable', 'integer'],
        ]);

        $query = app(\App\Services\Dashboard\DashboardSummary::class)
            ->users($actor, $role)->members()
            ->with(['province', 'city', 'dojo', 'beltLevel']);
        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term)
                ->orWhere('whatsapp', 'like', $term)->orWhere('id', 'like', $term)
                ->orWhere(fn ($numberQuery) => $numberQuery->whereMemberNumber($filters['search'])));
        }
        foreach (['province_id', 'city_id', 'dojo_id', 'belt_level_id'] as $column) {
            if (!empty($filters[$column])) $query->where($column, $filters[$column]);
        }
        if (isset($filters['status'])) $query->where('is_active', $filters['status']);
        $query = \App\Support\TableSort::apply($query, $request, [
            'name' => 'name', 'belt' => ['beltLevel', 'order'], 'dojo' => ['dojo', 'name'],
            'city' => ['city', 'name'], 'status' => 'is_active',
        ]);

        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['No. Anggota', 'Nama', 'WhatsApp', 'Email', 'Status', 'Sabuk', 'Dojo', 'Kabupaten / Kota', 'Kecamatan', 'Provinsi']);
            foreach ($query->get() as $member) {
                $row = [
                    $member->member_number, $member->name, $member->whatsapp, $member->email,
                    $member->is_active ? 'Aktif' : 'Nonaktif', $member->beltLevel?->display_name,
                    $member->dojo?->name, $member->city?->name, $member->district,
                    $member->province?->name,
                ];
                // Keep spreadsheet cells as text to avoid interpreting user data as formulas.
                $row = array_map(fn ($value) => preg_match('/^[=+@\\-\\t\\r]/u', (string) $value) ? "'" . $value : $value, $row);
                fputcsv($output, $row);
            }
            fclose($output);
        }, 'data-anggota-' . now()->format('Ymd-His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * FORM: Pendaftaran kolektif
     */
    public function create()
    {
        $user = auth()->user();
        $role = strtolower((string) $user->role);

        $beltLevels = BeltLevel::active()->orderBy('order')->get();

        $provinces = Province::query()
            ->when($role !== 'pb', function ($q) use ($user) {
                return $q->where('id', $user->province_id);
            })
            ->orderBy('name')
            ->get();

        $cities = City::query()
            ->when($role === 'pengprov', function ($q) use ($user) {
                return $q->where('province_id', $user->province_id);
            })
            ->when($role === 'pengcab', function ($q) use ($user) {
                return $q->where('id', $user->city_id);
            })
            ->orderBy('name')
            ->get();

        return view('admin.members.create', compact('provinces', 'beltLevels', 'cities', 'role'));
    }

    /**
     * REVIEW: halaman konfirmasi sebelum bayar
     * POST only
     */
    public function review(Request $request)
    {
        $admin = auth()->user();
        $role = strtolower((string) $admin->role);

        if ($request->isMethod('get')) {
            return redirect()->route('admin.members.create')
                ->withErrors(['review' => 'Silakan isi form pendaftaran terlebih dahulu.']);
        }

        $validated = $request->validate([
            'members' => ['required', 'array', 'min:1'],
            'members.*.name' => ['required', 'string', 'max:255'],
            'members.*.parent_name' => ['nullable', 'string', 'max:255'],
            'members.*.whatsapp' => ['required', 'string', 'max:30', 'regex:/^[0-9+\s()-]{8,30}$/'],
            'members.*.belt_level_id' => ['required', 'integer', \Illuminate\Validation\Rule::exists('belt_levels', 'id')->where('is_active', true)],
            'members.*.email' => ['nullable', 'email', 'max:255'],
            'members.*.city_id' => ['required', 'integer', 'exists:cities,id'],
            'members.*.district' => ['nullable', 'string', 'max:150'],
            'members.*.address' => ['nullable', 'string', 'max:2000'],
            'members.*.first_coach' => ['nullable', 'string', 'max:255'],
            'members.*.dojo_id' => ['nullable', 'integer', 'exists:dojos,id'],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
        ], [
            'members.*.whatsapp.regex' => 'Format WhatsApp tidak valid.',
        ]);

        $member_data = $validated['members'];

        $provinceId = $this->resolveProvinceId($admin, $role, (int) ($validated['province_id'] ?? 0));
        if ($provinceId <= 0) {
            return redirect()->route('admin.members.create')
                ->withErrors(['province_id' => 'Province belum di-set pada akun admin / dojo.']);
        }

        $this->validateMemberRegions($member_data, $provinceId);

        // IMPORTANT: fee map key biasanya INT, kita normalisasi biar aman di akses (int) key
        $beltFees = DB::table('fee_configurations')
            ->where('province_id', $provinceId)
            ->pluck('amount', 'belt_level_id')
            ->map(fn($v) => (int) $v)
            ->toArray();

        $beltIds = collect($member_data)->pluck('belt_level_id')->map(fn($v) => (int) $v)->unique()->values()->all();
        $beltMap = BeltLevel::whereIn('id', $beltIds)->get()->keyBy('id');
        $cityNames = City::where('province_id', $provinceId)->pluck('name', 'id');

        foreach ($member_data as $i => $item) {
            $member_data[$i]['city_name'] = $cityNames[$item['city_id']] ?? '-';
            $beltId = (int) $item['belt_level_id'];
            $belt = $beltMap->get($beltId);
            $unit = (int) ($beltFees[$beltId] ?? 0);

            if ($unit <= 0) {
                return redirect()->route('admin.members.create')
                    ->withErrors(['fee' => "Biaya iuran belum dikonfigurasi untuk sabuk yang dipilih (province_id={$provinceId})."]);
            }

            $member_data[$i]['belt_name'] = $belt
                ? strtoupper($belt->name) . ' (' . $belt->kyu_dan . ')'
                : 'N/A';

            $member_data[$i]['unit_fee'] = $unit;

            // pastikan field opsional ada biar gampang dibuat hidden input di review blade
            $member_data[$i]['email'] = $item['email'] ?? '';
            $member_data[$i]['parent_name'] = $item['parent_name'] ?? '';
            $member_data[$i]['address'] = $item['address'] ?? '';
        }

        $admin_fee = (int) config('services.membership.admin_fee', 0);
        $subtotal = array_sum(array_map(fn($m) => (int) ($m['unit_fee'] ?? 0), $member_data));
        $total = $subtotal + $admin_fee;

        // NOTE: $beltFees tidak wajib dipakai blade (bisa pakai unit_fee), tapi kita tetap kirim untuk debugging/tampilan jika perlu
        return view('admin.members.review', compact('member_data', 'beltFees', 'admin_fee', 'total', 'provinceId'));
    }

    /**
     * STORE: create member + payment (pending) + redirect ke DOKU
     * Ini dipanggil oleh tombol "Konfirmasi & Bayar Sekarang" di halaman review.
     */
    public function store(Request $request)
    {
        $admin = auth()->user();
        $role = strtolower((string) $admin->role);

        // guard: kalau akses store tanpa payload members, jangan balik ke previous (bisa lari ke create)
        if (!$request->has('members')) {
            return redirect()->route('admin.members.create')
                ->withErrors(['store' => 'Data member tidak ditemukan. Silakan ulangi dari form pendaftaran.']);
        }

        $validated = $request->validate([
            'members' => ['required', 'array', 'min:1'],
            'members.*.name' => ['required', 'string', 'max:255'],
            'members.*.parent_name' => ['nullable', 'string', 'max:255'],
            'members.*.whatsapp' => ['required', 'string', 'max:30', 'regex:/^[0-9+\s()-]{8,30}$/'],
            'members.*.belt_level_id' => ['required', 'integer', \Illuminate\Validation\Rule::exists('belt_levels', 'id')->where('is_active', true)],
            'members.*.email' => ['nullable', 'email', 'max:255'],
            'members.*.city_id' => ['required', 'integer', 'exists:cities,id'],
            'members.*.district' => ['nullable', 'string', 'max:150'],
            'members.*.address' => ['nullable', 'string', 'max:2000'],
            'members.*.first_coach' => ['nullable', 'string', 'max:255'],
            'members.*.dojo_id' => ['nullable', 'integer', 'exists:dojos,id'],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
        ], [
            'members.*.whatsapp.regex' => 'Format WhatsApp tidak valid.',
        ]);

        $provinceId = $this->resolveProvinceId($admin, $role, (int) ($validated['province_id'] ?? 0));
        if ($provinceId <= 0) {
            // FIX: jangan return back() (bisa balik ke create), arahkan ke create dengan pesan jelas
            return redirect()->route('admin.members.create')
                ->withErrors(['province_id' => 'Province belum di-set pada akun admin / dojo.']);
        }

        $this->validateMemberRegions($validated['members'], $provinceId);

        $feeMap = DB::table('fee_configurations')
            ->where('province_id', $provinceId)
            ->pluck('amount', 'belt_level_id')
            ->map(fn($v) => (int) $v)
            ->toArray();

        $adminFee = (int) config('services.membership.admin_fee', 0);

        $fullDojoName = $admin->dojo->name ?? 'DOJO';
        $firstWord = strtoupper(explode(' ', trim($fullDojoName))[0] ?? 'DOJO');
        $defaultPass = $firstWord . '123';

        try {
            $payment = DB::transaction(function () use ($validated, $admin, $provinceId, $feeMap, $adminFee, $defaultPass) {
                $total = 0;
                $paidForMembers = [];

                foreach ($validated['members'] as $idx => $data) {
                    $beltLevelId = (int) $data['belt_level_id'];

                    $unit = (int) ($feeMap[$beltLevelId] ?? 0);
                    if ($unit <= 0) {
                        throw new \RuntimeException("Biaya iuran belum di-set untuk belt_level_id={$beltLevelId} (province_id={$provinceId}).");
                    }

                    // Email unik fallback (hindari duplicate)
                    $email = !empty($data['email'])
                        ? $data['email']
                        : (preg_replace('/[^0-9]/', '', $data['whatsapp']) . '.' . strtolower(Str::random(4)) . '@perguruan.local');

                    // OPTIONAL: cegah WhatsApp double di database (kalau mau strict)
                    // if (User::where('whatsapp', $data['whatsapp'])->exists()) {
                    //     throw new \RuntimeException("WhatsApp {$data['whatsapp']} sudah terdaftar.");
                    // }

                    $member = User::create([
                        'name' => $data['name'],
                        'parent_name' => $data['parent_name'] ?? null,
                        'email' => $email,
                        'whatsapp' => $data['whatsapp'],
                        'password' => Hash::make($defaultPass),
                        'role' => 'member',
                        'positions' => ['member'], 'roles' => [],
                        'is_active' => false,
                        'province_id' => $provinceId,
                        'city_id' => $data['city_id'],
                        'district' => $data['district'] ?? null,
                        'address' => $data['address'] ?? null,
                        'dojo_id' => $data['dojo_id'] ?? $admin->dojo_id,
                        'first_coach' => $data['first_coach'] ?? null,
                        'belt_level_id' => $beltLevelId,
                    ]);

                    $total += $unit;

                    $paidForMembers[] = [
                        'user_id' => $member->id,
                        'name' => $member->name,
                        'whatsapp' => $member->whatsapp,
                        'belt_level_id' => $beltLevelId,
                        'amount' => $unit,
                    ];
                }

                $total += $adminFee;

                return Payment::create([
                    'user_id' => $admin->id,
                    'type' => 'iuran',
                    'reference' => 'IURAN:BULK:' . now()->format('Y-m') . ':PAYER:' . $admin->id,
                    'invoice_number' => 'COLL-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
                    'amount' => $total,
                    'status' => 'pending',
                    'expires_at' => now()->addMinutes((int) config('services.doku.expire_minutes', 60)),
                    'meta' => [
                        'kind' => 'collective_membership',
                        'province_id' => $provinceId,
                        'dojo_id' => $admin->dojo_id,
                        'admin_fee' => $adminFee,
                        'count' => count($paidForMembers),
                        'payer' => [
                            'id' => $admin->id,
                            'name' => $admin->name,
                            'role' => $admin->role ?? null,
                        ],
                    ],
                    'payload' => [
                        'paid_for_members' => $paidForMembers,
                    ],
                ]);
            });

            $checkout = $this->doku->createCheckout($payment, [
                'name' => $admin->name,
                'email' => $admin->email,
                'phone' => $admin->whatsapp ?? null,
            ]);

            if (empty($checkout['payment_url'])) {
                return redirect()->route('admin.dashboard')
                    ->withErrors(['payment' => 'Member tersimpan, tapi gagal membuat payment URL dari DOKU.']);
            }

            $payment->update(['payment_url' => $checkout['payment_url']]);

            if ($request->header('Sec-Fetch-Dest') === 'iframe' || $request->boolean('_form_modal')) {
                return view('admin.members.checkout-handoff', ['checkoutUrl' => $checkout['payment_url']]);
            }

            return redirect()->away($checkout['payment_url']);
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('admin.members.create')
                ->withErrors(['store' => 'ERROR DB: ' . $e->getMessage()]);
        }
    }

    /**
     * Province resolver:
     * - PB: boleh override dari form
     * - lainnya: pakai admin->province_id, fallback ke admin->dojo->province_id
     */
    protected function validateMemberRegions(array $members, int $provinceId): void
    {
        $cityIds = City::where('province_id', $provinceId)->pluck('id')->all();
        foreach ($members as $index => $member) {
            if (!empty($member['dojo_id']) && !\App\Models\Dojo::whereKey($member['dojo_id'])->where('city_id', $member['city_id'])->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(["members.$index.dojo_id" => 'Dojo harus sesuai kabupaten / kota anggota.']);
            }
            if (auth()->user()->hasRole('admin_dojo') && !auth()->user()->hasRole(['pb', 'pengprov', 'admin_pengprov', 'pengcab', 'admin_pengcab'])
                && !empty($member['dojo_id']) && (int) $member['dojo_id'] !== (int) auth()->user()->dojo_id) abort(403);
            if (!in_array((int) $member['city_id'], $cityIds, true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "members.$index.city_id" => 'Kabupaten / Kota harus sesuai dengan provinsi anggota.',
                ]);
            }
        }
    }

    protected function resolveProvinceId($admin, string $role, int $overrideProvinceId = 0): int
    {
        $provinceId = (int) ($admin->province_id ?? 0);

        if ($role === 'pb') {
            $provinceId = (int) ($overrideProvinceId ?: ($admin->province_id ?? 0));
        } else {
            if ($provinceId <= 0) {
                $provinceId = (int) ($admin->dojo->province_id ?? 0);
            }
        }

        return $provinceId;
    }
}
