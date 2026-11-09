<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Exam, User};
use Illuminate\Http\Request;

class OversightController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $memberCount = User::where('role', 'member')->count();
        $activeMemberCount = User::where('role', 'member')->where('is_active', true)->count();
        $pendingCount = Exam::where('review_status', 'submitted')->count();
        $examCount = Exam::count();
        $examQuery = Exam::with('province')->withCount('participants')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . trim($request->string('search')->toString()) . '%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)
                    ->orWhereHas('province', fn ($province) => $province->where('name', 'like', $term)));
            })
            ->orderByRaw('execution_date IS NULL')
            ->orderByDesc('execution_date');
        $exams = \App\Support\PageSize::paginate($examQuery, $request, 8);

        return view('admin.exams.oversight', compact('memberCount', 'activeMemberCount', 'pendingCount', 'examCount', 'exams'));
    }
}
