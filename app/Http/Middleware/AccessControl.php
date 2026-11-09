<?php

namespace App\Http\Middleware;

use App\Services\RoleAccess;
use Closure;
use Illuminate\Http\Request;

class AccessControl
{
    public function handle(Request $request, Closure $next)
    {
        $actor = $request->user();
        if ($request->routeIs('admin.certificate-templates.*') ||
            ($request->routeIs('admin.exams.certificates.*') && !$request->routeIs('admin.exams.certificates.index', 'admin.exams.certificates.print'))) {
            abort_unless(RoleAccess::certificates($actor), 403, 'Penerbitan sertifikat hanya untuk Pengelola Sertifikat PB.');
        }
        if ($request->routeIs('admin.members.*', 'admin.dashboard', 'admin.payments.*')) {
            abort_unless($actor->hasRole(['pb', 'pengprov', 'admin_pengprov', 'pengcab', 'admin_pengcab', 'admin_dojo']), 403);
        }
        if ($request->routeIs('admin.exams.register-member', 'admin.exams.remove-member', 'admin.exams.bulk-remove-member', 'admin.exams.bulk-payment')) {
            abort_unless($actor->hasRole(['pb', 'pengprov', 'admin_pengprov', 'pengcab', 'admin_pengcab', 'admin_dojo', 'panitia_ujian']), 403);
        }
        return $next($request);
    }
}
