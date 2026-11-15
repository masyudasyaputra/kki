<?php

namespace Tests\Feature;

use App\Http\Middleware\UseBuiltAssetsForRemoteClients;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

class RemoteAssetsTest extends TestCase
{
    public function test_mobile_lan_request_uses_built_assets_and_restores_desktop_hot_server(): void
    {
        if (!is_file(public_path('hot')) || !is_file(public_path('build/manifest.json'))) $this->markTestSkipped('Local Vite hot marker and build are required for this environment check.');
        $original = Vite::hotFile();
        $response = (new UseBuiltAssetsForRemoteClients)->handle(Request::create('http://192.168.1.5:8000/'), function () {
            $html = app(\Illuminate\Foundation\Vite::class)(['resources/js/app.js'])->toHtml();
            $this->assertStringNotContainsString('[::1]:5173', $html);
            $this->assertStringContainsString('/build/assets/', $html);
            return response($html);
        });
        $this->assertSame(200, $response->status());
        $this->assertSame($original, Vite::hotFile());
    }
}
