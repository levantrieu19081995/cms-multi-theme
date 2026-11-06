@extends('pages.bai-viet.edit_layout', ['resource' => 'banner'])


@section('_excerpt')
{{-- Nội dung hiển thị Card gốc trên bên trái, dưới URL --}}
@endsection

@section('_content')
<div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0 card-title">{{ __('Thông tin banner') }}</h5>

</div>
<div class="card-body">
    <div class="form-floating form-floating-outline mb-6">
        <script>
            window.actionSelectPost = (res) => {
                document.getElementById('banner-redirect-link').value = res.title;
                // document.getElementById('ref_url').value = res.slug;
                document.getElementById('ref_id').value = res.id;
            }
        </script>
        <div class="form-floating form-floating-outline mb-6">
            <x-input-post-select label="" id="banner-redirect-link" name="post_title" callback="actionSelectPost"
                displayMode="input" placeholder="" type="post"  value="{{ $title??null }}"/>
            <label for="banner-redirect-link">Link đến bài viết</label>
        </div>
      
        <div class="form-floating form-floating-outline mb-6">
            <input type="hidden" value="{{ old('ref_id')??($ref_id??null) }}" class="form-control" name="ref_id" id="ref_id">
            {{-- <label for="ref_url">ID bài viết</label> --}}
            <div class="col mb-6 mt-2"></div>
        </div>
        <div class="col mb-6 mt-2"></div>
    </div>
</div>
@endsection

@section('_infos')
{{-- Nội dung hiển thị ở Card gốc trên bên phải --}}
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
@endsection

@section('_attributes')
{{-- Nội dung hiển thị ở Card gốc dưới bên phải --}}
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
@endsection
