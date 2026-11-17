{{-- Cụm "Đối tác và đơn vị liên kết" của bandean-v2. Có HAI giao diện danh sách, chuyển bằng cặp nút ở góc trên phải (nhớ lựa chọn của người xem trong localStorage):
       - panel "carousel": băng thẻ coverflow trên nền kem (thẻ giữa to, có nút mũi tên; hai thẻ mỗi bên nhỏ dần, phẳng, lùi ra sau). Dùng lại cơ chế chuyển thẻ của cụm Video
         (js/screens/bandean-v2/theme.js, data-bd2-flow): kéo, nút, chấm, phím ←/→, tự chuyển; bấm thẻ bên cạnh = đưa vào giữa, bấm thẻ giữa = mở trang đối tác.
       - panel "grid": sơ đồ "kết nối" — vòng tròn KẾT NỐI ở giữa, lõi SBUT ở giữa, các đối tác (tối đa 8) chia đều sang hai bên, nối bằng đường bẻ góc vuông; nút đang sáng tự chạy vòng (theme.js, data-bd2-hub),
         trỏ chuột/focus vào nút nào thì nút đó sáng. Ở giao diện này tiêu đề chung của cụm bị ẩn (CSS theo section[data-bd2-view="grid"]), thay bằng cột chữ bên trái; trên điện thoại sơ đồ xếp thành lưới 2 cột.
     Thêm giao diện thứ ba = thêm một nút data-bd2-view-btn="<tên>" và một panel data-bd2-view-panel="<tên>" (hidden); logic chuyển nằm ở cuối theme.js (data-bd2-views).
     Dữ liệu: $links (đối tác hiển thị trên trang chủ: title, excerpt, ảnh 'avatars', url hoặc slug). CSS: sections.css (.bd2-partners, .bd2-pcard, .bd2-hub, .bd2-pt-switch). --}}
@php $partners = collect($links ?? [])->take(8)->values(); @endphp
@php $partnerCount = $partners->count(); @endphp
{{-- Sơ đồ kết nối: lõi SBUT ở giữa, các đối tác chia đều sang HAI BÊN (lẻ = cột trái, chẵn = cột phải, mỗi hàng một cặp; nhiều thì tự thêm hàng xuống dưới). Hàng chẵn của mỗi cột thụt vào 34px
     để hai cột nhìn như cánh cung. Đường nối bẻ góc vuông do theme.js vẽ (đo vị trí thật, vẽ lại khi đổi kích thước) vào <svg class="bd2-hub-lines">; mỗi <path> ứng với một nút cùng thứ tự. --}}
@php
    $hubPartners = $partners->values();
    $hubRows = max(1, (int) ceil($hubPartners->count() / 2));
    $hubIcons = ['ri-graduation-cap-line', 'ri-planet-line', 'ri-building-2-line', 'ri-global-line', 'ri-cpu-line', 'ri-team-line'];
    $hubContact = app(\Modules\CmsBackend\Features\FeatureRegistry::class)->isEnabled('contact');
