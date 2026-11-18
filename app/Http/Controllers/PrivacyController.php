<?php

namespace App\Http\Controllers;

use App\Models\PrivacyRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PrivacyController extends Controller
{
    public const VERSION = '2026-11-18';

    public function index(Request $request)
    {
        $requests = PrivacyRequest::where('user_id', $request->user()->id)->latest('id')->paginate(10);
        return response()->view('privacy.center', compact('requests'))->header('Cache-Control', 'private, no-store');
    }

    public function consent(Request $request)
    {
        abort_if($request->session()->has('impersonation'), 403);
        $data = $request->validate(['directory_consent' => ['required', 'boolean']]);
        DB::transaction(function () use ($request, $data) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $consent = (bool) $data['directory_consent'];
            if ((bool) $user->public_directory_consent === $consent) return;
            $user->forceFill(['public_directory_consent' => $consent])->save();
            DB::table('privacy_events')->insert(['user_id' => $user->id, 'directory_consent' => $consent,
                'policy_version' => self::VERSION, 'created_at' => now()]);
        });
        return back()->with('status', 'Pilihan publikasi direktori disimpan.');
    }

    public function store(Request $request)
    {
        abort_if($request->session()->has('impersonation'), 403);
        $data = $request->validate(['kind' => ['required', Rule::in(array_keys(PrivacyRequest::KINDS))],
            'details' => ['required', 'string', 'min:10', 'max:3000']]);
        PrivacyRequest::create($data + ['user_id' => $request->user()->id, 'status' => 'pending']);
        return back()->with('status', 'Permintaan tercatat. Pantau jawaban pengurus di riwayat permintaan.');
    }

    public function admin(Request $request)
    {
        abort_unless($request->user()->hasRole('super_admin'), 403);
        $requests = PrivacyRequest::with('user:id,name')->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'processing' THEN 1 ELSE 2 END")->orderBy('id')->paginate(15);
        return response()->view('privacy.admin', compact('requests'))->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, PrivacyRequest $privacyRequest)
    {
        abort_unless($request->user()->hasRole('super_admin'), 403);
        abort_if($request->session()->has('impersonation'), 403);
        $data = $request->validate(['status' => ['required', Rule::in(['processing', 'completed', 'rejected'])],
            'response' => ['required', 'string', 'min:10', 'max:3000']]);
        DB::transaction(function () use ($request, $privacyRequest, $data) {
            $locked = PrivacyRequest::whereKey($privacyRequest->id)->lockForUpdate()->firstOrFail();
            abort_if(in_array($locked->status, ['completed', 'rejected'], true), 409, 'Permintaan sudah ditutup.');
            $locked->update($data + ['reviewer_id' => $request->user()->id, 'responded_at' => now()]);
        });
        return back()->with('status', 'Tindak lanjut tersimpan.');
    }
}
