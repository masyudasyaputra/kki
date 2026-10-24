<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class CreateFormModalTest extends TestCase
{
    public function test_embedded_form_hides_backend_navigation_and_keeps_validation_messages(): void
    {
        $request = Request::create('/admin/dojos/create', 'GET', ['_form_modal' => '1']);
        $this->app->instance('request', $request);
        $errors = new \Illuminate\Support\ViewErrorBag();
        $errors->put('default', new \Illuminate\Support\MessageBag(['name' => 'Nama wajib diisi.']));
        view()->share('errors', $errors);
        $html = Blade::render('<x-app-layout><form action="/admin/dojos" method="POST"><input name="name" value="Dojo A"></form></x-app-layout>');
        $this->assertStringContainsString('form-modal-body', $html);
        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('Nama wajib diisi.', $html);
        $this->assertStringContainsString('data-modal-status="error"', $html);
        $this->assertStringNotContainsString('id="backend-sidebar"', $html);
        $this->assertStringNotContainsString('aria-label="Logout"', $html);
    }

    public function test_iframe_redirects_keep_the_embedded_layout_without_query_parameter(): void
    {
        $request = Request::create('/admin/dojos', 'GET', [], [], [], ['HTTP_SEC_FETCH_DEST' => 'iframe']);
        $this->app->instance('request', $request);
        $html = Blade::render('<x-app-layout><p>Form response</p></x-app-layout>');
        $this->assertStringContainsString('form-modal-body', $html);
        $this->assertStringNotContainsString('id="backend-sidebar"', $html);
    }

    public function test_checkout_handoff_serializes_the_payment_url_safely(): void
    {
        $html = view('admin.members.checkout-handoff', ['checkoutUrl' => 'https://checkout.example/pay?id=1'])->render();
        $this->assertStringContainsString('create-modal:checkout', $html);
        $this->assertStringContainsString('window.location.origin', $html);
        $this->assertStringNotContainsString('window.parent.location', $html);
    }
}
