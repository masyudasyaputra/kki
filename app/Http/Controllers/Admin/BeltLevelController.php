<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BeltLevel;
use App\Services\BeltCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BeltLevelController extends Controller
{
    public function index()
    {
        $beltLevels = BeltLevel::orderBy('order')->orderBy('kyu_dan')->get();

        return view('admin.belt-levels.index', [
            'beltLevels' => $beltLevels,
            'kyuCount' => $beltLevels->filter(fn ($belt) => str_starts_with(strtolower((string) $belt->kyu_dan), 'kyu'))->count(),
            'danCount' => $beltLevels->filter(fn ($belt) => str_starts_with(strtolower((string) $belt->kyu_dan), 'dan'))->count(),
        ]);
    }

    public function create()
    {
        return view('admin.belt-levels.create', [
            'beltLevel' => new BeltLevel(['order' => ((int) BeltLevel::max('order')) + 1, 'is_active' => true]),
        ]);
    }

    public function store(Request $request)
    {
        BeltLevel::create($this->validated($request));

        return redirect()->route('admin.belt-levels.index')->with('success', 'Tingkatan sabuk berhasil ditambahkan.');
    }

    public function edit(BeltLevel $beltLevel)
    {
        return view('admin.belt-levels.edit', compact('beltLevel'));
    }

    public function update(Request $request, BeltLevel $beltLevel)
    {
        $beltLevel->update($this->validated($request, $beltLevel));

        return redirect()->route('admin.belt-levels.index')->with('success', 'Tingkatan sabuk berhasil diperbarui.');
    }

    public function restoreDefaults()
    {
        if (BeltLevel::exists()) {
            return back()->with('error', 'Katalog bawaan hanya dapat dimuat saat daftar tingkatan masih kosong.');
        }

        DB::transaction(function () {
            foreach (BeltCatalog::defaults() as $attributes) {
                BeltLevel::create($attributes + ['is_active' => true]);
            }
        });

        return redirect()->route('admin.belt-levels.index')->with('success', 'Katalog bawaan 7 Kyu dan 6 DAN berhasil dimuat.');
    }

    private function validated(Request $request, ?BeltLevel $beltLevel = null): array
    {
        $id = $beltLevel?->id;

        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'kyu_dan' => [
                'required', 'string', 'max:20',
                'regex:/^(Kyu\s+\d+|DAN\s+[IVXLCDM]+)$/i',
                $beltLevel ? Rule::unique('belt_levels', 'kyu_dan')->ignore($id) : Rule::unique('belt_levels', 'kyu_dan'),
            ],
            'order' => ['required', 'integer', 'min:1', 'max:999'],
            'membership_fee' => ['required', 'integer', 'min:0', 'max:999999999'],
            'is_active' => ['required', 'boolean'],
        ], [
            'kyu_dan.regex' => 'Format tingkatan harus seperti Kyu 7 atau DAN I.',
            'kyu_dan.unique' => 'Tingkatan Kyu/DAN tersebut sudah terdaftar.',
        ]);
    }
}
