<div class="cms-screen-group cms-home-section cms-home-section--white">
{{-- "Chuyên mục nổi bật": mỗi chuyên mục chọn ở Cài đặt trang chủ là một hàng (tiêu đề + "Xem tất cả" + dải thẻ tự trượt ngang). --}}
@php $categoryTabs = collect($homeCategoryTabs ?? []); @endphp
<section class="cms-section cms-featured" aria-labelledby="cms-featured-title">
    <div class="cms-section-heading cms-featured-heading">
        <h2 id="cms-featured-title">{{ __('frontend.ui.chuyen_muc_noi_bat') }}</h2>
        <p class="cms-featured-sub">{{ __('frontend.ui.chuyen_muc_noi_bat_mo_ta') }}</p>
    </div>
    @forelse ($categoryTabs as $tab)
        <div class="cms-featured-row">
            <div class="cms-featured-head">
                <h3 id="cms-featured-row-{{ $tab['id'] }}"><a href="{{ $tab['url'] }}">{{ $tab['title'] }}</a></h3>
                <a class="cms-more" href="{{ $tab['url'] }}">{{ __('frontend.ui.xem_tat_ca') }} <span aria-hidden="true">→</span></a>
            </div>
            <div class="cms-featured-track" data-cms-slider role="group" aria-labelledby="cms-featured-row-{{ $tab['id'] }}">
                @foreach ($tab['stories'] as $story)
                    @include('cmsfrontend::screens.components.home.story-card', ['story' => $story, 'variant' => 'technical', 'categoryLabel' => null])
                @endforeach
            </div>
        </div>
    @empty
        <p class="cms-empty">{{ __('frontend.ui.chua_co_chuyen_muc_noi_bat_co_bai_viet_duoc_cong') }}</p>
    @endforelse
</section>
</div>
