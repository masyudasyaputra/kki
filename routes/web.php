<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\DojoController;
use App\Http\Controllers\Admin\OfficialController;
use App\Http\Controllers\Admin\ProvinceController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\FeeConfigurationController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\PublicDirectoryController;
use App\Http\Controllers\Admin\ExamScoreController;
use App\Http\Controllers\Admin\ExamExaminerController;
use App\Http\Controllers\Member\MemberDashboardController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;
use App\Models\City;
use App\Models\Dojo;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Http\Request;

// Web servers serve existing storage links directly; Laravel handles hosts without one.
Route::get('/storage/{path}', \App\Http\Controllers\PublicFileController::class)
    ->where('path', '.*')->name('public.storage');

Route::get('/', function () {
    if (in_array(request('auth'), ['login', 'register'], true)) session()->keep(['errors', '_old_input', 'status']);
    $dojos = Dojo::query()
        ->with(['city.province'])
        ->where('is_active', true)
        ->whereDate('sk_expiry_date', '>=', today())
        ->orderBy('name')
        ->get()
        ->sortBy(fn(Dojo $dojo) => $dojo->city?->name ?? '')
        ->values();

    $dojoRegions = $dojos->groupBy(fn(Dojo $dojo) => $dojo->city?->name ?? 'Wilayah belum ditentukan');

    $agendas = Exam::query()
        ->with('province')
        ->whereDate('execution_date', '>=', today())
        ->whereIn('status', ['open', 'ongoing'])
        ->orderBy('execution_date')
        ->limit(6)
        ->get();

    $verifiedMemberCount = User::query()
        ->members()
        ->where('verification_status', 'approved')
        ->count();

    return view('welcome', compact('dojos', 'dojoRegions', 'agendas', 'verifiedMemberCount'));
});

Route::get('/agenda-ukt', [PublicDirectoryController::class, 'agenda'])->name('public.agenda');
Route::get('/direktori-dojo', [PublicDirectoryController::class, 'dojos'])->name('public.dojos');
Route::get('/direktori-anggota', [PublicDirectoryController::class, 'members'])->name('public.members');

// ============================
// API DROPDOWN & VALIDASI
// ============================
Route::prefix('api')->group(function () {
    Route::get('/districts/{city}', function (City $city) {
        return $city->districts()->get(['id', 'name'])
            ->unique(fn ($district) => mb_strtolower(preg_replace('/\\s+/u', ' ', trim($district->name)), 'UTF-8'))
            ->values();
    });
    Route::get('/cities/{province_id}', function ($province_id) {
        return City::where('province_id', $province_id)->get();
    });

    Route::get('/dojos/{city_id}', function ($city_id) {
        return Dojo::where('city_id', $city_id)->get();
    });

    Route::get('/check-whatsapp', function (Request $request) {
        $exists = User::where('whatsapp', $request->query('number'))->exists();
        return response()->json(['exists' => $exists]);
    });
});

Route::get('/verifikasi-sertifikat/{certificate}', [\App\Http\Controllers\CertificateVerificationController::class, 'show'])
    ->middleware('signed')->name('certificates.verify');

// ============================
// DASHBOARD GATEWAY (SEMUA ROLE)
// - INI WAJIB: hanya auth, tanpa role:member
// ============================
Route::middleware(['auth'])->get('/dashboard', function () {
    return redirect()->to(\App\Support\DashboardDestination::url(auth()->user()));
})->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/panduan', \App\Http\Controllers\GuideController::class)->name('guide');
    Route::get('/profile/{member}/belt-diploma', [ProfileController::class, 'beltDiploma'])->name('profile.belt-diploma');
    Route::get('/kompetensi-wasit-juri', [\App\Http\Controllers\QualificationController::class, 'index'])->name('qualifications.index');
    Route::post('/kompetensi-wasit-juri', [\App\Http\Controllers\QualificationController::class, 'store'])->name('qualifications.store');
    Route::get('/kompetensi-wasit-juri/{qualification}/dokumen', [\App\Http\Controllers\QualificationController::class, 'document'])->name('qualifications.document');
});

// ============================
// DASHBOARD MEMBER (KHUSUS MEMBER)
// ============================
Route::middleware(['auth', 'role:member'])->group(function () {
    Route::get('/member/certificates/{certificate}', [\App\Http\Controllers\Admin\CertificateController::class, 'member'])->name('member.certificates.show');
    Route::get('/member/dashboard', [MemberDashboardController::class, 'index'])
        ->name('member.dashboard');
    foreach (['card', 'exams', 'payments', 'certificates'] as $section) {
        Route::get('/member/'.$section, [MemberDashboardController::class, 'index'])
            ->defaults('section', $section)->name('member.'.$section);
    }
});

