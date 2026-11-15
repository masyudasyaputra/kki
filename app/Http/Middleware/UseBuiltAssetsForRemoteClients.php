<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;

class UseBuiltAssetsForRemoteClients
{
    public function handle(Request $request, Closure $next)
    {
        $hotFile = Vite::hotFile();
        $loopback = ['localhost', '127.0.0.1', '::1'];
        $clientHost = strtolower(trim($request->getHost(), '[]'));
        $hotHost = is_file($hotFile) ? strtolower(trim((string) parse_url(trim(file_get_contents($hotFile)), PHP_URL_HOST), '[]')) : '';
        if (in_array($clientHost, $loopback, true) || !in_array($hotHost, $loopback, true) || !is_file(public_path('build/manifest.json'))) {
            return $next($request);
        }
        // A phone cannot reach the desktop's loopback development server.
        Vite::useHotFile(public_path('build/.remote-no-hot'));
        try {
            return $next($request);
        } finally {
            Vite::useHotFile($hotFile);
        }
    }
}
