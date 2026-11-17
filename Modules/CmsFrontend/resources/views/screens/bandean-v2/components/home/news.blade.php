{{-- Cụm "Tin tức, sự kiện nổi bật" của bandean-v2 (thiết kế bannerSBUT): bộ lọc dạng nút, băng thẻ cuộn ngang (3 thẻ mỗi trang), chỉ số trang 01 / 03.
     Bộ lọc dùng hook của shared/campus-base.js (data-sbut-news-filter, data-sbut-news-list, data-sbut-row-kind); phân trang: js/screens/bandean-v2/theme.js. --}}
@php
    $newsItems = $homeNews->take(6);
    $eventItems = $homeEvents->take(3);
@endphp
<section class="section bd2-list bd2-news" id="tin-tuc" data-sbut-group="news">
    <div class="wrap" data-stagger-group=":scope > .bd2-list-head > *, :scope > .bd2-list-intro, :scope > .bd2-pills, .bd2-carousel">
        <div class="bd2-list-head">
            <div>
                <div class="bd2-list-eyebrow">{{ __('frontend.ui.nhip_song_dai_hoc') }}</div>
                <h2 class="bd2-list-title">{{ __('frontend.ui.tin_tuc_su_kien_noi_bat') }}</h2>
            </div>
            <a class="bd2-list-all" href="{{ $newsUrl }}"><span>{{ __('frontend.ui.xem_tat_ca') }}</span></a>
        </div>
        <p class="bd2-list-intro">{{ __('frontend.ui.theo_doi_cac_hoat_dong_cau_chuyen_va_su_kien_tro') }}</p>
        <div class="bd2-pills" aria-label="{{ __('frontend.ui.loc_tin_tuc_va_su_kien_noi_bat') }}">
            <button type="button" class="bd2-pill active" data-sbut-news-filter="all" aria-pressed="true">{{ __('frontend.ui.tat_ca') }}</button>
            <button type="button" class="bd2-pill" data-sbut-news-filter="news" aria-pressed="false">{{ __('frontend.ui.tin_tuc') }}</button>
            <button type="button" class="bd2-pill" data-sbut-news-filter="event" aria-pressed="false">{{ __('frontend.ui.su_kien') }}</button>
        </div>
        <div class="bd2-carousel" data-bd2-carousel>
            <ul class="bd2-news-track" data-sbut-news-list data-bd2-track aria-label="{{ __('frontend.ui.danh_sach_tin_tuc_va_su_kien_noi_bat') }}">
            @foreach($newsItems->map(fn ($story) => ['story' => $story, 'kind' => 'news', 'label' => __('pages.tin_tuc'), 'url' => route('cms.tin-tuc.show', $story->slug)])->concat($eventItems->map(fn ($story) => ['story' => $story, 'kind' => 'event', 'label' => __('pages.su_kien'), 'url' => route('cms.su-kien.chi-tiet', $story->slug)])) as $row)
                @php($story = $row['story'])
                @php($image = trim((string) $story->getFirstMediaUrl('avatars')))
                <li class="bd2-news-item" data-sbut-row-kind="{{ $row['kind'] }}">
                    <a class="bd2-news-card" href="{{ $row['url'] }}">
                        <span class="bd2-news-thumb">@if($image !== '')<img src="{{ $image }}" alt="" width="640" height="360" loading="lazy">@else<i class="ri-newspaper-line" aria-hidden="true"></i>@endif</span>
                        <span class="bd2-news-body">
                            <span class="bd2-card-meta"><span class="bd2-card-tag">{{ $row['label'] }}</span>@if($story->published_at)<time datetime="{{ \Carbon\Carbon::parse($story->published_at)->format('Y-m-d') }}">{{ \Carbon\Carbon::parse($story->published_at)->format('d/m/Y') }}</time>@endif</span>
                            <h3>{{ $story->title }}</h3>
                            @if(filled($story->excerpt))<span class="bd2-card-text">{{ \Illuminate\Support\Str::limit(strip_tags($story->excerpt), 200) }}</span>@endif
                            <span class="bd2-card-action">{{ __('frontend.ui.xem_chi_tiet') }}</span>
                        </span>
                    </a>
                </li>
            @endforeach
            </ul>
            <p class="bd2-empty" data-sbut-news-empty hidden>{{ __('frontend.ui.chua_co_noi_dung_duoc_cong_bo') }}</p>
            <div class="bd2-pager" data-bd2-pager>
                <span class="bd2-pager-index"><b data-bd2-page>01</b><span>/ <span data-bd2-pages>01</span></span></span>
                <span class="bd2-pager-bars" data-bd2-bars aria-hidden="true"></span>
                <span class="bd2-pager-arrows"><button type="button" class="bd2-chev bd2-chev--left" data-bd2-move="-1" aria-label="{{ __('frontend.ui.banner_truoc') }}"></button><button type="button" class="bd2-chev" data-bd2-move="1" aria-label="{{ __('frontend.ui.banner_tiep_theo') }}"></button></span>
            </div>
        </div>
    </div>
</section>