// ============================
// AREA ADMIN & PENGURUS
// ============================
Route::middleware(['auth', 'verified', \App\Http\Middleware\AccessControl::class, \App\Http\Middleware\LockExamData::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::middleware('role:pb,super_admin,pengprov,admin_pengprov,pengcab,admin_pengcab')->group(function () {
            Route::get('/wasit-juri', [\App\Http\Controllers\Admin\QualificationReviewController::class, 'index'])->name('qualifications.index');
            Route::get('/wasit-juri/{qualification}/edit', [\App\Http\Controllers\Admin\QualificationReviewController::class, 'show'])->name('qualifications.show');
            Route::post('/wasit-juri/{qualification}', [\App\Http\Controllers\Admin\QualificationReviewController::class, 'store'])->name('qualifications.store');
        });
        Route::get('/sk-documents/{entity}/{id}', [\App\Http\Controllers\Admin\SkDocumentController::class, 'show'])->name('sk-documents.show');

        Route::middleware('role:super_admin')->group(function () {
            Route::get('/nomor-id/pengurus', [\App\Http\Controllers\Admin\OfficialNumberController::class, 'index'])->name('official-numbers.index');
            Route::get('/nomor-id/pengurus/{official}/edit', [\App\Http\Controllers\Admin\OfficialNumberController::class, 'edit'])->name('official-numbers.edit');
            Route::put('/nomor-id/pengurus/{official}', [\App\Http\Controllers\Admin\OfficialNumberController::class, 'update'])->name('official-numbers.update');
            Route::get('/nomor-id', [\App\Http\Controllers\Admin\MemberNumberController::class, 'index'])->name('member-numbers.index');
            Route::get('/nomor-id/{member}/edit', [\App\Http\Controllers\Admin\MemberNumberController::class, 'edit'])->name('member-numbers.edit');
            Route::put('/nomor-id/{member}', [\App\Http\Controllers\Admin\MemberNumberController::class, 'update'])->name('member-numbers.update');
        });

        Route::middleware('role:pb,super_admin,pengprov,admin_pengprov,pengcab,admin_pengcab,admin_dojo')->group(function () {
            Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
            Route::get('/finance/export', [FinanceController::class, 'export'])->name('finance.export');
        });

        Route::middleware('role:pb,super_admin,pengprov,admin_pengprov')->group(function () {
            Route::get('/official-verifications', [\App\Http\Controllers\Admin\OfficialVerificationController::class, 'index'])->name('official-verifications.index');
            Route::get('/official-verifications/{user}', [\App\Http\Controllers\Admin\OfficialVerificationController::class, 'show'])->name('official-verifications.show');
            Route::post('/users/{user}/appointment', [UserController::class, 'appoint'])->name('users.appoint');
            Route::resource('users', UserController::class)->except('show');
        });
        Route::middleware('role:pb,super_admin')->group(function () {
            Route::put('/exam-reviews/{exam}/reviewers', [\App\Http\Controllers\Admin\ExamReviewController::class, 'assign'])->name('exam-reviews.assign');
            Route::get('/access-audits', [\App\Http\Controllers\Admin\AccessAuditController::class, 'index'])->name('access-audits.index');
            Route::post('/belt-levels/restore-defaults', [\App\Http\Controllers\Admin\BeltLevelController::class, 'restoreDefaults'])->name('belt-levels.restore-defaults');
            Route::resource('belt-levels', \App\Http\Controllers\Admin\BeltLevelController::class)->except(['show', 'destroy']);
        });
        Route::get('/oversight', [\App\Http\Controllers\Admin\OversightController::class, 'index'])->middleware('role:pb,super_admin,dewan_guru_pusat')->name('oversight');
        Route::middleware('role:pb,super_admin,pengprov,admin_pengprov')->group(function () {
            Route::get('/regions', [\App\Http\Controllers\Admin\RegionController::class, 'index'])->name('regions.index');
            Route::post('/regions', [\App\Http\Controllers\Admin\RegionController::class, 'store'])->name('regions.store');
        });
        Route::middleware('role:pb,super_admin,dewan_guru_pusat,verifikator_ujian_pb,pengelola_sertifikat_pb,panitia_ujian,penguji,pengprov,admin_pengprov,pengcab,admin_pengcab')->group(function () {
            Route::get('/exam-reviews', [\App\Http\Controllers\Admin\ExamReviewController::class, 'index'])->name('exam-reviews.index');
            Route::get('/exam-reviews/{exam}', [\App\Http\Controllers\Admin\ExamReviewController::class, 'show'])->name('exam-reviews.show');
            Route::post('/exam-reviews/{exam}', [\App\Http\Controllers\Admin\ExamReviewController::class, 'store'])->middleware('role:pb,super_admin,verifikator_ujian_pb,pengprov,admin_pengprov,pengcab,admin_pengcab')->name('exam-reviews.store');
        });
        Route::middleware('role:super_admin,pengelola_sertifikat_pb,pengprov,admin_pengprov,pengcab,admin_pengcab')->group(function () {
            Route::get('/certificate-signers', [\App\Http\Controllers\Admin\CertificateSignerController::class, 'index'])->name('certificate-signers.index');
            Route::put('/certificate-signers/{template}', [\App\Http\Controllers\Admin\CertificateSignerController::class, 'update'])->name('certificate-signers.update');
            Route::get('/certificate-templates', [\App\Http\Controllers\Admin\CertificateTemplateController::class, 'index'])->name('certificate-templates.index');
            Route::post('/certificate-templates', [\App\Http\Controllers\Admin\CertificateTemplateController::class, 'template'])->name('certificate-templates.store');
            Route::delete('/certificate-templates/{template}', [\App\Http\Controllers\Admin\CertificateTemplateController::class, 'destroyTemplate'])->name('certificate-templates.destroy');
            Route::get('/certificate-templates/{template}/layout', [\App\Http\Controllers\Admin\CertificateTemplateController::class, 'layout'])->name('certificate-templates.layout');
            Route::post('/certificate-templates/{template}/layout', [\App\Http\Controllers\Admin\CertificateTemplateController::class, 'saveLayout'])->name('certificate-templates.layout.save');
        });

        Route::middleware('role:pb,super_admin,verifikator_anggota_pb,verifikator_pengprov,verifikator_pengcab')->group(function () {
            Route::get('/verifikasi-anggota', [\App\Http\Controllers\Admin\MembershipVerificationController::class, 'index'])->name('verifications.index');
            Route::get('/verifikasi-anggota/{member}', [\App\Http\Controllers\Admin\MembershipVerificationController::class, 'show'])->name('verifications.show');
            Route::post('/verifikasi-anggota/{member}', [\App\Http\Controllers\Admin\MembershipVerificationController::class, 'store'])->name('verifications.store');
        });

        /**
         * 1) AKSES SEMUA LEVEL (pb/pengprov/pengcab/admin_dojo/penguji)
         */
        Route::middleware(['role:pb,super_admin,dewan_guru_pusat,verifikator_ujian_pb,pengelola_sertifikat_pb,pengprov,pengcab,admin_dojo,penguji,panitia_ujian,admin_pengprov,admin_pengcab'])->group(function () {

            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

            // =========================
// MEMBERS
// PENTING: review harus sebelum resource
// - review hanya POST (mencegah ERR_TOO_MANY_REDIRECTS)
// - GET /members/review diarahkan ke form create
// =========================
            Route::post('/members/review', [MemberController::class, 'review'])
                ->name('members.review');

            Route::get('/members/review', function () {
                return redirect()->route('admin.members.create')
                    ->withErrors(['review' => 'Silakan isi form pendaftaran terlebih dahulu.']);
            });

            Route::get('/members/export', [MemberController::class, 'export'])->name('members.export');

            // Kalau MemberController kamu tidak punya show(), jangan aktifkan show
            Route::resource('members', MemberController::class)->except(['show']);

            // =========================
            // EXAMS (VIEW ONLY)
            // =========================
            Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
            Route::get('/exams/{exam}', [ExamController::class, 'show'])->name('exams.show');
            Route::get('/exams/{exam}/rekap-ukt', [ExamScoreController::class, 'roster'])->name('exams.roster');
            Route::get('/exams/{exam}/blanko-ukt/{participant}', [ExamScoreController::class, 'blanko'])->name('exams.blanko');
            Route::get('/exams/{exam}/certificates', [\App\Http\Controllers\Admin\CertificateController::class, 'index'])->name('exams.certificates.index');
            Route::post('/exams/{exam}/certificates/settings', [\App\Http\Controllers\Admin\CertificateController::class, 'settings'])->name('exams.certificates.settings');
            Route::post('/exams/{exam}/certificates/send-all', [\App\Http\Controllers\Admin\CertificateController::class, 'sendAll'])->name('exams.certificates.send-all');
            Route::post('/exams/{exam}/certificates/{participant}/send', [\App\Http\Controllers\Admin\CertificateController::class, 'send'])->name('exams.certificates.send');
            Route::post('/exams/{exam}/certificates/{participant}/identity', [\App\Http\Controllers\Admin\CertificateController::class, 'identity'])->name('exams.certificates.identity');
            Route::get('/exams/{exam}/certificates/{participant}/preview', [\App\Http\Controllers\Admin\CertificateController::class, 'preview'])->name('exams.certificates.preview');
            Route::post('/exams/{exam}/certificates/{participant}/issue', [\App\Http\Controllers\Admin\CertificateController::class, 'issue'])->name('exams.certificates.issue');
            Route::get('/exams/{exam}/certificates/{participant}/print', [\App\Http\Controllers\Admin\CertificateController::class, 'print'])->name('exams.certificates.print');

            Route::get('/exams/{exam}/scoring/data', [ExamScoreController::class, 'show'])->name('exams.scoring.show');
            Route::get('/exams/{exam}/scoring/{member}', [ExamController::class, 'showScoring'])
                ->name('exams.scoring.member');

            Route::post('/exams/{exam}/add-member', [ExamController::class, 'registerMember'])
                ->name('exams.register-member');

            Route::post('/exams/{exam}/bulk-payment', [ExamController::class, 'bulkPayment'])
                ->name('exams.bulk-payment');

            Route::delete('/exams/participants/{participant}', [ExamController::class, 'removeMember'])
                ->name('exams.remove-member');

            Route::delete('/exams/{exam}/bulk-remove', [ExamController::class, 'bulkRemoveMember'])
                ->name('exams.bulk-remove-member');

            // =========================
            // SCORING
            // =========================
            Route::get('/exams/{exam}/scoring', [ExamScoreController::class, 'index'])->name('exams.scoring');

            Route::post('/exams/{exam}/scoring', [ExamScoreController::class, 'store'])->name('exams.scoring.store');
            Route::post('/exams/{exam}/scoring/finalize', [ExamScoreController::class, 'finalize'])->name('exams.scoring.finalize');

            // =========================
            // PAYMENTS - ADMIN AREA
            // =========================
            Route::post('/payments/iuran/bulk', [PaymentController::class, 'createIuranBulk'])
                ->name('payments.iuran.bulk');

            Route::post('/payments/iuran', [PaymentController::class, 'createIuran'])
                ->name('payments.iuran.create');

            Route::post('/payments/ujian', [PaymentController::class, 'createUjian'])
                ->name('payments.ujian.create');
        });

        /**
         * 2) AKSES STRUKTURAL (PB, Pengprov, Pengcab)
         */
        Route::middleware(['role:pb,super_admin,pengprov,pengcab,admin_pengprov,admin_pengcab'])->group(function () {

            Route::resource('dojos', DojoController::class);
            Route::resource('officials', OfficialController::class);

            Route::resource('exams', ExamController::class)->except(['index', 'show']);

            Route::patch('/exams/{exam}/update-result', [ExamController::class, 'updateResult'])
                ->name('exams.update-result');

            Route::get('/exams/{exam}/examiners', [ExamExaminerController::class, 'edit'])
                ->name('exams.examiners.edit');

            Route::put('/exams/{exam}/examiners', [ExamExaminerController::class, 'update'])
                ->name('exams.examiners.update');
        });

        /**
         * 3) AKSES TINGGI (PB & Pengprov)
         */
        Route::middleware(['role:pb,super_admin,pengprov,admin_pengprov'])->group(function () {

            Route::post('/users/{user}/login-as', [\App\Http\Controllers\Admin\ImpersonationController::class, 'start'])->name('users.login-as');


            Route::resource('provinces', ProvinceController::class);

            Route::prefix('exams-fees')->name('exams.fees.')->group(function () {
                Route::get('/', [ExamController::class, 'feeIndex'])->name('index');
                Route::post('/', [ExamController::class, 'feeStore'])->name('store');
                Route::delete('/{id}', [ExamController::class, 'feeDestroy'])->name('destroy');
            });

            Route::resource('fees', FeeConfigurationController::class);
        });
    });

// ============================
// PROFILE UMUM
// ============================
Route::middleware('auth')->group(function () {
    Route::post('/impersonation/stop', [\App\Http\Controllers\Admin\ImpersonationController::class, 'stop'])->name('impersonation.stop');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

// ============================
// PAYMENTS (DOKU) - PUBLIC ENDPOINTS
// ============================
Route::prefix('payments')->name('payments.')->group(function () {
    Route::get('/doku/return', [PaymentController::class, 'dokuReturn'])->name('doku.return');
    Route::post('/doku/notify', [PaymentController::class, 'dokuNotify'])->name('doku.notify');
});
