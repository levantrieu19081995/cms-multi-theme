@extends('cmsbackend::screens.components.layouts.post-layout.create_edit_layout', ['resource' => 'cms.dashboard.cdn.bai-viet'])
@php
  // Ưu tiên giá trị người dùng vừa submit
  $selectedCatIds = collect(
    old('category_ids', $post->taxonomies->pluck('term_taxonomy_id')->all())
  )->map(fn($v) => (int)$v);
@endphp

@section('_excerpt')
    @if ($post->post_type === App\Enums\PostType::POST->value)
        <div class="form-floating form-floating-outline mb-5">
            <textarea class="form-control h-px-100" id="excerpt" placeholder="{{ __('admin.ui.trich_dan') }}" name="excerpt">{{ old('excerpt') ?? $post->excerpt }}</textarea>
            <label for="excerpt">{{ __('admin.ui.trich_dan') }}</label>
            <x-input-error :messages="$errors->get('excerpt')" class="mb-5" />
        </div>
        
    @endif
    @if ($post->post_type === App\Enums\PostType::POST->value)
        <div class="form-floating form-floating-outline mb-5">
            <textarea class="form-control h-px-100" id="author_names" placeholder="{{ __('admin.ui.tac_gia') }}" name="author_names">{{ old('author_names') ?? $post->author_names }}</textarea>
            <label for="author_names">{{ __('admin.ui.tac_gia') }}</label>
            <x-input-error :messages="$errors->get('author_names')" class="mb-5" />
        </div>
        
    @endif
@endsection

@section('_content')
    <div class="card-header d-flex justify-content-between align-items-center">
        @if ($post->post_type === App\Enums\PostType::POST->value)
            <h5 class="mb-0 card-title">{{ __('admin.ui.noi_dung_bai_viet') }}</h5>
        @else
            <h5 class="mb-0 card-title">{{ __('admin.ui.noi_dung_trang') }}</h5>
        @endif
    </div>
    <div class="card-body">
        <textarea name="content" id="ckeditor5">{{ old('content') ?? $post->content }}</textarea>
        <h5 class="mb-0 card-title mt-2">{{ __('admin.ui.tap_tin_dinh_kem') }}</h5>
    <div>
        @php
            $val = html_entity_decode(old('fileDinhKem') ?? $post->fileDinhKem);
            $btnname = __('admin.ui.dinh_kem_hinh_anh');
        @endphp
        <x-input-file-select id="file_multiple"   name="fileDinhKem"
            :value=$val :multiple="true">

        </x-input-file-select>
    </div>
    </div>

@endsection


@section('_infos')
    <div class="row mb-2">
        <div class="col-lg-4 col-md-12 col-sm-12">
            <b>{{ __('admin.ui.hien_thi_o_trang_chu_2') }}</b>
        </div>
        <div class="col-lg-8 col-md-12 col-sm-12"> 
            <input type="hidden" name="show_home_page" value="0"> {{-- mặc định tắt nếu không check --}}
            <label class="switch switch-success">
            <input
                type="checkbox"
                name="show_home_page"
                class="switch-input"
                value="1"
                {{ old('show_home_page', (int) $post->show_home_page) == 1 ? 'checked' : '' }}
            />
            <span class="switch-toggle-slider">
                <span class="switch-on"><i class="ri-check-line"></i></span>
                <span class="switch-off"><i class="ri-close-line"></i></span>
            </span>
            </label>
        </div>
    </div>

    {{-- Cho phép bình luận --}}
    <div class="row mb-2">
        <div class="col-lg-4 col-md-12 col-sm-12">
            <b>{{ __('admin.ui.cho_phep_binh_luan_2') }}</b>
        </div>
        <div class="col-lg-8 col-md-12 col-sm-12"> 
        <input type="hidden" name="comment_status" value="disabled"> {{-- mặc định tắt --}}
        <label class="switch switch-success">
        <input
            type="checkbox"
            name="comment_status"
            class="switch-input-comment"
            value="published"
            {{ old('comment_status', $post->comment_status) === 'published' ? 'checked' : '' }}
        />
        <span class="switch-toggle-slider">
            <span class="switch-on"><i class="ri-check-line"></i></span>
            <span class="switch-off"><i class="ri-close-line"></i></span>
        </span>
        </label>
        </div>
    </div>
