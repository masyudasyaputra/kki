<?php

namespace App\Services\Dashboard;

use App\Models\BeltLevel;
use App\Models\Dojo;
use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\Official;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class DashboardSummary
{
    public function users(User $actor, string $role): Builder
    {
        $query = User::query();
        if ($role === 'pb') {
            return $query;
        }
        $column = ['pengprov' => 'province_id', 'pengcab' => 'city_id', 'admin_dojo' => 'dojo_id'][$role] ?? null;
        if (! $column) {
            return $query->whereKey($actor->id);
        }

        return $actor->$column ? $query->where($column, $actor->$column) : $query->whereRaw('1 = 0');
    }

    public function build(User $actor, string $role, CarbonImmutable $month): array
    {
        $today = CarbonImmutable::today();
        $start = $month->startOfMonth();
        $end = $start->addMonth();
        $users = $this->users($actor, $role);
        $members = (clone $users)->where('role', 'member');
        $dojos = Dojo::query();
        $officials = Official::query();
        $exams = Exam::query();

        if ($role === 'pengprov' && $actor->province_id) {
            $dojos->whereHas('city', fn ($q) => $q->where('province_id', $actor->province_id));
            $officials->where('province_id', $actor->province_id);
            $exams->where('province_id', $actor->province_id);
        } elseif ($role === 'pengcab' && $actor->city_id) {
            $dojos->where('city_id', $actor->city_id);
            $officials->where('city_id', $actor->city_id);
            $exams->whereHas('participants.user', fn ($q) => $q->whereIn('id', (clone $members)->select('id')));
        } elseif ($role === 'admin_dojo' && $actor->dojo_id) {
            $dojos->whereKey($actor->dojo_id);
            $officials->where('city_id', $actor->dojo?->city_id ?? -1);
            $exams->whereHas('participants', fn ($q) => $q->where('dojo_id', $actor->dojo_id));
        } elseif ($role !== 'pb') {
            $dojos->whereRaw('1 = 0');
            $officials->whereRaw('1 = 0');
            $exams->whereRaw('1 = 0');
        }

        $organization = [
            'members' => (clone $members)->count(),
            'active_members' => (clone $members)->where('is_active', true)->count(),
            'dojos' => (clone $dojos)->count(),
            'active_dojos' => (clone $dojos)->whereDate('sk_expiry_date', '>=', $today)->count(),
            'cities' => (clone $dojos)->whereNotNull('city_id')->distinct()->count('city_id'),
            'officials' => (clone $officials)->count(),
            'active_officials' => (clone $officials)->whereDate('sk_expiry_date', '>=', $today)->count(),
            'dojo_expiring' => (clone $dojos)->whereDate('sk_expiry_date', '>=', $today)->whereDate('sk_expiry_date', '<=', $today->addDays(30))->count(),
            'dojo_expired' => (clone $dojos)->whereDate('sk_expiry_date', '<', $today)->count(),
            'official_expiring' => (clone $officials)->whereDate('sk_expiry_date', '>=', $today)->whereDate('sk_expiry_date', '<=', $today->addDays(30))->count(),
            'official_expired' => (clone $officials)->whereDate('sk_expiry_date', '<', $today)->count(),
        ];

        $branchDojos = (clone $dojos)
            ->select('city_id')
            ->selectRaw('COUNT(*) as dojo_count')
            ->selectRaw('SUM(CASE WHEN sk_expiry_date >= ? THEN 1 ELSE 0 END) as active_dojo_count', [$today->toDateString()])
            ->groupBy('city_id')
            ->with('city.province')
            ->get()
            ->sortBy(fn ($row) => $row->city?->name ?? 'Belum ditetapkan', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        // Payments belong to the payer, who may be an administrator paying collectively.
        // Never join to participants: one invoice must contribute to receipts only once.
        $payments = Payment::whereIn('user_id', (clone $users)->select('id'));
        $paid = (clone $payments)->where('status', 'paid')
            ->whereRaw('COALESCE(paid_at, updated_at) >= ?', [$start])
            ->whereRaw('COALESCE(paid_at, updated_at) < ?', [$end]);
        $pending = (clone $payments)->where('status', 'pending')
            ->where('created_at', '>=', $start)->where('created_at', '<', $end)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
        $finance = [];
        foreach (['iuran' => ['iuran', 'membership_fee'], 'ujian' => ['ujian', 'exam_fee']] as $type => $aliases) {
            $finance[$type] = [
                'paid' => (float) (clone $paid)->whereIn('type', $aliases)->sum('amount'),
                'paid_count' => (clone $paid)->whereIn('type', $aliases)->count(),
                'pending' => (float) (clone $pending)->whereIn('type', $aliases)->sum('amount'),
                'pending_count' => (clone $pending)->whereIn('type', $aliases)->count(),
            ];
        }
        $finance['total'] = $finance['iuran']['paid'] + $finance['ujian']['paid'];
        $finance['pending'] = $finance['iuran']['pending'] + $finance['ujian']['pending'];

        $participants = ExamParticipant::whereIn('user_id', (clone $members)->select('id'))
            ->whereHas('exam', fn ($q) => $q->where('execution_date', '>=', $start)->where('execution_date', '<', $end));
        $examSummary = [
            'sessions' => (clone $exams)->where('execution_date', '>=', $start)->where('execution_date', '<', $end)->count(),
            'participants' => (clone $participants)->count(),
            'paid_participants' => (clone $participants)->where('payment_status', 'paid')->count(),
            'unpaid_amount' => (float) (clone $participants)->where('payment_status', 'unpaid')->sum('fee_amount'),
            'paid_amount' => (float) (clone $participants)->where('payment_status', 'paid')->sum('fee_amount'),
        ];
        $upcomingExams = (clone $exams)->whereDate('execution_date', '>=', $today)
            ->whereIn('status', ['open', 'ongoing'])->orderBy('execution_date')->limit(4)->get();
        $counts = (clone $members)->selectRaw('belt_level_id, COUNT(*) as total')->groupBy('belt_level_id')->pluck('total', 'belt_level_id');
        $beltCounts = [];
        foreach (BeltLevel::orderBy('order')->get() as $belt) {
            if (isset($counts[$belt->id])) {
                $beltCounts[$belt->name] = (int) $counts[$belt->id];
            }
        }
        if (isset($counts[''])) {
            $beltCounts['Belum ditetapkan'] = (int) $counts[''];
        }

        return compact('organization', 'finance', 'examSummary', 'upcomingExams', 'beltCounts', 'branchDojos');
    }
}
