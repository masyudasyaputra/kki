<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserImpersonation as Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class UserImpersonation
{
    public function canStart(User $actor, User $target): bool
    {
        if (!$actor->is_active || (!$target->is_active && !$target->hasRole('member')) || $actor->id === $target->id) return false;
        if ($actor->hasRole('pb')) return true;
        if (!$actor->hasRole(['pengprov', 'admin_pengprov']) || !$actor->province_id) return false;
        if ($target->hasRole(['pb', 'super_admin', 'pengprov', 'admin_pengprov', 'dewan_guru_pusat', 'verifikator_anggota_pb', 'verifikator_ujian_pb', 'pengelola_sertifikat_pb'])) return false;
        return (int) $target->province_id === (int) $actor->province_id;
    }

    public function start(Request $request, User $target): void
    {
        abort_if($request->session()->has('impersonation'), 403, 'Kembali ke akun admin sebelum berpindah pengguna lagi.');
        $actor = $request->user();
        abort_unless($actor && $this->canStart($actor, $target), 403);
        $audit = Audit::create(['actor_id' => $actor->id, 'target_id' => $target->id, 'actor_name' => $actor->name, 'target_name' => $target->name, 'started_at' => now()]);
        $this->clearContext($request);
        $request->session()->put('impersonation', ['actor_id' => $actor->id, 'target_id' => $target->id, 'actor_name' => $actor->name, 'audit_id' => $audit->id]);
        // Never create a persistent login cookie for the impersonated account.
        Cookie::queue(Cookie::forget(Auth::guard('web')->getRecallerName()));
        Auth::guard('web')->login($target, false);
        $request->session()->regenerate();
        $request->session()->regenerateToken();
    }

    public function stop(Request $request): ?User
    {
        $state = $request->session()->get('impersonation');
        abort_unless(is_array($state), 403);
        $audit = Audit::whereKey($state['audit_id'] ?? 0)->where('target_id', $state['target_id'] ?? 0)->whereNull('ended_at')->first();
        abort_unless($audit && (int) $request->user()->id === (int) $state['target_id'], 403);
        $actor = User::find($state['actor_id']);
        abort_unless(($audit->actor_id && (int) $audit->actor_id === (int) $state['actor_id']) || (!$audit->actor_id && !$actor), 403);
        $canReturn = $actor && $actor->is_active && ($actor->hasRole('pb') || ($actor->province_id && $actor->hasRole(['pengprov', 'admin_pengprov'])));
        $this->finish($request, $canReturn ? 'returned' : 'actor_unavailable');
        $this->clearContext($request);
        if (!$canReturn) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return null;
        }
        Auth::guard('web')->login($actor, false);
        $request->session()->regenerate();
        $request->session()->regenerateToken();
        return $actor;
    }

    public function finish(Request $request, string $reason): void
    {
        $state = $request->session()->get('impersonation');
        if (!is_array($state)) return;
        Audit::whereKey($state['audit_id'] ?? 0)->where('target_id', $state['target_id'] ?? 0)->whereNull('ended_at')->update(['ended_at' => now(), 'end_reason' => $reason]);
        $request->session()->forget('impersonation');
    }

    private function clearContext(Request $request): void
    {
        $request->session()->forget(['auth.password_confirmed_at', 'password_hash_web', 'url.intended', 'google_signup', 'google_oauth', '_old_input', 'errors', 'success']);
    }
}
