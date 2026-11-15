<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{User, Province, City};
use App\Services\RoleAccess;
use App\Support\PageSize;
use Illuminate\Http\Request;

class OfficialVerificationController extends Controller
{
    public function index(Request $request)
    {
        $query = app(\App\Services\OfficialReview::class)->applications($request->user())->with(['province', 'city', 'official']);
        $status = $request->query('status') === 'appointed' ? 'appointed' : 'pending';
        $counts = ['pending' => (clone $query)->whereDoesntHave('official')->count(), 'appointed' => (clone $query)->whereHas('official')->count()];
        $status === 'appointed' ? $query->whereHas('official') : $query->whereDoesntHave('official');
        if ($request->filled('search')) {
            $term = '%'.trim($request->string('search')).'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term)->orWhere('whatsapp', 'like', $term));
        }
        $users = PageSize::paginate($query->latest('id'), $request, 10);
        return view('admin.official-verifications.index', compact('users', 'status', 'counts'));
    }

    public function show(Request $request, User $user)
    {
        abort_unless($user->registration_type === 'official', 404);
        if (!RoleAccess::managesUsers($request->user())) {
            abort_unless($request->user()->province_id && (int) $user->province_id === (int) $request->user()->province_id && !$user->hasRole(RoleAccess::NATIONAL), 403);
        }
        $user->load(['official', 'province', 'city']);
        return redirect()->route('admin.officials.create', ['user_id' => $user->id]);
    }
}
