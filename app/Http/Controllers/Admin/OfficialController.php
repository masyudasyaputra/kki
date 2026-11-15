<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Official;
use App\Models\Province;
use App\Models\City; // Tambahkan ini agar bisa pilih kota saat edit
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Services\RoleAccess;

class OfficialController extends Controller
{
    public function index(Request $request) // Tambahkan Request agar pencarian tetap jalan
    {
        $user = auth()->user();

        // 1. Inisialisasi query dengan eager loading yang kamu butuhkan
        $query = Official::with(['province', 'city', 'city.dojos']);

        // 2. PROTEKSI WILAYAH: Hanya tampilkan data di bawah Pengprov yang sedang login
        if ($user->role === 'pengprov') {
            $query->where('province_id', $user->province_id);
        }
        // Jika nanti ada role pengcab, ini proteksi tambahannya (opsional)
        elseif ($user->role === 'pengcab') {
            $query->where('city_id', $user->city_id);
        }

        // 3. FITUR PENCARIAN: Jangan sampai hilang agar user tidak bingung
        if ($request->has('search') && $request->search != '') {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // 4. Eksekusi query (Gunakan paginate jika data mulai banyak, atau get() sesuai kode kamu)
        $officials = $query->latest()->get();

        return view('admin.officials.index', compact('officials'));
    }

    public function create(Request $request)
    {
        $national = RoleAccess::managesUsers($request->user());
        $canAppoint = $request->user()->hasRole(['pb', 'super_admin', 'pengprov', 'admin_pengprov']);
        $candidates = collect();
        $candidate = null;
        if ($canAppoint) {
            $query = User::where('registration_type', 'official');
            if (!$national) {
                $query->where('province_id', $request->user()->province_id ?: 0);
            }
            foreach (RoleAccess::NATIONAL as $role) {
                $query->where('role', '!=', $role)
                    ->where(fn ($q) => $q->whereNull('roles')->orWhereJsonDoesntContain('roles', $role))
                    ->where(fn ($q) => $q->whereNull('positions')->orWhereJsonDoesntContain('positions', $role));
            }
            if ($request->filled('user_id')) {
                $candidate = (clone $query)->with('official')->findOrFail($request->integer('user_id'));
            }
            $candidates = $query->whereDoesntHave('official')->orderBy('name')->get();
        } else {
            abort_if($request->filled('user_id'), 403);
        }
        $provinces = Province::when(!$national, fn ($q) => $q->whereKey($request->user()->province_id))->orderBy('name')->get();
        return view('admin.officials.create', compact('provinces', 'candidates', 'candidate', 'canAppoint', 'national'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'required|in:provinsi,pengcab',
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required_if:level,pengcab|nullable|exists:cities,id',
            'position' => 'required|string',
            'phone_number' => 'nullable|string|max:20',
            'sk_number' => 'nullable|string|max:100',
            'sk_expiry_date' => 'required|date',
            'sk_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $validated['sk_document_path'] = $request->file('sk_document')?->store('sk-documents/officials', 'local');
        unset($validated['sk_document']);
        Official::create($validated);

        return redirect()->route('admin.officials.index')
            ->with('success', 'Data Pengurus berhasil ditambahkan!');
    }

    /**
     * INI FUNGSI YANG HILANG (Penyebab Tombol Edit Error)
     */
    public function edit(Official $official)
    {
        if ($official->user_id) {
            return redirect()->route('admin.users.edit', $official->user_id);
        }
        $provinces = Province::orderBy('name')->get();
        // Ambil data kota berdasarkan provinsi yang sedang dipilih si pengurus
        $cities = City::where('province_id', $official->province_id)->get();

        return view('admin.officials.edit', compact('official', 'provinces', 'cities'));
    }

    /**
     * FUNGSI UNTUK MENYIMPAN PERUBAHAN
     */
    public function update(Request $request, Official $official)
    {
        if ($official->user_id) {
            return redirect()->route('admin.users.edit', $official->user_id)
                ->with('success', 'Perbarui pengurus melalui formulir penetapan agar data akun dan wilayah tersimpan bersama.');
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'required|in:provinsi,pengcab',
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required_if:level,pengcab|nullable|exists:cities,id',
            'position' => 'required|string',
            'phone_number' => 'nullable|string|max:20',
            'sk_number' => 'nullable|string|max:100',
            'sk_expiry_date' => 'required|date',
            'sk_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $oldDocument = $official->sk_document_path;
        if ($request->hasFile('sk_document')) {
            $validated['sk_document_path'] = $request->file('sk_document')->store('sk-documents/officials', 'local');
        }
        unset($validated['sk_document']);
        $official->update($validated);
        if (!empty($validated['sk_document_path']) && $oldDocument) Storage::disk('local')->delete($oldDocument);

        return redirect()->route('admin.officials.index')
            ->with('success', 'Data Pengurus berhasil diperbarui!');
    }

    /**
     * FUNGSI HAPUS
     */
    public function destroy(Official $official)
    {
        $official->delete();
        return redirect()->route('admin.officials.index')
            ->with('success', 'Data Pengurus berhasil dihapus!');
    }
}
