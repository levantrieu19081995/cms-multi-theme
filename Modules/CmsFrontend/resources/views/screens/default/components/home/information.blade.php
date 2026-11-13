{{-- Thông báo theo chuyên mục. Văn bản, tài liệu là cụm riêng (blocks.documents). --}}
<div class="cms-home-section cms-home-section--green cms-notices-band">
    <section class="cms-section" aria-labelledby="cms-information-title">
        <div class="cms-section-heading">
            <h2 id="cms-information-title">{{ __('frontend.ui.thong_tin_thong_bao_2') }}</h2>
            <a class="cms-more" href="{{ $noticeUrl }}">{{ __('frontend.ui.xem_tat_ca') }} <span aria-hidden="true">→</span></a>
        </div>
        <div class="cms-information-list">
            @forelse ($homeNotices as $story)
                @include('cmsfrontend::screens.components.home.story-card', ['story' => $story, 'variant' => 'row'])
            @empty
                <p class="cms-empty">{{ __('frontend.ui.chua_co_thong_bao_duoc_cong_bo') }}</p>
            @endforelse
        </div>
    </section>
</div>
