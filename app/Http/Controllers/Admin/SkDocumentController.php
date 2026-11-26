<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{City, Dojo, Exam, Official, Province};
use App\Services\RoleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SkDocumentController extends Controller
{
    public function show(Request $request, string $entity, int $id)
    {
        $actor = $request->user();
        $path = match ($entity) {
            'province' => $this->provincePath($actor, $id),
            'official' => $this->officialPath($actor, $id),
            'dojo' => $this->dojoPath($actor, $id),
            'exam' => $this->examPath($actor, $id),
            default => abort(404),
        };

        abort_unless($path && Storage::disk('local')->exists($path), 404);
        $fileName = basename($path);
        $mime = Storage::disk('local')->mimeType($path) ?: 'application/octet-stream';

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function provincePath($actor, int $id): ?string
    {
        $province = Province::findOrFail($id);
        $allowed = RoleAccess::archives($actor)
            || ($actor->hasRole(['pengprov', 'admin_pengprov']) && (int) $actor->province_id === (int) $province->id);
        abort_unless($allowed, 403);

        return $province->sk_document_path;
    }

    private function officialPath($actor, int $id): ?string
    {
        $official = Official::findOrFail($id);
        $allowed = (RoleAccess::nationalOfficials($actor) || RoleAccess::archives($actor))
            || ($actor->hasRole(['pengprov', 'admin_pengprov', 'verifikator_pengurus_provinsi']) && (int) $actor->province_id === (int) $official->province_id)
            || ($official->city_id && $actor->hasRole(['pengcab', 'admin_pengcab', 'verifikator_pengurus_pengcab']) && (int) $actor->city_id === (int) $official->city_id);
        abort_unless($allowed, 403);

        return $official->sk_document_path;
    }

    private function dojoPath($actor, int $id): ?string
    {
        $dojo = Dojo::with('city')->findOrFail($id);
        $allowed = RoleAccess::archives($actor)
            || ($actor->hasRole(['pengprov', 'admin_pengprov']) && (int) $actor->province_id === (int) $dojo->city?->province_id)
            || ($actor->hasRole(['pengcab', 'admin_pengcab']) && (int) $actor->city_id === (int) $dojo->city_id)
            || ($actor->hasRole('admin_dojo') && (int) $actor->dojo_id === (int) $dojo->id);
        abort_unless($allowed, 403);

        return $dojo->sk_document_path;
    }

    private function examPath($actor, int $id): ?string
    {
        $exam = Exam::findOrFail($id);
        app(\App\Services\ExamCertificates::class)->authorizeIssuance($actor, $exam);

        return $exam->certificate_settings['decision_document_path'] ?? null;
    }
}
