<x-app-layout>
    <x-slot name="header"><h1 class="page-title">{{ $exam->name }} — Pengesahan {{ app(\App\Services\ExamAuthority::class)->destination($exam) }}</h1></x-slot>
    <div class="mx-auto max-w-7xl space-y-5 p-6">
        @if(session('success'))<p role="status">{{ session('success') }}</p>@endif
        @if($errors->any())<ul class="text-red-600">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
        <p>Status: {{ ['draft' => 'Belum dikirim', 'submitted' => 'Menunggu pengesahan', 'revision' => 'Perlu perbaikan', 'approved' => 'Disahkan'][$exam->review_status] ?? $exam->review_status }}</p>
        @if(!auth()->user()->hasRole('super_admin') || \App\Services\RoleAccess::nationalExamReader(auth()->user()))<div class="flex gap-3"><a class="btn btn-secondary" href="{{ route('admin.exams.show', $exam) }}">Data peserta & sertifikat</a><a class="btn btn-secondary" href="{{ route('admin.exams.scoring', $exam) }}">{{ app(\App\Services\ExamCertificates::class)->canScore(auth()->user(), $exam) ? 'Input nilai ujian' : 'Lihat nilai ujian' }}</a></div>@endif
        @if($canAssign && $exam->review_status !== 'approved')
            <form method="post" action="{{ route('admin.exam-reviews.assign', $exam) }}" class="space-y-3 rounded-xl bg-white p-5"
                x-data="verificationChecklist(@js($reviewers->pluck('id')->values()), @js(old('reviewer_ids', $assigned)))">@csrf @method('put')
                <h2 class="font-semibold">Penugasan Verifikator Ujian Pengurus Pusat</h2>
                <p class="text-xs text-slate-500">Dapat ditugaskan kepada Dewan Guru atau pengguna lain yang memiliki hak Verifikator Ujian Pengurus Pusat. Panitia, penguji, dan pengirim sesi ini dikecualikan.</p>
                <input type="hidden" name="reviewer_ids" value="">
                @if($reviewers->isNotEmpty())<x-verification-select-all label="Pilih semua verifikator" />@endif
                @forelse($reviewers as $reviewer)<label class="flex gap-2"><input type="checkbox" name="reviewer_ids[]" value="{{ $reviewer->id }}" x-model="checked" @checked(in_array($reviewer->id, (array) old('reviewer_ids', $assigned)))>{{ $reviewer->name }}{{ $reviewer->hasRole('dewan_guru_pusat') ? ' — Dewan Guru Pusat' : '' }}</label>@empty<p>Belum ada pengguna yang memenuhi syarat. Berikan hak Verifikator Ujian Pengurus Pusat melalui Manajemen User.</p>@endforelse
                <button class="btn btn-primary">Simpan penugasan</button>
            </form>
        @endif
        @if($submission)
            <div class="rounded-xl bg-white p-5"><h2 class="font-semibold">Data yang dikirim panitia</h2><p>{{ $submission->snapshot['exam']['location'] ?? '' }} · {{ $submission->created_at->format('d/m/Y H:i') }}</p></div>
            <div class="overflow-x-auto rounded-xl bg-white p-5"><table class="w-full text-left text-sm"><thead><tr><th>Peserta / Dojo</th>@foreach(\App\Services\ExamCertificates::materials($submission->snapshot['exam']['exam_type']) as $label)<th class="p-2">{{ $label }}</th>@endforeach<th>Hasil</th></tr></thead><tbody>
                @foreach($submission->snapshot['participants'] as $p)<tr class="border-t"><td class="p-3"><x-member-identity :name="$p['name']" :photo="$participantPhotos[$p['member_id']] ?? null">{{ $p['name'] }}<p>{{ $p['member_number'] }} · {{ $p['dojo'] }}</p></x-member-identity></td>@foreach(\App\Services\ExamCertificates::materials($submission->snapshot['exam']['exam_type']) as $field => $label)<td class="p-2">{{ $p['score'][$field] ?? '-' }}</td>@endforeach<td>{{ $p['score']['result'] ?? '-' }}</td></tr>@endforeach
            </tbody></table></div>
        @else<p>Panitia belum mengirimkan hasil ujian.</p>@endif
        @if($canReview && $exam->review_status === 'submitted')
            <form method="post" action="{{ route('admin.exam-reviews.store', $exam) }}" class="space-y-3 rounded-xl bg-white p-5">@csrf
                <label class="block">Keputusan<select name="status" required><option value="approved">Setujui hasil ujian</option><option value="revision">Kembalikan untuk perbaikan</option></select></label>
                <label class="block">Catatan (wajib untuk perbaikan)<textarea name="notes" maxlength="5000">{{ old('notes') }}</textarea></label>
                <button class="btn btn-primary">Simpan verifikasi</button>
            </form>
        @endif
        <div class="rounded-xl bg-white p-5"><h2 class="font-semibold">Riwayat pengajuan</h2>@foreach($history as $review)<p class="mt-3">{{ $review->created_at->format('d/m/Y H:i') }} · {{ $review->actor?->name ?? 'Akun dihapus' }} · {{ $review->status }}</p><p class="whitespace-pre-line">{{ $review->notes }}</p>@endforeach</div>
    </div>
</x-app-layout>