@endsection
{{-- 
@section('page-script')
    @parent
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // Lắng nghe mọi thay đổi checkbox
            document.querySelectorAll('.form-check-input').forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    const isChild = this.closest('.child-checkboxes');
                    const parentDiv = isChild ? isChild.previousElementSibling : this.parentElement;
                    const parentCheckbox = isChild ? parentDiv.querySelector('input[type="checkbox"]') : null;
                    const childWrapper = this.closest('.child-checkboxes');

                    if (!isChild) {
                        // Nếu là cha → check/bo toàn bộ con
                        const childrenContainer = this.parentElement.nextElementSibling;
                        if (childrenContainer && childrenContainer.classList.contains('child-checkboxes')) {
                            const children = childrenContainer.querySelectorAll('input[type="checkbox"]');
                            children.forEach(child => child.checked = this.checked);
                        }
                    } else {
                        // Nếu là con → xử lý cha
                        const siblings = childWrapper.querySelectorAll('input[type="checkbox"]');
                        const anyChecked = Array.from(siblings).some(cb => cb.checked);
                        if (parentCheckbox) parentCheckbox.checked = anyChecked;
                    }
                });
            });
        });
    </script>
    <script> 
        document.addEventListener('DOMContentLoaded', function () { 
        // Khởi tạo: nếu có child nào checked => bật checkbox cha
        document.querySelectorAll('.child-checkboxes').forEach(group => {
            const anyChecked = group.querySelectorAll('input.form-check-input:checked').length > 0;
            const parentCheckbox = group.previousElementSibling?.querySelector('input.form-check-input');
            if (anyChecked && parentCheckbox) parentCheckbox.checked = true;
        });
        }); 

    </script>
@endsection --}}

@section('page-script')
@parent
<script>
document.addEventListener("DOMContentLoaded", function () {

    // ========================
    // 1) CHECK CHA → CHECK TOÀN BỘ CON (đa cấp)
    // ========================
    function checkAllChildren(parentCheckbox, checked) {
        const parentDiv = parentCheckbox.closest(".form-check");
        const childContainer = parentDiv.nextElementSibling;

        if (!childContainer || !childContainer.classList.contains("child-checkboxes")) return;

        const inputs = childContainer.querySelectorAll("input.form-check-input");
        inputs.forEach(cb => {
            cb.checked = checked;
            checkAllChildren(cb, checked); // ĐỆ QUY — để kiểm tra sâu đến cấp 4–5–6
        });
    }

    // ========================
    // 2) CHECK CON → AUTO CHECK CHA (đa cấp)
    // ========================
    function updateParents(childCheckbox) {
        let parentContainer = childCheckbox.closest(".child-checkboxes");

        while (parentContainer) {
            const parentLabelWrap = parentContainer.previousElementSibling; // div.form-check
            const parentCheckbox = parentLabelWrap.querySelector("input.form-check-input");

            const siblings = parentContainer.querySelectorAll("input.form-check-input");
            const anyChecked = Array.from(siblings).some(cb => cb.checked);

            parentCheckbox.checked = anyChecked;

            // Move up to next level
            parentContainer = parentLabelWrap.closest(".form-check")
                                            ?.closest(".child-checkboxes");
        }
    }

    // ========================
    // 3) LẮNG NGHE MỌI CHECKBOX
    // ========================
    document.querySelectorAll("input.form-check-input").forEach(cb => {
        cb.addEventListener("change", function () {

            // Nếu là checkbox CHA
            if (!cb.closest(".child-checkboxes")) {
                checkAllChildren(cb, cb.checked);
                return;
            }

            // Nếu là checkbox CON
            updateParents(cb);
        });
    });

    // ========================
    // 4) INIT — KHI LOAD LẠI FORM
    // ========================
    document.querySelectorAll(".child-checkboxes").forEach(container => {
        const anyChecked = container.querySelector("input.form-check-input:checked");
        if (anyChecked) {
            const parentWrap = container.previousElementSibling;
            const parentCheckbox = parentWrap.querySelector("input.form-check-input");
            parentCheckbox.checked = true;
            updateParents(parentCheckbox);
        }
    });

});
</script>
@endsection


