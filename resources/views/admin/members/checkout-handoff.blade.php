<!doctype html>
<html lang="id"><head>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-kki.png') }}"><meta charset="utf-8"><title>Pembayaran anggota</title></head>
<body><p>Membuka halaman pembayaran...</p>
<script>
    window.parent.postMessage({ type: 'create-modal:checkout', url: @json($checkoutUrl) }, window.location.origin);
</script>
</body></html>
