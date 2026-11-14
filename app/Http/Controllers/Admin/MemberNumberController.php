<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{MemberNumberChange, Province, User};
use App\Support\PageSize;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberNumberController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'mode' => ['nullable', Rule::in(['auto', 'manual'])],
        ]);
        $query = User::where('role', 'member')->with(['province', 'beltLevel']);
        $total = (clone $query)->count();
        $manual = (clone $query)->where('member_number_manual', true)->count();
        if ($search = trim($filters['search'] ?? '')) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')->orWhere(fn ($q) => $q->whereMemberNumber($search)));
        }
        if (!empty($filters['province_id'])) $query->where('province_id', $filters['province_id']);
        if (($filters['mode'] ?? '') === 'manual') $query->where('member_number_manual', true);
        if (($filters['mode'] ?? '') === 'auto') $query->where('member_number_manual', false);
        $members = PageSize::paginate($query->orderBy('id'), $request, 25);
        $provinces = Province::orderBy('name')->get();
        return view('admin.member-numbers.index', compact('members', 'provinces', 'total', 'manual'));
    }

    public function edit(User $member)
    {
        abort_unless($member->role === 'member', 404);
        $member->load(['province', 'beltLevel']);
        $changes = MemberNumberChange::where('member_id', $member->id)->with('actor')->latest('id')->paginate(10);
        return view('admin.member-numbers.edit', compact('member', 'changes'));
    }

    public function update(Request $request, User $member, \App\Services\MemberNumbers $numbers)
    {
        abort_unless($member->role === 'member', 404);
        abort_if($request->session()->has('impersonation'), 403);
        foreach (['number', 'target_number', 'reason'] as $key) {
            if (is_string($request->input($key))) $request->merge([$key => $key === 'reason' ? trim($request->input($key)) : strtoupper(trim($request->input($key)))]);
        }
        $data = $request->validate([
            'mode' => ['required', Rule::in(['auto', 'manual', 'swap'])],
            'number' => ['required_if:mode,manual', 'nullable', 'string', 'max:40', 'regex:/^[A-Z0-9][A-Z0-9-]{2,39}$/D'],
            'target_number' => ['required_if:mode,swap', 'nullable', 'string', 'max:40'],
            'reason' => ['required', 'string', 'max:2000'],
            'expected_number' => ['required', 'string', 'max:40'],
        ]);
        if ($data['mode'] === 'swap') $numbers->swap($member, $request->user(), $data);
        else $numbers->change($member, $request->user(), $data);
        return redirect()->route('admin.member-numbers.edit', ['member' => $member, '_form_modal' => $request->boolean('_form_modal') ? 1 : null])->with('success', 'Nomor ID berhasil diperbarui dan riwayat perubahan dicatat.');
    }
}
