{{-- Cụm giới thiệu của bandean-v2 (thiết kế bannerSBUT): ảnh campus có các biểu tượng nổi ở bên trái, chữ và nút ở bên phải.
     Dữ liệu như bản dùng chung (screens/_campus): bài đầu của chuyên mục "gioi-thieu" nếu có, nếu không thì dòng giới thiệu mặc định. --}}
@php($introduction = ($homeSections['introduction']['items'] ?? collect())->first())
<section class="section bd2-intro" id="gioi-thieu">
    <div class="wrap bd2-intro-layout">
        <figure class="bd2-intro-figure" data-stagger-group>
            <img src="{{ asset('assets/img/themes/bandean-v2/intro-campus.webp') }}" alt="{{ __('frontend.ui.phoi_canh_kien_truc_campus_nbdut') }}" width="1100" height="659" loading="lazy">
            <figcaption>{{ __('frontend.ui.bd2_chu_thich_anh') }}</figcaption>
        </figure>
        <div class="bd2-intro-copy" data-stagger-group data-stagger-offset=".25">
            <div class="bd2-intro-eyebrow">{{ __('frontend.ui.chao_mung_den_voi_nbdut') }}</div>
            {{-- Không dùng data-reveal-lines: tách dòng bằng <br> làm dòng thứ hai kẹt ở trạng thái mờ; hiệu ứng hiện dần do khung (data-stagger-group) lo. --}}
            <h2 class="bd2-intro-title"><span>{{ __('frontend.ui.mot_dai_hoc_cong_nghe') }}</span> <span>{{ __('frontend.ui.nhieu_canh_cua_tuong_lai') }}</span></h2>
            <p class="bd2-intro-text">{{ $introduction ? \Illuminate\Support\Str::limit(strip_tags($introduction->excerpt ?: $introduction->content), 500) : __('frontend.ui.bd2_gioi_thieu_mac_dinh') }}</p>
            <ul class="bd2-intro-pills"><li>{{ __('frontend.ui.bd2_tri_thuc') }}</li><li>{{ __('frontend.ui.uu_tu') }}</li><li>{{ __('frontend.ui.cong_hien') }}</li></ul>
            @if($introduction)
                <a class="bd2-intro-cta" href="{{ route('cms.tin-tuc.show', $introduction->slug) }}"><span>{{ __('frontend.ui.kham_pha_ve_nbdut') }}</span></a>
            @elseif (app(\Modules\CmsBackend\Features\FeatureRegistry::class)->isEnabled('contact'))
                <a class="bd2-intro-cta" href="{{ route('cms.lien-he') }}"><span>{{ __('frontend.ui.ket_noi_cung_nbdut') }}</span></a>
            @endif
        </div>
    </div>
</section>
