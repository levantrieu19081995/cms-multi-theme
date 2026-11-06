{{-- Dải thông báo mới dưới banner (bật/tắt/sắp xếp bằng Homepage Layout Manager). --}}
@if ($homeNotices->isNotEmpty())
    <div class="cms-ticker" aria-label="{{ __('frontend.ui.thong_bao_moi') }}">
        <span class="cms-ticker-badge">{{ __('frontend.ui.thong_bao') }}</span>
        <span class="cms-ticker-star" aria-hidden="true">★</span>
        <a href="{{ $homeNotices->first()->url ?? \Modules\CmsBackend\Support\ModuleUrl::route('cms.tin-tuc.show', ['slug' => $homeNotices->first()->slug]) }}">{{ $homeNotices->first()->title }} <span aria-hidden="true">→</span></a>
    </div>
@endif
