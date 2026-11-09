<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessAudit;
use App\Support\PageSize;
use Illuminate\Http\Request;

class AccessAuditController extends Controller
{
    public function index(Request $request)
    {
        $audits = AccessAudit::query()->leftJoin('users as actors', 'actors.id', '=', 'access_audits.actor_id')
            ->leftJoin('users as targets', 'targets.id', '=', 'access_audits.target_id')
            ->select('access_audits.*', 'actors.name as actor_name', 'targets.name as target_name')->orderByDesc('access_audits.id');
        $audits = PageSize::paginate($audits, $request, 25);
        return view('admin.users.access-audits', compact('audits'));
    }
}
