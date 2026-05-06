@if ($paginator->hasPages())
    <nav id="pagination-nav" class="arsipkan-pagination" aria-label="Navigasi halaman">
        <style>
            .arsipkan-pagination .pagination {
                margin-bottom: 0;
            }

            .arsipkan-pagination .page-link {
                border-radius: 0.9rem;
                min-width: 2.75rem;
                height: 2.75rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0.6rem 0.9rem;
                font-weight: 600;
            }

            .arsipkan-pagination .page-item:not(:first-child) .page-link {
                margin-left: 0.4rem;
            }

            .arsipkan-pagination .page-item.active .page-link {
                background: var(--bs-primary, #0d6efd);
                border-color: var(--bs-primary, #0d6efd);
                box-shadow: 0 8px 18px rgba(13, 110, 253, 0.18);
            }

            .arsipkan-pagination-summary {
                font-size: 0.92rem;
            }

            .arsipkan-pagination-mobile {
                gap: 0.75rem;
            }

            .arsipkan-pagination-mobile .pagination {
                width: 100%;
                gap: 0.75rem;
            }

            .arsipkan-pagination-mobile .page-item {
                flex: 1 1 0;
            }

            .arsipkan-pagination-mobile .page-link {
                width: 100%;
                margin-left: 0 !important;
                border-radius: 1rem;
                padding: 0.8rem 1rem;
                justify-content: center;
                white-space: nowrap;
            }
        </style>
        <script>
            (() => {
                const storageKey = 'arsipkan-pagination-scroll-y';

                document.addEventListener('DOMContentLoaded', () => {
                    const savedScroll = sessionStorage.getItem(storageKey);

                    if (savedScroll !== null) {
                        sessionStorage.removeItem(storageKey);
                        window.scrollTo({ top: Number(savedScroll), behavior: 'auto' });
                    }

                    document.querySelectorAll('#pagination-nav a.page-link').forEach((link) => {
                        link.addEventListener('click', () => {
                            sessionStorage.setItem(storageKey, String(window.scrollY));
                        }, { once: true });
                    });
                }, { once: true });
            })();
        </script>

        <div class="d-flex d-sm-none arsipkan-pagination-mobile">
            <ul class="pagination w-100">
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled flex-fill" aria-disabled="true">
                        <span class="page-link">{{ __('pagination.previous') }}</span>
                    </li>
                @else
                    <li class="page-item flex-fill">
                        <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">{{ __('pagination.previous') }}</a>
                    </li>
                @endif

                @if ($paginator->hasMorePages())
                    <li class="page-item flex-fill">
                        <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('pagination.next') }}</a>
                    </li>
                @else
                    <li class="page-item disabled flex-fill" aria-disabled="true">
                        <span class="page-link">{{ __('pagination.next') }}</span>
                    </li>
                @endif
            </ul>
        </div>

        <div class="d-none d-sm-flex align-items-sm-center justify-content-sm-between gap-3">
            <div class="small text-muted arsipkan-pagination-summary">
                Menampilkan
                <span class="fw-semibold">{{ $paginator->firstItem() }}</span>
                sampai
                <span class="fw-semibold">{{ $paginator->lastItem() }}</span>
                dari
                <span class="fw-semibold">{{ $paginator->total() }}</span>
                hasil
            </div>

            <div>
                <ul class="pagination">
                    @if ($paginator->onFirstPage())
                        <li class="page-item disabled" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="page-link" aria-hidden="true">&lsaquo;</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}">&lsaquo;</a>
                        </li>
                    @endif

                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                                @else
                                    <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    @if ($paginator->hasMorePages())
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}">&rsaquo;</a>
                        </li>
                    @else
                        <li class="page-item disabled" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="page-link" aria-hidden="true">&rsaquo;</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>
@endif
