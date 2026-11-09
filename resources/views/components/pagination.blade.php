@props(['paginator', 'label' => 'data'])
@php
    $requestedPageSize = request()->query('per_page');
    $selectedPageSize = in_array((string) $requestedPageSize, ['10', '25', '50', '100', 'all'], true)
        ? $requestedPageSize
        : $paginator->perPage();
    $pageSizes = [10, 25, 50, 100];
    if ($selectedPageSize !== 'all' && !in_array((int) $selectedPageSize, $pageSizes, true)) {
        $pageSizes[] = (int) $selectedPageSize;
        sort($pageSizes);
    }
@endphp
<div class="pagination-panel">
    <p class="pagination-summary">
        @if($paginator->total())
            Menampilkan <strong>{{ number_format($paginator->firstItem()) }}–{{ number_format($paginator->lastItem()) }}</strong> dari <strong>{{ number_format($paginator->total()) }}</strong> {{ $label }}
        @else
            Belum ada {{ $label }}
        @endif
    </p>
    <div class="pagination-actions">
        <form method="get" action="{{ request()->url() }}" class="pagination-size-form">
            @foreach(request()->query() as $key => $value)
                @if(!in_array($key, ['page', 'per_page'], true) && is_scalar($value))
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <label for="per-page-{{ $paginator->getPageName() }}">Tampilkan</label>
            <select id="per-page-{{ $paginator->getPageName() }}" name="per_page" onchange="this.form.requestSubmit()">
                @foreach($pageSizes as $size)
                    <option value="{{ $size }}" @selected((string) $selectedPageSize === (string) $size)>{{ $size }}</option>
                @endforeach
                <option value="all" @selected($selectedPageSize === 'all')>Semua</option>
            </select>
            <span>per halaman</span>
        </form>

        @if($paginator->hasPages())
            <nav class="pagination-nav" role="navigation" aria-label="Navigasi halaman">
                @if($paginator->onFirstPage())
                    <span class="pagination-button is-disabled" aria-disabled="true">Sebelumnya</span>
                @else
                    <a class="pagination-button" href="{{ $paginator->previousPageUrl() }}" rel="prev">Sebelumnya</a>
                @endif
                <div class="pagination-pages">
                    @php
                        $firstVisiblePage = $paginator->lastPage() <= 5 ? 1 : max(1, $paginator->currentPage() - 1);
                        $lastVisiblePage = $paginator->lastPage() <= 5 ? $paginator->lastPage() : min($paginator->lastPage(), $paginator->currentPage() + 1);
                    @endphp
                    @if($firstVisiblePage > 1)
                        <a class="pagination-page" href="{{ $paginator->url(1) }}" aria-label="Halaman 1">1</a>
                        @if($firstVisiblePage > 2)<span class="pagination-dots" aria-hidden="true">…</span>@endif
                    @endif
                    @for($page = $firstVisiblePage; $page <= $lastVisiblePage; $page++)
                        @if($page === $paginator->currentPage())
                            <span class="pagination-page is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="pagination-page" href="{{ $paginator->url($page) }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                        @endif
                    @endfor
                    @if($lastVisiblePage < $paginator->lastPage())
                        @if($lastVisiblePage < $paginator->lastPage() - 1)<span class="pagination-dots" aria-hidden="true">…</span>@endif
                        <a class="pagination-page" href="{{ $paginator->url($paginator->lastPage()) }}" aria-label="Halaman {{ $paginator->lastPage() }}">{{ $paginator->lastPage() }}</a>
                    @endif
                </div>
                @if($paginator->hasMorePages())
                    <a class="pagination-button" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya</a>
                @else
                    <span class="pagination-button is-disabled" aria-disabled="true">Berikutnya</span>
                @endif
            </nav>
        @endif
    </div>
</div>
