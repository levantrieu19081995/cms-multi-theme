@php
    use App\Enums\PostType;
    if (!isset($banner)) {
        $banner = (object) [
            'description' => '',
            'btn_text' => '',
            'show_text' => 1,
        ];
    }
    $showBannerText = (bool) old('show_text', $banner->show_text ?? 1);

@endphp
{{-- layout create/edit dùng chung cho tin tức, bài viết, banner, sự kiện,..... --}}
@extends('cmsbackend::screens.components.layouts.post-layout.create_edit_layout', ['resource' => 'cms.dashboard.cdn.banner'])

@section('_excerpt')
    {{-- Nội dung hiển thị Card gốc trên bên trái, dưới URL --}}
@endsection

@section('_content')
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0 card-title">{{ __('admin.ui.thong_tin_banner') }}</h5>
        {{-- Nút chọn lấy banner từ bài viết hay tự tạo đường dẫn (slug) --}}
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="btn-banner-redirect-link" name="redirect" value="1"
                @checked($banner->redirect ?? '')>
            <label class="form-check-label" for="btn-banner-redirect-link">{{ __('admin.ui.link_den_bai_viet') }}</label>
        </div>
    </div>
    <div class="card-body">
        <div class="mb-6">
            {{-- Công tắc hiện chữ: tắt thì trang chủ chỉ hiện hình, tiêu đề/mô tả/nút được để trống. Ô ẩn bảo đảm giá trị 0 được gửi khi tắt. --}}
            <div class="form-check form-switch mb-1">
                <input type="hidden" name="show_text" value="0">
                <input class="form-check-input" type="checkbox" id="banner-show-text" name="show_text" value="1" @checked($showBannerText)>
                <label class="form-check-label fw-semibold" for="banner-show-text">{{ __('admin.ui.hien_chu_tren_banner') }}</label>
            </div>
            <div class="form-text mb-6">{{ __('admin.ui.hien_chu_tren_banner_mo_ta') }}</div>
            <div id="redirect-link" style="display: none;">
                <div class="form-floating form-floating-outline mb-6">
                    <x-input-post-select label="" data-default="{{ $title ?? null }}" id="banner-redirect-link"
                        name="post_title" callback="actionSelectPost" displayMode="input" placeholder="" type="post"
                        value="{{ $title ?? null }}" />
                    <label for="banner-redirect-link">{{ __('admin.ui.link_den_bai_viet') }}</label>
                </div>
            </div>
            <div class="form-floating form-floating-outline mb-6">
                <input type="text" id="banner-description" name="description" class="form-control"
                    value="{{ old('description') ?? ($banner->description ?? '') }}" placeholder="{{ __('admin.ui.nhap_vao_thong_tin_mo_ta') }}">
                <label for="description">{{ __('admin.ui.thong_tin_mo_ta') }}</label>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div class="form-floating form-floating-outline mb-6">
                <input type="text" id="banner-btn-text" name="btn_text" class="form-control"
                    value="{{ old('btn_text') ?? ($banner->btn_text ?? '') }}" placeholder="{{ __('admin.ui.nhan_hien_thi_tren_nut') }}">
                <label for="btn_text">{{ __('admin.ui.nhan_nut') }}</label>
                <x-input-error :messages="$errors->get('btn_text')" class="mt-2" />
            </div>

            <div class="form-floating form-floating-outline mb-6">
                <input type="hidden" value="{{ old('ref_id') ?? ($ref_id ?? null) }}" class="form-control" name="ref_id"
                    id="ref_id">
            </div>
        </div>
    </div>

    {{-- <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0 card-title">{{ __('Xem trước giao diện') }}</h5>
        <small class="text-light"><i>* Kích thước ảnh 1200x400px</i></small>
    </div>
    <div class="card-body">
        @include('cmsbackend::screens.pages.banner._partials.live-preview')
    </div> --}}
@endsection

@section('_infos')
    {{-- Nội dung hiển thị ở Card gốc trên bên phải --}}
    <div class="row mb-2">
        <div class="col-lg-4 col-md-12 col-sm-12">
            <b>{{ __('admin.ui.hien_thi_trang_chu') }}</b>
        </div>

        <div class="col-lg-8 col-md-12 col-sm-12">
            <label class="switch switch-success">
                {{-- <input type="checkbox" name="show_home_page" class="switch-input" @checked($post->show_home_page) /> --}}
                <input type="checkbox" name="show_home_page" value="1" class="switch-input"
                    {{ old('show_home_page', $post->show_home_page) == '1' ? 'checked' : '' }} />

                <span class="switch-toggle-slider">
                    <span class="switch-on">
                        <i class="ri-check-line"></i>
                    </span>
                    <span class="switch-off">
                        <i class="ri-close-line"></i>
                    </span>
                </span>
            </label>
        </div>
    </div>