@section('_attributes')
    @if ($post->post_type === App\Enums\PostType::POST->value)
        <div class="accordion-item active">
            <h2 class="accordion-header d-flex align-items-center">
                <button type="button" class="accordion-button accordion-header-font-size" data-bs-toggle="collapse"
                    data-bs-target="#accordionWithIcon-1" aria-expanded="true">
                    <i class="ri-book-shelf-fill ri-20px me-2"></i>
                    {{ __('admin.ui.chuyen_muc') }}
                </button>
            </h2>
            <div id="accordionWithIcon-1" class="accordion-collapse collapse show">
                <div class="accordion-body" style="padding: 0;padding-bottom: 10px">
                    <div class="card" style="box-shadow: none">
                        <div class="card-header p-0">
                            <div class="nav-align-top">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item">
                                        <button type="button" class="nav-link active text-none" role="tab"
                                            data-bs-toggle="tab" data-bs-target="#navs-top-home"
                                            aria-controls="navs-top-home"
                                            aria-selected="true">{{ __('admin.ui.tat_ca_chuyen_muc') }}</button>
                                    </li> 
                                </ul>
                            </div>
                        </div>

                        <div class="card-body ps ps--active-y">
                            <div class="tab-content p-0">

                                <div class="tab-pane fade show active" id="navs-top-home" role="tabpanel">
                                    <div class="form-floating form-floating-outline chuyen-muc-danh-sach"> 
                                        <div class="parent-checkbox" style="max-height: 500px; overflow-y: auto;">
                                            {{-- @if (isset($cate))
                                                @foreach ($cate as $category) 
                                                    <div class="form-check mt-1">
                                                    <input  class="form-check-input"
                                                            type="checkbox"
                                                            id="category{{ $category->term->term_id }}"
                                                            name="category_ids[]"
                                                            value="{{ $category->term_taxonomy_id }}"
                                                            {{ $selectedCatIds->contains($category->term_taxonomy_id) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="category{{ $category->term->term_id }}">
                                                        {{ $category->term->name }} ({{ $category->count }})
                                                    </label>
                                                    </div>
 
                                                    @if ($category->children->isNotEmpty())
                                                    <div class="child-checkboxes ms-4">
                                                        @foreach ($category->children as $child)
                                                        <div class="form-check mt-1">
                                                            <input  class="form-check-input"
                                                                    type="checkbox"
                                                                    id="category{{ $child->term->term_id }}"
                                                                    name="category_ids[]"
                                                                    value="{{ $child->term_taxonomy_id }}"
                                                                    {{ $selectedCatIds->contains($child->term_taxonomy_id) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="category{{ $child->term->term_id }}">
                                                            {{ $child->term->name }} ({{ $child->count }})
                                                            </label>
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                    @endif
                                                @endforeach
                                            @endif --}}
                                            @if (isset($cate))
                                                <x-category-tree-post 
                                                    :nodes="$cate"
                                                    :selectedCatIds="$selectedCatIds"
                                                />
                                            @endif
                                        </div>  
                                    </div>
                                </div>

                                
                            </div>
                        </div>
                    </div>
                </div> 
            </div>
        </div>
    @elseif($post->post_type === App\Enums\PostType::IMAGE->value)
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
                        <input class="form-control" name="order" type="number"
                            value="{{ old('order') ?? $post->order }}" placeholder="0" id="html5-number-input" />
                        <label for="html5-number-input">{{ __('admin.ui.sap_xep') }}</label>
                    </div>


                </div>
            </div>
        </div>
    @endif
    @if ($post->post_type === App\Enums\PostType::IMAGE->value)
    @elseif($post->post_type === App\Enums\PostType::VIDEO->value)
    @else
        <div class="accordion-item active">
            <h2 class="accordion-header d-flex align-items-center">
                <button type="button" class="accordion-button accordion-header-font-size" data-bs-toggle="collapse"
                    data-bs-target="#accordionWithIcon-2" aria-expanded="true">
                    <i class="ri-image-fill ri-20px me-2"></i>
                    @if ($post->post_type === App\Enums\PostType::BANNER->value)
                        {{ __('Banner') }}
                    @else
                        {{ __('admin.ui.anh_dai_dien') }}
                    @endif
                </button>
            </h2>
            <div id="accordionWithIcon-2" class="accordion-collapse collapse show">
                <div class="accordion-body">
                    <div class="row">
                        <input id="thumbnail" name="avatar" class="form-control" type="hidden" name="filepath"
                            value="{{ old('avatar') ?? $post->getFirstMediaUrl('avatars') }}">
                        <div id="holder" style="margin-top:15px;max-height:400px" class="img-avt">
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
    @endif
@endsection  
