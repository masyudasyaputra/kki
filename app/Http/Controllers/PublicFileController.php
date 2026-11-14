<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicFileController extends Controller
{
    /** Serve public uploads when the host cannot expose the storage symlink. */
    public function __invoke(string $path): BinaryFileResponse
    {
        $disk = Storage::disk('public');
        $root = realpath($disk->path(''));
        $file = realpath($disk->path($path));

        abort_unless($root && $file && is_file($file), 404);
        // Resolve the actual file before checking containment, including symlinks.
        abort_unless(str_starts_with($file, $root.DIRECTORY_SEPARATOR), 404);
        abort_unless(in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'pdf'], true), 404);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'application/pdf'], true), 404);

        return response()->file($file, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