@endphp
<section class="section bd2-partners" id="doi-tac" aria-labelledby="partnersHeading" data-sbut-group="partners-support" data-bd2-view="carousel">
    <div class="wrap" data-stagger-group=":scope > .bd2-pt-head > *, :scope > .bd2-pt-views">
        @if($partnerCount > 0)
        <div class="bd2-pt-switch" role="group" aria-label="{{ __('frontend.ui.doi_tac_chon_giao_dien') }}">
            <button type="button" class="bd2-pt-view-btn is-active" data-bd2-view-btn="carousel" aria-pressed="true" title="{{ __('frontend.ui.doi_tac_dang_bang_the') }}" aria-label="{{ __('frontend.ui.doi_tac_dang_bang_the') }}"><i class="ri-gallery-view-2" aria-hidden="true"></i></button>
            <button type="button" class="bd2-pt-view-btn" data-bd2-view-btn="grid" aria-pressed="false" title="{{ __('frontend.ui.doi_tac_dang_luoi') }}" aria-label="{{ __('frontend.ui.doi_tac_dang_luoi') }}"><i class="ri-layout-grid-line" aria-hidden="true"></i></button>
        </div>
        @endif
        <div class="bd2-pt-head">
            <div class="bd2-pt-eyebrow">{{ __('frontend.ui.ket_noi_de_cung_tien_xa') }}</div>
            <h2 class="bd2-pt-title" id="partnersHeading"><span>{{ __('frontend.ui.doi_tac_va_don_vi_lien_ket') }}</span><svg class="bd2-pt-swoosh" viewBox="0 0 260 14" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="M3 9 C 55 3, 150 2, 257 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg></h2>
            <p class="bd2-pt-intro">{{ __('frontend.ui.doi_tac_mang_luoi_hop_tac') }}</p>
        </div>
        @if($partnerCount > 0)
        <div class="bd2-pt-views" data-bd2-views data-bd2-views-key="bd2-partners-view">
            <div class="bd2-pt-panel" data-bd2-view-panel="carousel">
                <div class="bd2-flow bd2-flow--partners" data-bd2-flow data-bd2-flow-autoplay="6000" aria-roledescription="carousel" aria-label="{{ __('frontend.ui.danh_sach_doi_tac_lien_ket') }}">
                    <div class="bd2-flow-stage" data-bd2-flow-stage>
                    @foreach($partners as $partner)
                        @php $partnerImage = trim((string) $partner->getFirstMediaUrl('avatars')); @endphp
                        @php $partnerNote = filled($partner->excerpt) ? \Illuminate\Support\Str::limit(trim(strip_tags((string) $partner->excerpt)), 70) : ''; @endphp
                        <a class="bd2-flow-card bd2-pcard" data-bd2-flow-item href="{{ $partner->url ?? route('cms.doi-tac.show', $partner->slug) }}" aria-label="{{ $partner->title }}">
                            <span class="bd2-pcard-top">
                                <span class="bd2-pcard-no">{{ __('frontend.ui.doi_tac') }} · {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="bd2-pcard-go bd2-chev" aria-hidden="true"></span>
                            </span>
                            <span class="bd2-pcard-main">
                                <span class="bd2-pcard-logo">@if($partnerImage !== '')<img src="{{ $partnerImage }}" alt="" width="160" height="160" loading="{{ $loop->first ? 'eager' : 'lazy' }}">@else<i class="ri-building-line" aria-hidden="true"></i>@endif</span>
                                <strong class="bd2-pcard-name">{{ $partner->title }}</strong>
                            </span>
                            <span class="bd2-pcard-foot">
                                <span class="bd2-pcard-note">{{ $partnerNote }}</span>
                                <span class="bd2-pcard-pill">{{ __('frontend.ui.xem_chi_tiet') }}</span>
                            </span>
                        </a>
                    @endforeach
                    </div>
                    <div class="bd2-flow-pager" data-bd2-flow-pager @if($partnerCount < 2) hidden @endif>
                        <button type="button" class="bd2-pt-arrow bd2-chev bd2-chev--left" data-bd2-flow-move="-1" aria-label="{{ __('frontend.ui.banner_truoc') }}"></button>
                        <span class="bd2-flow-index"><b data-bd2-flow-page>01</b><span>/ {{ str_pad((string) $partnerCount, 2, '0', STR_PAD_LEFT) }}</span></span>
                        <button type="button" class="bd2-pt-arrow bd2-chev" data-bd2-flow-move="1" aria-label="{{ __('frontend.ui.banner_tiep_theo') }}"></button>
                        <span class="bd2-flow-dots" data-bd2-flow-dots role="group" aria-label="{{ __('frontend.ui.danh_sach_doi_tac_lien_ket') }}"></span>
                    </div>
                </div>
            </div>

            {{-- Giao diện thứ hai: bố cục hai cột (từ 1240px): cột trái là lời giới thiệu + nút liên hệ, cột phải là khung xanh có sơ đồ kết nối. Dưới 1240px xếp chồng. --}}
            <div class="bd2-pt-panel" data-bd2-view-panel="grid" hidden>
                <div class="bd2-hubwrap">
                    <div class="bd2-hubcopy">
                        <div class="bd2-pt-eyebrow">{{ __('frontend.ui.ket_noi_de_cung_tien_xa') }}</div>
                        <div class="bd2-hubcopy-main">
                            <h2 class="bd2-list-title bd2-hubcopy-title">{!! preg_replace(['/&amp;/', '/\.$/'], ['<span class="bd2-hubcopy-accent">&amp;</span>', '<span class="bd2-hubcopy-accent">.</span>'], e(__('frontend.ui.doi_tac_hub_tieu_de'))) !!}</h2>
                            <p class="bd2-hubcopy-sub">{{ __('frontend.ui.doi_tac_hub_phu_de') }}</p>
                            <p class="bd2-hubcopy-text">{{ __('frontend.ui.doi_tac_hub_mo_ta') }}</p>
                        </div>
                        @if($hubContact)
                            <div class="bd2-hubcopy-actions">
                                <a class="bd2-hubcopy-btn" href="{{ route('cms.lien-he') }}"><span>{{ __('frontend.ui.doi_tac_hub_lien_he') }}</span><i class="ri-arrow-right-line" aria-hidden="true"></i></a>
                            </div>
                        @endif
                    </div>
                    <div class="bd2-hub" data-bd2-hub role="group" aria-label="{{ __('frontend.ui.danh_sach_doi_tac_lien_ket') }}">
                        <div class="bd2-hub-body" style="--rows: {{ $hubRows }}">
                            <svg class="bd2-hub-lines" aria-hidden="true" focusable="false">@foreach($hubPartners as $unused)<path data-bd2-hub-line d=""/>@endforeach</svg>
                            <div class="bd2-hub-core" aria-hidden="true">
                                <strong>{{ __('frontend.ui.doi_tac_hub_ma') }}</strong>
                                <small>{{ __('frontend.ui.doi_tac_hub_ten_truong') }}</small>
                            </div>
                            @foreach($hubPartners as $i => $partner)
                                @php $partnerImage = trim((string) $partner->getFirstMediaUrl('avatars')); @endphp
                                <a class="bd2-hub-node {{ $i % 2 === 0 ? 'bd2-hub-node--l' : 'bd2-hub-node--r' }} @if(intdiv($i, 2) % 2 === 0) is-inset @endif" data-bd2-hub-node style="--c: {{ $i % 2 === 0 ? 1 : 3 }}; --r: {{ intdiv($i, 2) + 1 }}" href="{{ $partner->url ?? route('cms.doi-tac.show', $partner->slug) }}" title="{{ $partner->title }}">
                                    <span class="bd2-hub-no">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="bd2-hub-ico">@if($partnerImage !== '')<img src="{{ $partnerImage }}" alt="" width="96" height="96" loading="lazy">@else<i class="{{ $hubIcons[$i % count($hubIcons)] }}" aria-hidden="true"></i>@endif</span>
                                    <span class="bd2-hub-label">{{ $partner->title }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @else
            <p class="bd2-empty">{{ __('frontend.ui.thong_tin_doi_tac_dang_duoc_cap_nhat') }}</p>
        @endif
    </div>
</section>
