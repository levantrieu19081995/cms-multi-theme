@extends('pages.bai-viet.edit_layout', ['resource' => 'trang'])


@section('_excerpt')
{{-- Nội dung hiển thị Card gốc trên bên trái, dưới URL --}}
@endsection

@section('_content')
<div class="card-header d-flex justify-content-between align-items-center">
    @if ($post->post_type === App\Enums\PostType::POST->value)
        <h5 class="mb-0 card-title">{{ __('Nội dung bài viết') }}</h5>
    @else
        <h5 class="mb-0 card-title">{{ __('Nội dung trang') }}</h5>
    @endif
    <a href="javascript:void(0);" class="fw-medium">Add media from URL</a>
</div>
<div class="card-body">
    <textarea name="content" id="ckeditor5">{{ old('content') ?? $post->content }}</textarea>
</div>
@endsection

@section('_infos')
@if($post->post_type !== App\Enums\PostType::PAGE->value)
<div class="row mb-2">
<div class="col-lg-3 col-md-12 col-sm-12">
    <b>Hiển thị ở trang chủ:</b>

</div>
<div class="col-lg-9 col-md-12 col-sm-12">
    <label class="switch switch-success">
    <input type="checkbox" name="show_home_page" class="switch-input" {{ $post->show_home_page ===
    '1'?'checked':'' }} />
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
@endif
@endsection

@section('_attributes')
{{-- Nội dung hiển thị ở Card gốc dưới bên phải --}}
@if($post->post_type === App\Enums\PostType::IMAGE->value || $post->post_type === App\Enums\PostType::PAGE->value)
    {{-- <div class="accordion-item active" style="color:black;">
        <h2 class="accordion-header d-flex align-items-center">
        <button type="button" class="accordion-button accordion-header-font-size" data-bs-toggle="collapse"
            data-bs-target="#accordionWithIcon-1" aria-expanded="true">
            <i class="ri-settings-3-fill ri-20px me-2"></i>
            {{ __('Thuộc tính') }}
        </button>
        </h2>
        <div id="accordionWithIcon-1" class="accordion-collapse collapse show">
        <div class="accordion-body " style="color:black;">
            <div class="form-floating form-floating-outline mt-5 trang-cha-select">
            <select id="selectTrangCha" class="select2 form-select form-select-lg" name="post_parent"
                data-allow-clear="true">
                <option value> {{ __('Không có trang cha') }}</option>
                {!! $pageOptions !!}
            </select>
            <label style="color:black;" for="selectTrangCha">
                @if($post->post_type === App\Enums\PostType::PAGE->value)
                {{ __('Trang cha') }}
                @else
                {{ __('Thư viện cha') }}
                @endif
            </label>
            </div>
            <div class="form-floating form-floating-outline mt-5">
            <input class="form-control" name="order" type="number" value="{{ old('order')?? $post->order }}"
                placeholder="0" id="html5-number-input" />
            <label for="html5-number-input">{{ __('Sắp xếp') }}</label>
            </div>


        </div>
        </div>
    </div> --}}
@endif
@endsection
