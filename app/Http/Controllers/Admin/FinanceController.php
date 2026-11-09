<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PageSize;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        $actor = $request->user();
        $this->authorizeAccess($actor);
        $filters = $this->filters($request);
        [$from, $to] = $this->range($filters);

        $periodLedger = $this->scopedLedger($actor)->whereBetween('occurred_at', [$from, $to]);
        $summaryRows = (clone $periodLedger)->selectRaw("source, SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_total, SUM(CASE WHEN status IN ('pending','unpaid') THEN amount ELSE 0 END) as due_total, SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count, SUM(CASE WHEN status IN ('pending','unpaid') THEN 1 ELSE 0 END) as due_count, SUM(CASE WHEN status IN ('failed','expired','canceled') THEN 1 ELSE 0 END) as failed_count")
            ->groupBy('source')->get()->keyBy('source');
        $summary = [];
        foreach (['iuran', 'ujian'] as $source) {
            $row = $summaryRows->get($source);
            $summary[$source] = [
                'paid' => (float) ($row->paid_total ?? 0), 'due' => (float) ($row->due_total ?? 0),
                'paid_count' => (int) ($row->paid_count ?? 0), 'due_count' => (int) ($row->due_count ?? 0),
                'failed_count' => (int) ($row->failed_count ?? 0),
            ];
        }
        $summary['total'] = $summary['iuran']['paid'] + $summary['ujian']['paid'];
        $summary['due'] = $summary['iuran']['due'] + $summary['ujian']['due'];
        $summary['paid_count'] = $summary['iuran']['paid_count'] + $summary['ujian']['paid_count'];
        $summary['due_count'] = $summary['iuran']['due_count'] + $summary['ujian']['due_count'];

        $ledger = $this->applyTableFilters(clone $periodLedger, $filters);
        $ledger->orderByDesc('occurred_at')->orderByDesc('record_id')->orderBy('source');
        $transactions = PageSize::paginate($ledger, $request, 15);
        $trend = $this->monthlyTrend($actor);

        return view('admin.finance.index', compact('filters', 'from', 'to', 'summary', 'transactions', 'trend'));
    }

    public function export(Request $request)
    {
        $actor = $request->user();
        $this->authorizeAccess($actor);
        $filters = $this->filters($request);
        [$from, $to] = $this->range($filters);
        $rows = $this->applyTableFilters($this->scopedLedger($actor)->whereBetween('occurred_at', [$from, $to]), $filters)
            ->orderByDesc('occurred_at')->orderByDesc('record_id')->orderBy('source');

        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Tanggal', 'Referensi', 'Jenis', 'Nama', 'Ujian', 'Dojo', 'Kabupaten/Kota', 'Provinsi', 'Nominal', 'Status', 'Kanal']);
            foreach ($rows->cursor() as $row) {
                fputcsv($output, [$row->occurred_at, $row->reference, $this->sourceLabel($row->source), $row->member_name,
                    $row->exam_name, $row->dojo_name, $row->city_name, $row->province_name, $row->amount,
                    $this->statusLabel($row->status), $row->channel]);
            }
            fclose($output);
        }, 'rekap-keuangan-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date'],
            'source' => ['nullable', 'in:all,iuran,ujian'],
            'status' => ['nullable', 'in:all,paid,pending,failed,unpaid'],
            'search' => ['nullable', 'string', 'max:150'],
        ]);
    }

    private function range(array $filters): array
    {
        $from = Carbon::parse($filters['date_from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($filters['date_to'] ?? now()->toDateString())->endOfDay();
        if ($from->greaterThan($to)) {
            throw ValidationException::withMessages(['date_from' => 'Tanggal mulai harus sama dengan atau sebelum tanggal akhir.']);
        }

        return [$from, $to];
    }

    private function authorizeAccess(User $actor): void
    {
        abort_unless($actor->hasRole(['pb', 'super_admin', 'pengprov', 'admin_pengprov', 'pengcab', 'admin_pengcab', 'admin_dojo']), 403);
    }

    private function scopedLedger(User $actor): Builder
    {
        $payments = $this->paymentRows($actor);
        $manualExamRows = $this->manualExamRows($actor);

        return DB::query()->fromSub($payments->unionAll($manualExamRows), 'finance_rows');
    }

    private function paymentRows(User $actor): Builder
    {
        $query = DB::table('payments as p')
            ->join('users as payer', 'payer.id', '=', 'p.user_id')
            ->leftJoin('cities as payer_city', 'payer_city.id', '=', 'payer.city_id')
            ->leftJoin('dojos as payer_dojo', 'payer_dojo.id', '=', 'payer.dojo_id')
            ->leftJoin('cities as dojo_city', 'dojo_city.id', '=', 'payer_dojo.city_id')
            ->leftJoin('provinces as payer_province', 'payer_province.id', '=', 'payer.province_id')
            ->leftJoin('provinces as dojo_province', 'dojo_province.id', '=', 'dojo_city.province_id')
            ->whereIn(DB::raw('LOWER(p.type)'), ['iuran', 'membership_fee', 'ujian', 'exam_fee'])
            ->select([
                'p.id as record_id', DB::raw("COALESCE(NULLIF(p.invoice_number, ''), p.reference, CAST(p.id AS CHAR)) as reference"),
                'payer.name as payer_name', 'payer.name as member_name', 'payer_dojo.name as dojo_name',
                DB::raw('NULL as exam_name'),
                'payer.photo as member_photo',
            ])
            ->selectRaw("CASE WHEN LOWER(p.type) IN ('iuran','membership_fee') THEN 'iuran' ELSE 'ujian' END as source")
            ->selectRaw('LOWER(p.status) as status, p.amount as amount')
            ->selectRaw("CASE WHEN LOWER(p.status) = 'paid' THEN COALESCE(p.paid_at, p.updated_at, p.created_at) ELSE p.created_at END as occurred_at")
            ->selectRaw('COALESCE(payer_province.name, dojo_province.name) as province_name')
            ->addSelect(DB::raw('COALESCE(payer_city.name, dojo_city.name) as city_name'))
            ->selectRaw("CASE WHEN p.doku_transaction_id IS NOT NULL THEN 'DOKU' WHEN LOWER(p.status) = 'paid' THEN 'Verifikasi manual' ELSE 'Tagihan sistem' END as channel");

        $this->applyPaymentScope($query, $actor);

        return $query;
    }

    private function manualExamRows(User $actor): Builder
    {
        $query = DB::table('exam_participants as ep')
            ->join('exams as e', 'e.id', '=', 'ep.exam_id')
            ->join('users as member', 'member.id', '=', 'ep.user_id')
            ->leftJoin('dojos as dojo', 'dojo.id', '=', 'ep.dojo_id')
            ->leftJoin('cities as dojo_city', 'dojo_city.id', '=', 'dojo.city_id')
            ->leftJoin('cities as member_city', 'member_city.id', '=', 'member.city_id')
            ->leftJoin('provinces as exam_province', 'exam_province.id', '=', 'e.province_id')
            ->leftJoin('provinces as dojo_province', 'dojo_province.id', '=', 'dojo_city.province_id')
            ->leftJoin('provinces as member_province', 'member_province.id', '=', 'member.province_id')
            ->whereIn('ep.payment_status', ['paid', 'unpaid'])
            ->whereNotExists(function ($exists) {
                $exists->selectRaw('1')->from('payments as settled_payment')
                    ->whereColumn('settled_payment.user_id', 'ep.user_id')
                    ->whereIn(DB::raw('LOWER(settled_payment.type)'), ['ujian', 'exam_fee'])
                    ->whereRaw($this->examReferenceMatch('settled_payment.reference', 'ep.exam_id'));
            })
            ->select([
                'ep.id as record_id', 'ep.id as reference', 'member.name as payer_name', 'member.name as member_name',
                'dojo.name as dojo_name', 'e.name as exam_name', 'member.photo as member_photo',
            ])
            ->selectRaw("'ujian' as source, LOWER(ep.payment_status) as status, ep.fee_amount as amount")
            ->selectRaw("CASE WHEN LOWER(ep.payment_status) = 'paid' THEN COALESCE(ep.updated_at, ep.created_at) ELSE ep.created_at END as occurred_at")
            ->selectRaw('COALESCE(exam_province.name, dojo_province.name, member_province.name) as province_name')
            ->addSelect(DB::raw('COALESCE(dojo_city.name, member_city.name) as city_name'))
            ->selectRaw("CASE WHEN LOWER(ep.payment_status) = 'paid' THEN 'Verifikasi manual' ELSE 'Daftar peserta ujian' END as channel");

        $this->applyExamScope($query, $actor);

        return $query;
    }

    private function examReferenceMatch(string $referenceColumn, string $examIdColumn): string
    {
        return DB::connection()->getDriverName() === 'mysql'
            ? "$referenceColumn = CONCAT('UJIAN:EXAM-', $examIdColumn)"
            : "$referenceColumn = ('UJIAN:EXAM-' || CAST($examIdColumn AS TEXT))";
    }

    private function applyPaymentScope(Builder $query, User $actor): void
    {
        if ($actor->hasRole(['pb', 'super_admin'])) return;

        if ($actor->hasRole(['pengprov', 'admin_pengprov'])) {
            $provinceId = (int) ($actor->province_id ?? 0);
            $query->where(fn ($q) => $q->where('payer.province_id', $provinceId)
                ->orWhere('payer_city.province_id', $provinceId)->orWhere('dojo_city.province_id', $provinceId));
            return;
        }
        if ($actor->hasRole(['pengcab', 'admin_pengcab'])) {
            $cityId = (int) ($actor->city_id ?? 0);
            $query->where(fn ($q) => $q->where('payer.city_id', $cityId)
                ->orWhere('payer_dojo.city_id', $cityId));
            return;
        }

        $query->where('payer.dojo_id', (int) ($actor->dojo_id ?? 0));
    }

    private function applyExamScope(Builder $query, User $actor): void
    {
        if ($actor->hasRole(['pb', 'super_admin'])) return;

        if ($actor->hasRole(['pengprov', 'admin_pengprov'])) {
            $provinceId = (int) ($actor->province_id ?? 0);
            $query->where(fn ($q) => $q->where('e.province_id', $provinceId)
                ->orWhere('dojo_city.province_id', $provinceId)->orWhere('member.province_id', $provinceId));
            return;
        }
        if ($actor->hasRole(['pengcab', 'admin_pengcab'])) {
            $cityId = (int) ($actor->city_id ?? 0);
            $query->where(fn ($q) => $q->where('dojo.city_id', $cityId)->orWhere('member.city_id', $cityId));
            return;
        }

        $query->where('ep.dojo_id', (int) ($actor->dojo_id ?? 0));
    }

    private function applyTableFilters(Builder $query, array $filters): Builder
    {
        if (($filters['source'] ?? 'all') !== 'all') $query->where('source', $filters['source']);

        $status = $filters['status'] ?? 'all';
        if ($status === 'failed') $query->whereIn('status', ['failed', 'expired', 'canceled']);
        elseif ($status !== 'all') $query->where('status', $status);

        if (!empty($filters['search'])) {
            $term = '%'.trim($filters['search']).'%';
            $query->where(fn ($q) => $q->where('reference', 'like', $term)->orWhere('payer_name', 'like', $term)
                ->orWhere('member_name', 'like', $term)->orWhere('dojo_name', 'like', $term)
                ->orWhere('exam_name', 'like', $term)->orWhere('city_name', 'like', $term)->orWhere('province_name', 'like', $term));
        }

        return $query;
    }

    private function monthlyTrend(User $actor): array
    {
        $months = collect(range(5, 0))->map(fn ($offset) => now()->startOfMonth()->subMonths($offset));
        $results = [];
        foreach ($months as $month) {
            $rows = $this->scopedLedger($actor)->where('status', 'paid')
                ->whereBetween('occurred_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->selectRaw('source, SUM(amount) as total')->groupBy('source')->pluck('total', 'source');
            $results[] = ['label' => $month->locale('id')->translatedFormat('M'), 'iuran' => (float) ($rows['iuran'] ?? 0), 'ujian' => (float) ($rows['ujian'] ?? 0)];
        }

        return $results;
    }

    public static function sourceLabel(string $source): string
    {
        return $source === 'iuran' ? 'Iuran' : 'Ujian';
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'paid' => 'Lunas', 'pending' => 'Menunggu', 'unpaid' => 'Belum dibayar',
            'failed', 'expired', 'canceled' => 'Gagal / kedaluwarsa', default => ucfirst($status),
        };
    }
}