@endsection

@section('_attributes')
    {{-- Nội dung hiển thị ở Card gốc dưới bên phải --}}
    <div class="accordion-item active">
        <h2 class="accordion-header d-flex align-items-center">
            <button type="button" class="accordion-button accordion-header-font-size" data-bs-toggle="collapse"
                data-bs-target="#accordionWithIcon-2" aria-expanded="true">
                <i class="ri-image-fill ri-20px me-2"></i>
                @if ($post->post_type === PostType::BANNER->value)
                    {{ __('Banner') }}
                @else
                    {{ __('admin.ui.anh_dai_dien') }}
                @endif
            </button>
        </h2>
        <div id="accordionWithIcon-2" class="accordion-collapse collapse show">
            <div class="accordion-body">
                {{-- GHI CHÚ HƯỚNG DẪN KÍCH THƯỚC BANNER --}}
                <div class="alert alert-info mb-3" style="font-size: 13px;">
                    <strong>{{ __('admin.ui.goi_y_kich_thuoc_chuan_cho_banner') }}</strong><br>
                    • <b>Rendered size:</b> 1920 × 400 px<br>
                    • <b>Aspect ratio:</b> 24 ∶ 5<br>
                    • <b>{{ __('admin.ui.anh_mau_tham_khao') }}</b>
                    <a href="https://be-better.monamedia.net/wp-content/themes/edura/assets/img/breadcumb_shape_1_1.png"
                       target="_blank">
                        https://be-better.monamedia.net/.../breadcumb_shape_1_1.png
                    </a>
                </div>
                {{-- END GHI CHÚ --}}

                <div class="row">
                    <input id="thumbnail" name="avatar" class="form-control" type="hidden" name="filepath"
                        value="{{ old('avatar') ?? $post->getFirstMediaUrl('avatars') }}">
                    <div id="holder" style="max-height:400px" class="img-avt mb-3 mt-3">
                        <img src="{{ old('avatar') ?? $post->getFirstMediaUrl('avatars') }}">
                    </div>
                    <x-input-error :messages="$errors->get('avatar')" class="mt-2" />
                </div>
                <div class="row">
                    <button type="button" id="lfm" data-input="thumbnail" data-preview="holder"
                        style="border: thin dashed #cfd0d6;height: 35px; width: 100%;">
                        <i class="fa fa-picture-o"></i> {{ __('admin.ui.chon_anh') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('_page-script')
    <script>
        // Khởi tạo nút chọn link đến bài viết
        let btnBannerRedirectLink = document.getElementById('btn-banner-redirect-link');
        if (btnBannerRedirectLink) {
            toggleRedirectLink();
            btnBannerRedirectLink.addEventListener('change', toggleRedirectLink);
        }

        // Công tắc hiện chữ: làm mờ các ô chữ khi tắt (vẫn gửi đi, để giữ lại nội dung nếu bật lại).
        const showTextSwitch = document.getElementById('banner-show-text');
        if (showTextSwitch) {
            const textFields = ['title', 'banner-description', 'banner-btn-text']
                .map(id => document.getElementById(id)?.closest('.form-floating')).filter(Boolean);
            const syncShowText = () => textFields.forEach(el => { el.style.opacity = showTextSwitch.checked ? '' : '.5'; });
            showTextSwitch.addEventListener('change', syncShowText);
            syncShowText();
        }

        // Hiển thị link chọn bài viết
        function toggleRedirectLink() {
            let isChecked = btnBannerRedirectLink.checked;
            const redirectLinkWrapper = document.getElementById('redirect-link');
            const slugInput = document.getElementById('slug');
            const bannerRedirectInput = document.getElementById('banner-redirect-link');
            const refIdInput = document.getElementById('ref_id');

            if (redirectLinkWrapper) {
                redirectLinkWrapper.style.display = isChecked ? 'block' : 'none';
            }

            if (!isChecked) {
                // Tắt liên kết → reset liên kết, nhưng chỉ reset slug nếu nó chưa có giá trị (tức là đang tạo mới)
                if (bannerRedirectInput) bannerRedirectInput.value = '';
                if (refIdInput) refIdInput.value = '';
                if (slugInput) {
                    slugInput.removeAttribute('readonly');
                    slugInput.removeAttribute('data-slug-locked');
                    slugInput.style.color = 'blue';
                    // Nếu slugInput không có giá trị (tạo mới), mới reset
                    if (!slugInput.value) {
                        slugInput.value = '';
                    }
                    // Nếu slugInput đã có giá trị (edit), giữ nguyên
                }
            } else {
                // Bật liên kết → đặt lại slug, khóa lại input
                if (bannerRedirectInput && !bannerRedirectInput.value) {
                    bannerRedirectInput.value = bannerRedirectInput.getAttribute('data-default') || '';
                }
                if (refIdInput && !refIdInput.value) {
                    refIdInput.value = refIdInput.getAttribute('data-default') || '';
                }
                if (slugInput && bannerRedirectInput && bannerRedirectInput.value) {
                    slugInput.value = slugInput.getAttribute('data-default') || '';
                    slugInput.setAttribute('readonly', true);
                    slugInput.setAttribute('data-slug-locked', '1');
                }
            }
        }

        // Hàm xử lý khi chọn bài viết từ modal
        window.actionSelectPost = (res) => {
            // console.log('[]ress', res);
            document.getElementById('banner-redirect-link').value = res.title;
            // document.getElementById('ref_url').value = res.slug;
            document.getElementById('ref_id').value = res.id;
            const slugInput = document.getElementById('slug');
            if (slugInput) {
                const fullUrl = window.location.origin + '/' + res.slug.replace(/^\/+/, '');
                slugInput.value = fullUrl;
                slugInput.readOnly = true;
                slugInput.setAttribute('data-slug-locked', '1');
                slugInput.style.color = 'gray';
                getChiTietBaiViet(res.slug);

            }
        }

        function getChiTietBaiViet(postSlug) {
            fetch(`/api/bai-viet/getChiTietBaiViet/${postSlug}`)
                .then(response => response.json())
                .then(data => {
                    if (data.code == 200) {
                        const post = data.data;
                        const fallbackImage = '/assets/img/khuyen-nong-can-tho/banner/1200x400.png';
                        // console.log('Em xinh say Hi', post.media?.length == 0);
                        if (post.media?.length == 0) {
                            toastr.warning(@json(__('admin.js.khong_the_lay_hinh_anh_tu_bai_viet')), @json(__('admin.js.luu_y')));
                        }
                        const imageUrl = post.media?.[0]?.original_url || fallbackImage;

                        // Gán vào hidden input để updateBannerPreview xử lý
                        document.getElementById('thumbnail').value = imageUrl;

                        // Gọi lại update (đã xử lý preview-img và backgroundImage bên trong)
                        updateBannerPreview();
                    }
                })
                .catch(error => console.error(@json(__('admin.js.loi_khi_tai_chi_tiet_bai_viet')), error));
        }

        function updateBannerPreview() {
            const title = document.getElementById('title')?.value || @json(__('admin.ui.tieu_de'));
            const description = document.getElementById('banner-description')?.value || @json(__('admin.ui.thong_tin_mo_ta'));
            const btnText = document.getElementById('banner-btn-text')?.value || @json(__('admin.ui.nhan_nut'));
            const imageUrl = document.getElementById('thumbnail')?.value || '/assets/img/khuyen-nong-can-tho/banner/1200x400.png';

            document.getElementById('preview-title').textContent = title;
            document.getElementById('preview-description').textContent = description;
            document.getElementById('preview-btn').innerHTML =
                `${btnText} <i class="fa fa-arrow-right mx-2" aria-hidden="true"></i>`;

            document.getElementById('preview-img').src = imageUrl;
            document.getElementById('preview-img').onerror = () => {
                document.getElementById('preview-img').src = '/assets/img/khuyen-nong-can-tho/banner/1200x400.png';
            };
            document.getElementById('banner-bg').style.backgroundImage = `url('${imageUrl}')`;
            document.getElementById('banner-bg').style.backgroundImage = `url('${imageUrl}')`;
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Gọi update ngay lần đầu nếu đã có dữ liệu
            //updateBannerPreview();
            // toggleRedirectLink();

            // // Gắn các sự kiện input
            // document.getElementById('title')?.addEventListener('input', updateBannerPreview);
            // document.getElementById('banner-description')?.addEventListener('input', updateBannerPreview);
            // document.getElementById('banner-btn-text')?.addEventListener('input', updateBannerPreview);

            // // Theo dõi khi input hidden #thumbnail bị thay đổi (do chọn ảnh)
            // const thumbnailInput = document.getElementById('thumbnail');
            // if (thumbnailInput) {
            //     const observer = new MutationObserver(() => {
            //         updateBannerPreview();
            //     });

            //     observer.observe(thumbnailInput, {
            //         attributes: true,
            //         attributeFilter: ['value'] // chỉ khi value thay đổi
            //     });

            //     // Trường hợp fallback: gắn thêm sự kiện "change"
            //     thumbnailInput.addEventListener('change', updateBannerPreview);
            // }
        });
    </script>
@endsection
