<?php

namespace App\Services;

use App\Models\{MemberNumberChange, User};
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberNumbers
{
    public function issue(User $member): void
    {
        if ($member->role !== 'member' || $member->member_number_override ||
            !$member->getConnection()->getSchemaBuilder()->hasColumn('users', 'member_number_override')) return;
        $member->load(['province', 'beltLevel']);
        $candidate = $member->automatic_member_number;
        $separator = strrpos($candidate, '-');
        $prefix = substr($candidate, 0, $separator + 1);
        $sequence = (int) substr($candidate, $separator + 1);
        // The unique database index arbitrates concurrent registration and manual changes.
        for ($attempt = 0; $attempt < 1000; $attempt++, $sequence++) {
            try {
                DB::transaction(function () use ($member, $prefix, $sequence) {
                    $member->forceFill(['member_number_override' => $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT), 'member_number_manual' => false])->saveQuietly();
                });
                return;
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === 999) throw $exception;
            }
        }
    }

    public function change(User $member, User $actor, array $data): void
    {
        DB::transaction(function () use ($member, $actor, $data) {
            $locked = User::whereKey($member->id)->where('role', 'member')->lockForUpdate()->firstOrFail();
            $locked->load(['province', 'beltLevel']);
            $this->checkCurrent($locked, $data['expected_number']);
            $before = $locked->member_number;
            $number = $data['mode'] === 'auto' ? $locked->automatic_member_number : $data['number'];
            if (User::where('member_number_override', $number)->where('id', '!=', $locked->id)->exists()) {
                throw ValidationException::withMessages(['number' => 'Nomor ID sudah dipakai anggota lain. Gunakan fitur tukar nomor jika ingin menukarnya.']);
            }
            if ($before === $number && (bool) $locked->member_number_manual === ($data['mode'] === 'manual')) {
                throw ValidationException::withMessages(['number' => 'Tidak ada perubahan nomor atau mode penomoran.']);
            }
            try {
                $locked->forceFill(['member_number_override' => $number, 'member_number_manual' => $data['mode'] === 'manual'])->save();
            } catch (UniqueConstraintViolationException $exception) {
                throw ValidationException::withMessages(['number' => 'Nomor ID baru saja digunakan. Muat ulang halaman dan pilih nomor lain.']);
            }
            $this->record($locked, $actor, $before, $data['mode'], $data['reason']);
        });
    }

    public function swap(User $member, User $actor, array $data): void
    {
        $target = User::where('role', 'member')->whereMemberNumber($data['target_number'])->first();
        if (!$target || $target->id === $member->id) {
            throw ValidationException::withMessages(['target_number' => 'Masukkan Nomor ID anggota lain yang valid.']);
        }
        DB::transaction(function () use ($member, $actor, $target, $data) {
            $members = User::where('role', 'member')->whereIn('id', [$member->id, $target->id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            abort_unless($members->count() === 2, 404);
            $first = $members[$member->id];
            $second = $members[$target->id];
            $this->checkCurrent($first, $data['expected_number']);
            if ($second->member_number !== $data['target_number']) {
                throw ValidationException::withMessages(['target_number' => 'Nomor tujuan berubah. Muat ulang dan periksa kembali.']);
            }
            $a = $first->member_number;
            $b = $second->member_number;
            // Release both unique values inside the transaction, then exchange atomically.
            User::whereIn('id', $members->keys())->update(['member_number_override' => null]);
            $first->forceFill(['member_number_override' => $b, 'member_number_manual' => true])->save();
            $second->forceFill(['member_number_override' => $a, 'member_number_manual' => true])->save();
            $this->record($first, $actor, $a, 'swap', $data['reason'].' [Ditukar dengan akun #'.$second->id.']');
            $this->record($second, $actor, $b, 'swap', $data['reason'].' [Ditukar dengan akun #'.$first->id.']');
        });
    }

    private function checkCurrent(User $member, string $expected): void
    {
        if ($member->member_number !== $expected) throw ValidationException::withMessages(['expected_number' => 'Nomor berubah sejak halaman dibuka. Muat ulang sebelum menyimpan.']);
    }

    private function record(User $member, User $actor, string $before, string $mode, string $reason): void
    {
        MemberNumberChange::create(['member_id' => $member->id, 'actor_id' => $actor->id, 'before_number' => $before, 'after_number' => $member->member_number, 'mode' => $mode, 'reason' => $reason]);
    }
}
