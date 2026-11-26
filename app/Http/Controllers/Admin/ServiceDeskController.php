<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{User, Official, Dojo, Province, City, Exam, ExamCertificate, AccessAudit};
use App\Services\RoleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Artisan, DB, Schema};

class ServiceDeskController extends Controller
{
    public function index(Request $request)
    {
        $actor = $request->user();
        abort_unless(RoleAccess::serviceDesk($actor), 403);
        $actions = [];
        if ($actor->hasRole(['super_admin', 'pb', 'koordinator_nasional_sim_kki'])) {
            $actions[] = ['Pemantauan nasional', 'Ringkasan anggota dan kegiatan ujian seluruh wilayah.', 'admin.oversight'];
            $actions[] = ['Riwayat audit', 'Telusuri perubahan akun, penugasan, dan kegiatan pengelolaan data.', 'admin.access-audits.index'];
        }
        if (RoleAccess::managesUsers($actor)) {
            $actions[] = ['Manajemen akun & kewenangan', 'Atur akun, role, hak akses, dan status pengguna.', 'admin.users.index'];
            $actions[] = ['Penetapan pengurus', 'Periksa usulan pengurus dan tetapkan wilayah serta SK.', 'admin.official-verifications.index'];
        }
        if ($actor->hasRole(array_keys(\App\Services\MembershipReview::ROLES))) {
            $actions[] = ['Verifikasi data anggota / DAN', 'Periksa data, bukti ijazah, dan simpan keputusan sesuai kewenangan.', 'admin.verifications.index'];
        }
        if (RoleAccess::certificates($actor)) {
            $actions[] = ['Administrasi sertifikat', 'Penerbitan, nomor custom, revisi, dan pengiriman sertifikat per sesi ujian.', 'admin.exams.index'];
            $actions[] = ['Template e-sertifikat', 'Kelola desain PDF dan koordinat data pada sertifikat.', 'admin.certificate-templates.index'];
            $actions[] = ['Penandatangan', 'Atur penandatangan dan QR verifikasi sertifikat.', 'admin.certificate-signers.index'];
        }
        if (RoleAccess::archives($actor)) $actions[] = ['Arsip dokumen SK', 'Cari dan baca SK pengurus, provinsi, dojo, dan ujian.', 'admin.services.archives'];
        if (RoleAccess::managesRegionData($actor)) $actions[] = ['Master wilayah', 'Kelola kabupaten/kota serta kecamatan sesuai induk wilayah.', 'admin.regions.index'];

        $quality = $actor->hasRole(['super_admin', 'pb', 'koordinator_nasional_sim_kki', 'adaptasi_database_digitalisasi']);
        $metrics = [];
        if ($quality) {
            foreach ([
                ['Anggota tanpa foto', User::members(), 'photo'],
                ['Anggota tanpa dojo', User::members(), 'dojo_id'],
                ['Pengurus tanpa dokumen SK', Official::query(), 'sk_document_path'],
                ['Dojo tanpa dokumen SK', Dojo::query(), 'sk_document_path'],
                ['Kabupaten/kota tanpa kode', City::query(), 'code'],
            ] as [$label, $query, $column]) {
                $metrics[$label] = Schema::hasColumn($query->getModel()->getTable(), $column)
                    ? $query->where(fn ($q) => $q->whereNull($column)->orWhere($column, ''))->count() : null;
            }
            if (Schema::hasColumn('users', 'verification_status')) $metrics['Pengajuan anggota menunggu verifikasi'] = User::members()->where('verification_status', 'pending')->count();
            if (Schema::hasColumn('exams', 'review_status')) $metrics['Hasil ujian menunggu pengesahan'] = Exam::where('review_status', 'submitted')->count();
            if (Schema::hasColumn('exam_certificates', 'sent_at')) $metrics['Sertifikat belum dikirim'] = ExamCertificate::whereNull('sent_at')->count();
        }
        return response()->view('admin.services.index', compact('actions', 'metrics', 'actor'))->header('Cache-Control', 'private, no-store');
    }

    public function archives(Request $request)
    {
        abort_unless(RoleAccess::archives($request->user()), 403);
        $filters = $request->validate(['type' => 'nullable|in:official,province,dojo,exam', 'search' => 'nullable|string|max:150']);
        $type = $filters['type'] ?? 'official';
        $models = ['official' => Official::class, 'province' => Province::class, 'dojo' => Dojo::class, 'exam' => Exam::class];
        $column = $type === 'exam' ? 'certificate_settings->decision_document_path' : 'sk_document_path';
        $query = $models[$type]::query()->whereNotNull($column)->where($column, '!=', '');
        if (!empty($filters['search'])) $query->where('name', 'like', '%'.$filters['search'].'%');
        $records = $query->orderBy('name')->paginate(20)->withQueryString();
        return response()->view('admin.services.archives', compact('records', 'type', 'filters'))->header('Cache-Control', 'private, no-store');
    }

    public function exportRegions(Request $request)
    {
        abort_unless(RoleAccess::managesRegionData($request->user()), 403);
        AccessAudit::record('regions.exported', $request->user(), null, [], ['cities' => City::count()]);
        return response()->streamDownload(function () {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['ID Kota', 'Kode Provinsi', 'Provinsi', 'Kode Kemendagri Kab/Kota', 'Kabupaten/Kota'], ',', '"', '');
            City::with('province')->orderBy('id')->chunkById(200, function ($cities) use ($output) {
                foreach ($cities as $city) {
                    $row = [$city->id, $city->province?->code, $city->province?->name, $city->code, $city->name];
                    $row = array_map(fn ($value) => preg_match('/^[\s]*[=+@-]/u', (string) $value) ? "'".$value : $value, $row);
                    fputcsv($output, $row, ',', '"', '');
                }
            });
            fclose($output);
        }, 'master-wilayah-kki.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    public function syncRegions(Request $request)
    {
        abort_unless(RoleAccess::managesRegionData($request->user()), 403);
        abort_if($request->session()->has('impersonation'), 403);
        DB::transaction(function () use ($request) {
            $before = City::whereNull('code')->count();
            $result = Artisan::call('db:seed', ['--class' => \Database\Seeders\ExistingRegionCodeSeeder::class, '--force' => true]);
            if ($result !== 0) throw \Illuminate\Validation\ValidationException::withMessages(['sync' => 'Sinkronisasi kode wilayah gagal. Perubahan dibatalkan.']);
            AccessAudit::record('regions.codes.synced', $request->user(), null, ['missing_city_codes' => $before], ['missing_city_codes' => City::whereNull('code')->count()]);
        });
        return redirect()->route('admin.services.index')->with('status', 'Sinkronisasi selesai. Kode diisi hanya untuk nama dan provinsi yang cocok; data yang konflik tetap perlu ditinjau.');
    }
}
