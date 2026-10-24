<!doctype html>
<html lang="id"><head>
    <link rel="icon" type="image/webp" href="{{ asset('images/logo-shokaido.webp') }}"><meta charset="utf-8"><title>Pembayaran anggota</title></head>
<body><p>Membuka halaman pembayaran...</p>
<script>
    window.parent.postMessage({ type: 'create-modal:checkout', url: @json($checkoutUrl) }, window.location.origin);
</script>
</body></html>
