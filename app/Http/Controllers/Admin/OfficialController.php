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
        $query = Official::with(['province', 'city', 'city.dojos', 'user']);

        if (!RoleAccess::nationalOfficials($user)) {
            $query->where('province_id', $user->province_id ?: 0);
            if (!$user->hasRole(['pengprov', 'admin_pengprov', 'verifikator_pengurus_provinsi'])) $query->where('city_id', $user->city_id ?: 0);
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
        $national = RoleAccess::nationalOfficials($request->user());
        $canAppoint = RoleAccess::reviewsOfficials($request->user());
        $candidates = collect();
        $candidate = null;
        if ($canAppoint) {
            $query = app(\App\Services\OfficialReview::class)->applications($request->user());
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
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'level' => ['required', \Illuminate\Validation\Rule::in(RoleAccess::officialLevels($request->user()))],
            'province_id' => 'exclude_if:level,pusat|required|exists:provinces,id',
            'city_id' => ['exclude_unless:level,pengcab', 'required', \Illuminate\Validation\Rule::exists('cities', 'id')->where('province_id', $request->province_id)],
            'position' => 'required|string',
            'phone_number' => 'nullable|string|max:20',
            'sk_number' => 'nullable|string|max:100',
            'sk_expiry_date' => 'required|date',
            'sk_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        app(\App\Services\OfficialReview::class)->authorizeRegion($request->user(), $validated);
        $validated['province_id'] = $validated['level'] === 'pusat' ? null : $validated['province_id'];
        $validated['city_id'] = $validated['level'] === 'pengcab' ? $validated['city_id'] : null;

        $validated['sk_document_path'] = $request->file('sk_document')?->store('sk-documents/officials', 'local');
        unset($validated['sk_document']);
        unset($validated['photo']);
        $photo = $request->file('photo')?->store('official-photos', 'public');
        if ($photo === false) throw \Illuminate\Validation\ValidationException::withMessages(['photo' => 'Foto belum dapat disimpan. Silakan coba lagi.']);
        if ($photo) $validated['photo_path'] = $photo;
        try {
            \Illuminate\Support\Facades\DB::transaction(fn () => Official::create($validated));
        } catch (\Throwable $error) {
            if ($photo) Storage::disk('public')->delete($photo);
            if ($validated['sk_document_path']) Storage::disk('local')->delete($validated['sk_document_path']);
            throw $error;
        }

        return redirect()->route('admin.officials.index')
            ->with('success', 'Data Pengurus berhasil ditambahkan!');
    }

    /**
     * INI FUNGSI YANG HILANG (Penyebab Tombol Edit Error)
     */
    public function edit(Official $official)
    {
        $this->authorizeOfficial($official);
        if ($official->user_id) {
            return redirect()->route('admin.officials.create', ['user_id' => $official->user_id]);
        }
        $provinces = Province::when(!RoleAccess::nationalOfficials(auth()->user()), fn ($q) => $q->whereKey(auth()->user()->province_id))->orderBy('name')->get();
        // Ambil data kota berdasarkan provinsi yang sedang dipilih si pengurus
        $cities = City::where('province_id', $official->province_id)->get();

        return view('admin.officials.edit', compact('official', 'provinces', 'cities'));
    }

    /**
     * FUNGSI UNTUK MENYIMPAN PERUBAHAN
     */
    public function update(Request $request, Official $official)
    {
        $this->authorizeOfficial($official);
        if ($official->user_id) {
            return app(UserController::class)->appoint($request, $official->user);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'level' => ['required', \Illuminate\Validation\Rule::in(RoleAccess::officialLevels($request->user()))],
            'province_id' => 'exclude_if:level,pusat|required|exists:provinces,id',
            'city_id' => ['exclude_unless:level,pengcab', 'required', \Illuminate\Validation\Rule::exists('cities', 'id')->where('province_id', $request->province_id)],
            'position' => 'required|string',
            'phone_number' => 'nullable|string|max:20',
            'sk_number' => 'nullable|string|max:100',
            'sk_expiry_date' => 'required|date',
            'sk_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        app(\App\Services\OfficialReview::class)->authorizeRegion($request->user(), $validated);
        $validated['province_id'] = $validated['level'] === 'pusat' ? null : $validated['province_id'];
        $validated['city_id'] = $validated['level'] === 'pengcab' ? $validated['city_id'] : null;

        $oldDocument = $official->sk_document_path;
        if ($request->hasFile('sk_document')) {
            $validated['sk_document_path'] = $request->file('sk_document')->store('sk-documents/officials', 'local');
        }
        unset($validated['sk_document']);
        unset($validated['photo']);
        $oldPhoto = $official->photo_path;
        $photo = $request->file('photo')?->store('official-photos', 'public');
        if ($photo === false) throw \Illuminate\Validation\ValidationException::withMessages(['photo' => 'Foto belum dapat disimpan. Silakan coba lagi.']);
        if ($photo) $validated['photo_path'] = $photo;
        try {
            \Illuminate\Support\Facades\DB::transaction(fn () => $official->update($validated));
        } catch (\Throwable $error) {
            if ($photo) Storage::disk('public')->delete($photo);
            if (!empty($validated['sk_document_path'])) Storage::disk('local')->delete($validated['sk_document_path']);
            throw $error;
        }
        if ($photo && $oldPhoto && str_starts_with($oldPhoto, 'official-photos/')) Storage::disk('public')->delete($oldPhoto);
        if (!empty($validated['sk_document_path']) && $oldDocument) Storage::disk('local')->delete($oldDocument);

        return redirect()->route('admin.officials.index')
            ->with('success', 'Data Pengurus berhasil diperbarui!');
    }

    /**
     * FUNGSI HAPUS
     */
    public function destroy(Official $official)
    {
        $this->authorizeOfficial($official);
        abort_unless(auth()->user()->hasRole(['pb', 'super_admin', 'pengprov', 'admin_pengprov', 'pengcab', 'admin_pengcab']), 403);
        $photo = $official->photo_path;
        $official->delete();
        if ($photo && str_starts_with($photo, 'official-photos/')) Storage::disk('public')->delete($photo);
        return redirect()->route('admin.officials.index')
            ->with('success', 'Data Pengurus berhasil dihapus!');
    }
    private function authorizeOfficial(Official $official): void
    {
        $actor = auth()->user();
        if (RoleAccess::nationalOfficials($actor)) return;
        abort_unless($actor->province_id && (int) $actor->province_id === (int) $official->province_id, 403);
        if (!$actor->hasRole(['pengprov', 'admin_pengprov', 'verifikator_pengurus_provinsi'])) {
            abort_unless($actor->city_id && (int) $actor->city_id === (int) $official->city_id, 403);
        }
    }

}
