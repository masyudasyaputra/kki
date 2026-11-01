<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserImpersonation;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function start(Request $request, User $user, UserImpersonation $service)
    {
        $service->start($request, $user);
        return redirect()->route('dashboard');
    }

    public function stop(Request $request, UserImpersonation $service)
    {
        $actor = $service->stop($request);
        return $actor ? redirect()->route('admin.users.index')->with('success', 'Anda sudah kembali ke akun admin.')
            : redirect()->route('login')->with('status', 'Akun admin asal tidak tersedia. Silakan masuk kembali.');
    }
}
