@extends('cmsbackend::screens.components.layouts.post-layout.create_edit_layout', ['resource' => 'cms.dashboard.cdn.thu-vien-hinh-anh'])


@section('_content')
    <!-- Chọn hình ảnh từ Laravel File Manager -->
    <div class="card-header align-items-center">
        <button type="button" id="lfm" data-input="thumbnailImage" data-preview="holder" class="btn btn-primary">
            <i class="ri-image-add-fill"></i> {{ __('admin.ui.chon_anh') }}</button>
        <input id="thumbnailImage" name="images" class="form-control" type="hidden" name="filepath"
            value="{{ old('images') }}">
        <x-input-error :messages="$errors->get('images')" class="mt-2" />
        <img id="holder" style="margin-top:15px;max-height:100px;">

    </div>

    <div class="card-body">
        <h4 class="mb-5">{{ __('admin.ui.danh_sach_hinh_anh') }}</h4>

        <div class="row" id="image-preview-container">
        
        </div>
    </div>
@endsection

@section('_infos') 
    <div class="row mb-2">
        <div class="col-lg-4 col-md-12 col-sm-12">
            <b>{{ __('admin.ui.hien_thi_o_trang_chu_2') }}</b>

        </div>
        <div class="col-lg-8 col-md-12 col-sm-12">
            <label class="switch switch-success"> 
                    <input type="checkbox" name="show_home_page" value="1" class="switch-input"
                    {{ old('show_home_page') !== null ? 'checked' : ($post->show_home_page == '1' ? 'checked' : '') }} />


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
{{$post->post_type}}
    {{-- Nội dung hiển thị ở Card gốc dưới bên phải --}}
    @if ($post->post_type === App\Enums\PostType::IMAGE->value || $post->post_type === App\Enums\PostType::PAGE->value)
        <div class="accordion-item active" style="color:black;">
            <h2 class="accordion-header d-flex align-items-center">
                <button type="button" class="accordion-button accordion-header-font-size" data-bs-toggle="collapse"
                    data-bs-target="#accordionWithIcon-1" aria-expanded="true">
                    <i class="ri-settings-3-fill ri-20px me-2"></i>
                    {{ __('admin.ui.thuoc_tinh') }}
                </button>
            </h2>
            <div id="accordionWithIcon-1" class="accordion-collapse collapse show">
                <div class="accordion-body " style="color:black;">
                    <div class="form-floating form-floating-outline mt-5 trang-cha-select">
                        <select id="selectTrangCha" class="select2 form-select form-select-lg" name="post_parent"
                            data-allow-clear="true">
                            <option value> {{ __('admin.ui.khong_co_trang_cha') }}</option>
                            {!! $pageOptions !!}
                        </select>
                        <label style="color:black;" for="selectTrangCha">
                            @if ($post->post_type === App\Enums\PostType::PAGE->value)
                                {{ __('Trang cha') }}
                            @else
                                {{ __('admin.ui.thu_vien_cha') }}
                            @endif
                        </label>
                    </div>
                    <div class="form-floating form-floating-outline mt-5">
                        <input class="form-control" name="order" type="number" value="{{ old('order') ?? $post->order }}"
                            placeholder="0" id="html5-number-input" /> 
                        <label for="html5-number-input">{{ __('admin.ui.sap_xep') }}</label>
                    </div>


                </div>
            </div>
        </div>
    @endif
@endsection
