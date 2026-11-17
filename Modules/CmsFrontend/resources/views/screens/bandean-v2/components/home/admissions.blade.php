{{-- Cụm "Thông báo tuyển sinh" của bandean-v2 (thiết kế bannerSBUT): bên trái lời dẫn, danh sách ý, nút và ảnh; bên phải bốn thẻ thông báo có ảnh.
     Dữ liệu như bản dùng chung (screens/_campus): $homeSections['admissions'] = bài của chuyên mục "thong-bao-tuyen-sinh" + địa chỉ "Thông tin tuyển sinh".
     Hình tùy chọn (đặt tệp vào public/assets/img/themes/bandean-v2/): admissions-mascot.png (nhân vật cạnh tiêu đề, không có thì bỏ qua) và
     admissions-photo.jpg (ảnh dưới nút; không có thì dùng ảnh campus của banner). --}}
@php
    $admissions = $homeSections['admissions'] ?? ['items' => collect(), 'url' => null];
    $imgDir = 'assets/img/themes/bandean-v2/';
    $mascot = is_file(public_path($imgDir.'admissions-mascot.png')) ? asset($imgDir.'admissions-mascot.png') : null;
    $photo = asset($imgDir.(is_file(public_path($imgDir.'admissions-photo.jpg')) ? 'admissions-photo.jpg' : 'hero-campus.jpg'));
@endphp
<section class="section bd2-adm" id="tuyen-sinh" data-sbut-group="information">
    <div class="wrap bd2-adm-layout">
        <div class="bd2-adm-copy" data-stagger-group>
            <div class="bd2-list-eyebrow">{{ __('frontend.ui.cung_ban_chon_tuong_lai') }}</div>
            <div class="bd2-adm-titlerow">
                <h2 class="bd2-list-title">{{ __('frontend.ui.thong_bao_tuyen_sinh_2') }}</h2>
                @if($mascot)<img class="bd2-adm-mascot" src="{{ $mascot }}" alt="" width="160" height="160" loading="lazy">@endif
            </div>
            <p class="bd2-adm-lead">{{ __('frontend.ui.bd2_tuyen_sinh_dan') }}</p>
            <ul class="bd2-adm-points"><li>{{ __('frontend.ui.bd2_tuyen_sinh_y_1') }}</li><li>{{ __('frontend.ui.bd2_tuyen_sinh_y_2') }}</li><li>{{ __('frontend.ui.bd2_tuyen_sinh_y_3') }}</li></ul>
            @if($admissions['url'])
                <a class="bd2-intro-cta" href="{{ $admissions['url'] }}"><span>{{ __('frontend.ui.thong_tin_tuyen_sinh') }}</span></a>
            @else
                <a class="bd2-intro-cta" href="{{ route('cms.frontend.hoidap.gui') }}"><span>{{ __('frontend.ui.gui_cau_hoi_tuyen_sinh') }}</span></a>
            @endif
            <img class="bd2-adm-photo" src="{{ $photo }}" alt="{{ __('frontend.ui.phoi_canh_campus_nbdut') }}" width="1600" height="900" loading="lazy">
        </div>
        <div class="bd2-adm-list" data-stagger-group data-stagger-offset=".25">
        @forelse($admissions['items'] as $story)
            @php($image = trim((string) $story->getFirstMediaUrl('avatars')))
            <a class="bd2-adm-card" href="{{ route('cms.tin-tuc.show', $story->slug) }}">
                <span class="bd2-adm-num" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                <span class="bd2-adm-thumb">@if($image !== '')<img src="{{ $image }}" alt="" width="320" height="200" loading="lazy">@else<i class="ri-image-line" aria-hidden="true"></i>@endif</span>
                <span class="bd2-adm-body">
                    <span class="bd2-card-meta"><i class="ri-notification-3-line bd2-adm-bell" aria-hidden="true"></i><span class="bd2-card-tag">{{ __('frontend.ui.thong_bao_tuyen_sinh') }}</span>@if($story->published_at)<time datetime="{{ \Carbon\Carbon::parse($story->published_at)->format('Y-m-d') }}">{{ \Carbon\Carbon::parse($story->published_at)->format('d/m/Y') }}</time>@endif</span>
                    <h3>{{ $story->title }}</h3>
                    @if(filled($story->excerpt))<span class="bd2-card-text">{{ \Illuminate\Support\Str::limit(strip_tags($story->excerpt), 180) }}</span>@endif
                </span>
                <span class="bd2-adm-go" aria-hidden="true"><i class="bd2-chev"></i></span>
            </a>
        @empty
            <div class="bd2-empty"><i class="ri-graduation-cap-line" aria-hidden="true"></i><p>{{ __('frontend.ui.thong_bao_tuyen_sinh_dang_duoc_cap_nhat') }}</p></div>
        @endforelse
        </div>
    </div>
</section>
