<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\MemberCardData;

class MemberCardVerificationController extends Controller
{
    public function show(int $member)
    {
        $member = User::query()->members()->with(['province', 'city', 'dojo', 'beltLevel'])->findOrFail($member);

        return response()->view('member.verify-card', [
            'cardData' => MemberCardData::forUser($member),
            'verified' => $member->verification_status === 'approved',
        ])->header('Cache-Control', 'private, no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
