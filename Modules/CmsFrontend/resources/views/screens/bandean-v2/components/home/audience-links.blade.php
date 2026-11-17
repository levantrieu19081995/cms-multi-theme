@php
    $audiences = [
        'student' => ['id'=>'sinh-vien','title'=>__('pages.danh_cho_sinh_vien'),'eyebrow'=>__('pages.hoc_tap_trai_nghiem'),'description'=>__('pages.nhung_thong_tin_va_dich_vu_can_thiet_cho_hanh_tr'),'links'=>[
            [__('pages.cong_thong_tin_sinh_vien'),__('pages.ho_so_hoc_tap_ket_qua_va_nhung_thong_tin_ca_nhan'),'ri-graduation-cap-line',null],
            [__('pages.lich_hoc_lich_thi'),__('pages.theo_doi_cac_moc_hoc_vu_va_hoat_dong_trong_tuan'),'ri-calendar-line',null],
            [__('pages.thu_vien_hoc_lieu'),__('pages.kham_pha_giao_trinh_va_cac_nguon_hoc_lieu_mo'),'ri-book-open-line',route('cms.frontend.vanban.index')],
            [__('pages.thu_tuc_bieu_mau'),__('pages.tim_huong_dan_va_cac_bieu_mau_danh_cho_nguoi_hoc'),'ri-file-text-line',route('cms.frontend.vanban.index')],
            [__('pages.hoc_bong_ho_tro'),__('pages.thong_tin_ve_chinh_sach_ho_tro_nguoi_hoc'),'ri-heart-line',null],
            [__('pages.cau_lac_bo_hoat_dong'),__('pages.ket_noi_cong_dong_qua_cac_hoat_dong_chung'),'ri-team-line',route('cms.su-kien')],
        ]],
        'faculty' => ['id'=>'giang-vien','title'=>__('pages.danh_cho_giang_vien'),'eyebrow'=>__('pages.giang_day_nghien_cuu'),'description'=>__('pages.cac_cong_cu_ho_tro_cong_tac_chuyen_mon_giang_day'),'links'=>[
            [__('pages.khong_gian_giang_vien'),__('pages.thong_tin_giang_day_va_cac_tien_ich_chuyen_mon'),'ri-team-line',null],
            [__('pages.lich_giang_day'),__('pages.lich_lop_hoc_lich_cong_tac_va_hoat_dong_hoc_thua'),'ri-calendar-line',null],
            [__('pages.ho_so_khoa_hoc'),__('pages.to_chuc_cong_bo_de_tai_va_thong_tin_nghien_cuu'),'ri-flask-line',null],
            [__('pages.hoc_lieu_lop_hoc_so'),__('pages.to_chuc_va_chia_se_tai_nguyen_hoc_tap'),'ri-book-open-line',null],
            [__('pages.van_ban_quy_trinh'),__('pages.thong_tin_quy_trinh_va_bieu_mau_cong_tac'),'ri-file-text-line',route('cms.frontend.vanban.index')],
            [__('pages.de_tai_hop_tac'),__('pages.ket_noi_cac_huong_nghien_cuu_va_nhu_cau_hop_tac'),'ri-global-line',route('cms.trang-chu').'#nghien-cuu'],
        ]],
    ];
@endphp
{{-- Cụm "Liên kết nhanh dành cho sinh viên, giảng viên" của bandean-v2 (thiết kế bannerSBUT): hai thẻ trắng, mỗi thẻ có ô biểu tượng, tên nhóm, nhãn "Tiện ích",
     lưới liên kết hai cột (viền xanh, nút mũi tên tròn) và nút "Cần hỗ trợ? Gửi câu hỏi". Danh sách liên kết và địa chỉ như bản dùng chung (screens/_campus):
     địa chỉ cấu hình ở audience_links của theme; liên kết chưa có địa chỉ hiện "Đang cập nhật". --}}
<section class="section bd2-aud" id="lien-ket-nhanh" aria-labelledby="quickLinksHeading">
    <div class="wrap">
        <div class="bd2-aud-head" data-stagger-group>
            <div class="bd2-list-eyebrow">{{ __('frontend.ui.ket_noi_dung_dieu_ban_can') }}</div>
            <h2 class="bd2-list-title" id="quickLinksHeading">{{ __('frontend.ui.lien_ket_nhanh_danh_cho_sinh_vien_giang_vien') }}</h2>
            <p class="bd2-list-intro">{{ __('frontend.ui.tat_ca_tien_ich_danh_cho_sinh_vien_va_giang_vien') }}</p>
        </div>
        <div class="bd2-aud-groups">
        @foreach($audiences as $key => $audience)
            <section class="bd2-aud-card" data-stagger-group=".bd2-aud-top, .bd2-aud-link, .bd2-aud-help" id="{{ $audience['id'] }}" aria-labelledby="{{ $key }}Heading">
                <div class="bd2-aud-top">
                    <span class="bd2-aud-icon"><i class="ri-{{ $key === 'student' ? 'graduation-cap' : 'team' }}-line" aria-hidden="true"></i></span>
                    <div><span class="bd2-aud-eyebrow">{{ $audience['eyebrow'] }}</span><h3 id="{{ $key }}Heading">{{ $audience['title'] }}</h3></div>
                    <span class="bd2-aud-badge">{{ __('frontend.ui.bd2_tien_ich') }}</span>
                </div>
                <div class="bd2-aud-links">
                @foreach($audience['links'] as $index => [$label, $description, $icon, $defaultUrl])
                    @php
                        $url = config('cmsbackend.screens.themes.bandean-v2.audience_links.'.$key.'.'.$index, $defaultUrl);
                        $url = is_string($url) && preg_match('~^(https?://|/)~i', $url) ? $url : null;
                    @endphp
                    @if($url)
                        <a class="bd2-aud-link" href="{{ $url }}"><i class="{{ $icon }} bd2-aud-link-icon" aria-hidden="true"></i><span><strong>{{ $label }}</strong><small>{{ $description }}</small></span><i class="bd2-chev bd2-aud-go" aria-hidden="true"></i></a>
                    @else
                        <div class="bd2-aud-link is-pending" aria-disabled="true"><i class="{{ $icon }} bd2-aud-link-icon" aria-hidden="true"></i><span><strong>{{ $label }}</strong><small>{{ __('frontend.ui.dang_cap_nhat') }}</small></span><i class="bd2-chev bd2-aud-go" aria-hidden="true"></i></div>
                    @endif
                @endforeach
                </div>
                <a class="bd2-aud-help" href="{{ route('cms.frontend.hoidap.gui') }}">{{ __('frontend.ui.can_ho_tro_gui_cau_hoi') }}</a>
            </section>
        @endforeach
        </div>
    </div>
</section>
