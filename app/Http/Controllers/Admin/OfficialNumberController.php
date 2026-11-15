<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Official, AccessAudit};
use App\Services\OfficialNumbers;
use App\Support\PageSize;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OfficialNumberController extends Controller
{
    public function index(Request $request)
    {
        $query = Official::with('province');
        if ($request->filled('search')) {
            $term = '%'.trim($request->string('search')).'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('official_number', 'like', $term));
        }
        $officials = PageSize::paginate($query->orderBy('id'), $request, 25);
        return view('admin.member-numbers.officials', compact('officials'));
    }

    public function edit(Official $official, OfficialNumbers $numbers)
    {
        $automatic = $numbers->automatic($official);
        $changes = AccessAudit::where('event', 'official.number.updated')->where('after->official_id', $official->id)->latest('id')->paginate(10);
        return view('admin.member-numbers.official-edit', compact('official', 'automatic', 'changes'));
    }

    public function update(Request $request, Official $official, OfficialNumbers $numbers)
    {
        abort_if($request->session()->has('impersonation'), 403);
        if (is_string($request->input('number'))) $request->merge(['number' => strtoupper(trim($request->input('number')))]);
        $data = $request->validate([
            'mode' => ['required', Rule::in(['auto', 'manual'])],
            'number' => ['nullable', 'required_if:mode,manual', 'string', 'max:40', 'regex:/^PGR-[A-Z0-9][A-Z0-9-]{0,35}$/D'],
            'expected_number' => ['required', 'string', 'max:40'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);
        $numbers->change($official, $request->user(), $data);
        return redirect()->route('admin.official-numbers.edit', $official)->with('success', 'Nomor ID pengurus diperbarui.');
    }
}
