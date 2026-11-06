<nav class="cms-pagination" aria-label="{{ __('admin.ui.phan_trang_danh_sach') }}">
    <p class="cms-pagination-summary">{{ __('admin.ui.dang_hien_thi') }} <strong>{{ $paginator->firstItem() ?? 0 }}</strong> {{ __('admin.ui.den') }} <strong>{{ $paginator->lastItem() ?? 0 }}</strong> {{ __('admin.ui.tren_tong_so') }} <strong>{{ $paginator->total() }}</strong></p>
    <ul class="pagination mb-0">
        @if($paginator->onFirstPage())
            <li class="page-item disabled" aria-disabled="true"><span class="page-link" aria-label="{{ __('admin.ui.trang_truoc') }}">‹</span></li>
        @else
            <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('admin.ui.trang_truoc') }}">‹</a></li>
        @endif
        @foreach($elements as $element)
            @if(is_string($element))
                <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
            @endif
            @if(is_array($element))
                @foreach($element as $page => $url)
                    @if($page == $paginator->currentPage())
                        <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $url }}" aria-label="Trang {{ $page }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach
        @if($paginator->hasMorePages())
            <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Trang sau">›</a></li>
        @else
            <li class="page-item disabled" aria-disabled="true"><span class="page-link" aria-label="Trang sau">›</span></li>
        @endif
    </ul>
</nav>
