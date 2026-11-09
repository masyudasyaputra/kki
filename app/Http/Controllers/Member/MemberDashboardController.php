<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\ExamCertificate;
use App\Models\ExamParticipant;
use App\Support\PageSize;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MemberDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->load(['beltLevel', 'province', 'city', 'dojo']);
        $section = $request->route('section', 'overview');
        $latestVerification = $user->membershipVerifications()->latest('id')->first();
        $certificates = ExamCertificate::whereNotNull('sent_at')
            ->whereHas('participant', fn ($query) => $query->where('user_id', $user->id));
        $participants = ExamParticipant::where('user_id', $user->id);
        $payments = $user->payments()->getQuery();
        $summary = [
            'exams' => (clone $participants)->count(),
            'certificates' => (clone $certificates)->count(),
            'pending' => (clone $payments)->where('status', 'pending')->count(),
            'amount' => (clone $payments)->where('status', 'pending')->sum('amount'),
        ];
        $myCertificates = $section === 'certificates' ? PageSize::paginate($certificates->latest('sent_at'), $request) : collect();
        $myExams = $section === 'exams' ? PageSize::paginate($participants->with(['exam', 'targetBelt'])->latest('id'), $request) : collect();
        $myPayments = $section === 'payments' ? PageSize::paginate($payments->latest('id'), $request) : collect();
        $qrPngBase64 = $section === 'card'
            ? base64_encode(QrCode::format('svg')->size(200)->errorCorrection('H')->generate((string) ($user->member_number ?: $user->id)))
            : null;

        return view('member.dashboard', compact('user', 'section', 'latestVerification', 'summary', 'myCertificates', 'myExams', 'myPayments', 'qrPngBase64'));
    }
}
