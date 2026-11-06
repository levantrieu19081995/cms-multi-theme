@extends('cmsbackend::screens.components.layouts.post-layout.create_edit_layout', ['resource' => 'cms.dashboard.su-kien'])

@section('_content')
<!-- {{ Breadcrumbs::render($post->id ? 'cms.dashboard.su-kien.edit' : 'cms.dashboard.su-kien.create', $post->id ?? '') }} -->
<div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0 card-title">{{ __('admin.ui.noi_dung_su_kien') }}</h5>
</div>
<div class="card-body">
    <textarea name="content" id="ckeditor5">{{ old('content') ?? $post->content }}</textarea>
</div>
@endsection

@section('_infos')
<div class="row mb-2">
    <div class="col-lg-3 col-md-12 col-sm-12">
        <b>{{ __('admin.ui.su_kien_noi_bat') }}</b>
    </div>
    <div class="col-lg-9 col-md-12 col-sm-12">
        <label class="switch switch-success">
            {{-- <input type="checkbox" name="show_home_page" class="switch-input"
                {{ $post->show_home_page == '1' ? 'checked' : '' }} /> --}}
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

<div class="row mb-2">
    <div class="col-lg-3 col-md-12 col-sm-12">
        <b>{{ __('admin.ui.cho_phep_binh_luan_2') }}</b>
    </div>
    <div class="col-lg-9 col-md-12 col-sm-12">
        <label class="switch switch-success">
            {{-- <input type="checkbox" name="comment_status" class="switch-input-comment"
                {{ $post->comment_status == 'published' ? 'checked' : '' }} /> --}}
            <input type="checkbox" name="comment_status" value="published" class="switch-input-comment"
            {{ old('comment_status', $post->comment_status) == 'published' ? 'checked' : '' }} />

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

@section('_excerpt')
<div class="form-floating form-floating-outline mb-5">
    <textarea class="form-control h-px-100" id="excerpt" placeholder="{{ __('admin.ui.dia_diem') }}" name="excerpt"> {{ old('excerpt') ?? $post->excerpt }}</textarea>
    <label for="excerpt">{{ __('admin.ui.dia_diem') }}</label>
</div>
<x-input-error :messages="$errors->get('excerpt')" class="mt-2" />
@endsection

@section('_attributes')
{{-- <div class="accordion-item active">
    <h2 class="accordion-header d-flex align-items-center">
        <button type="button" class="accordion-button accordion-header-font-size" data-bs-toggle="collapse"
            data-bs-target="#accordionWithIcon-2" aria-expanded="true">
            Thời gian diễn ra sự kiện
        </button>
    </h2>
    <div id="accordionWithIcon-2" class="accordion-collapse collapse show">
        <div class="accordion-body">
            <div class="row mb-2 loai-xuat-ban ngay-xuat-ban">
                <div class="col-lg-3 col-md-12 col-sm-12">
                    <b>Từ ngày:</b>
                </div>
                <div class="col-lg-9 col-md-12 col-sm-12 mb-2">
                    <input class="form-control" name="published_at"
                        value="{{ old('published_at') ?? $post->published_at }}" type="datetime-local"
                        id="html5-datetime-local-input">
                </div>
                <div class="col-lg-3 col-md-12 col-sm-12">
                    <b>Đến ngày:</b>
                </div>
                <div class="col-lg-9 col-md-12 col-sm-12">
                    <input class="form-control" name="publication_date_expires"
                        value="{{ old('publication_date_expires') ?? $post->publication_date_expires }}"
                        type="datetime-local" id="html5-datetime-local-input">
                </div>
            </div>
        </div>
    </div>
</div> --}}

<div class="accordion-item active">
    <h2 class="accordion-header d-flex align-items-center">
        <button type="button" class="accordion-button accordion-header-font-size" data-bs-toggle="collapse"
            data-bs-target="#accordionWithIcon-2" aria-expanded="true">
            {{ __('admin.ui.anh_dai_dien') }}
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

@endsection