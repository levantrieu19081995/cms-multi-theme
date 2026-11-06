<div class="cms-section-heading">
    <h2>{{ $heading }}</h2>
    @if (!empty($moreUrl))
        <a class="cms-more" href="{{ $moreUrl }}">{{ $moreLabel ?? __('pages.xem_tat_ca') }} <span aria-hidden="true">→</span></a>
    @endif
</div>
