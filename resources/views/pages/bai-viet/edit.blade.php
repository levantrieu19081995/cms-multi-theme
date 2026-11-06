@extends('pages.bai-viet.edit_layout', ['resource' => 'bai-viet'])


@section('_excerpt')
@if($post->post_type === App\Enums\PostType::POST->value)
<div class="form-floating form-floating-outline mb-5">
    <textarea class="form-control h-px-100" id="excerpt" placeholder="{{ __('Trích dẫn') }}" name="excerpt">{{ old('excerpt')?? $post->excerpt }}</textarea>
    <label for="excerpt">{{ __('Trích dẫn') }}</label>
</div>
<x-input-error :messages="$errors->get('excerpt')" class="mt-2" />
@endif
@endsection

@section('_content')
<div class="card-header d-flex justify-content-between align-items-center">
    @if ($post->post_type === App\Enums\PostType::POST->value)
    <h5 class="mb-0 card-title">{{ __('Nội dung bài viết') }}</h5>
    @else
    <h5 class="mb-0 card-title">{{ __('Nội dung trang') }}</h5>
    @endif
    {{-- <a href="javascript:void(0);" class="fw-medium">Add media from URL</a> --}}
</div>
<div class="card-body">
    <textarea name="content" id="ckeditor5">{{ old('content') ?? $post->content }}</textarea>
</div>
@endsection

@section('_infos')
<div class="row mb-2">
    <div class="col-lg-3 col-md-12 col-sm-12">
        <b>Hiển thị ở trang chủ:</b>

    </div>
    <div class="col-lg-9 col-md-12 col-sm-12">
        <label class="switch switch-success">
            <input type="checkbox" name="show_home_page" class="switch-input" {{ $post->show_home_page ==
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
<div class="row mb-2">
    <div class="col-lg-3 col-md-12 col-sm-12">
        <b>Cho phép bình luận:</b>

    </div>
    <div class="col-lg-9 col-md-12 col-sm-12">
        <label class="switch switch-success">
            <input type="checkbox" name="comment_status" class="switch-input-comment" {{ $post->comment_status ==
    'published'?'checked':'' }} />
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
@if($post->post_type === App\Enums\PostType::POST->value)
<div class="accordion-item active">
    <h2 class="accordion-header d-flex align-items-center">
        <button type="button" class="accordion-button accordion-header-font-size" data-bs-toggle="collapse"
            data-bs-target="#accordionWithIcon-1" aria-expanded="true">
            <i class="ri-book-shelf-fill ri-20px me-2"></i>
            {{ __('Chuyên mục') }}
        </button>
    </h2>
    <div id="accordionWithIcon-1" class="accordion-collapse collapse show">
        <div class="accordion-body" style="padding: 0;padding-bottom: 10px">
            <div class="card" style="box-shadow: none">
                <div class="card-header p-0">
                    <div class="nav-align-top">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#navs-top-home" aria-controls="navs-top-home" aria-selected="true">{{
                    __('Tất cả chuyên mục') }}</button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                                    aria-selected="false">{{
                    __('Sử dụng nhiều') }}</button>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="card-body ps ps--active-y">
                    <div class="tab-content p-0">
                        <div class="tab-pane fade show active" id="navs-top-home" role="tabpanel">
                            <div class="form-floating form-floating-outline chuyen-muc-danh-sach">
                                <div class="parent-checkbox" style="max-height: 500px; overflow-y: auto;">
                                    @if(isset($cate))
                                    @foreach ($cate as $c)
                                    @include('pages.category.partials.category-checkbox', ['category' => $c,'post' => $post])
                                    @endforeach
                                    @endif
                                    <x-input-error :messages="$errors->get('category_ids')" class="mt-2" />
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="navs-top-profile" role="tabpanel">
                            <div class="form-floating form-floating-outline chuyen-muc-danh-sach">
                                <div class="parent-checkbox" style="max-height: 500px; overflow-y: auto;">
                                    @if(isset($topCate))
                                    @foreach ($topCate as $tc)
                                    <div class="form-check mt-1">
                                        <input class="form-check-input parent" type="checkbox"
                                            value="{{ $tc->term_taxonomy_id }}" name="category_ids[]"
                                            id="parent{{$tc->term->term_id}}top" {{ $post->taxonomies->contains('term_id',
                    $tc->term->term_id) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="parent{{$tc->term->term_id}}top">
                                            {{ $tc->term->name }} ({{ $tc->count }})
                                        </label>
                                    </div>
                                    @endforeach
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
</div>
@endif
@if($post->post_type === App\Enums\PostType::IMAGE->value)
@elseif($post->post_type === App\Enums\PostType::VIDEO->value)
@else
<div class="accordion-item active">
    <h2 class="accordion-header d-flex align-items-center">
        <button type="button" class="accordion-button accordion-header-font-size" data-bs-toggle="collapse"
            data-bs-target="#accordionWithIcon-2" aria-expanded="true">
            <i class="ri-image-fill ri-20px me-2"></i>
            @if($post->post_type === App\Enums\PostType::BANNER->value)
            {{ __('Banner') }}
            @else
            {{ __('Ảnh đại diện') }}
            @endif
        </button>
    </h2>
    <div id="accordionWithIcon-2" class="accordion-collapse collapse show">
        <div class="accordion-body">
            <div class="row">
                <input id="thumbnail" name="avatar" class="form-control" type="hidden" name="filepath"
                    value="{{ old('avatar') ??$post->getFirstMediaUrl('avatars') }}">
                <div id="holder" style="margin-top:15px;max-height:400px" class="img-avt">
                    <img src="{{ old('avatar') ??$post->getFirstMediaUrl('avatars') }}">
                </div>
                <x-input-error :messages="$errors->get('avatar')" class="mt-2" />
            </div>
            <div class="row">
                <button type="button" id="lfm" data-input="thumbnail" data-preview="holder"
                    style="border: thin dashed #cfd0d6;height: 35px; width: 100%;">
                    <i class="fa fa-picture-o"></i> Chọn ảnh
                </button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection