@extends('pages.bai-viet.edit_layout', ['resource' => 'hinh-anh'])


@section('_excerpt')
    {{-- Nội dung hiển thị Card gốc trên bên trái, dưới URL --}}
@endsection

@section('_content')
    <!-- Chọn hình ảnh từ Laravel File Manager -->
    <div class="card-header align-items-center">
        <button type="button" id="lfm" data-input="thumbnailImage" data-preview="holder" class="btn btn-primary">
            <i class="ri-image-add-fill"></i> Chọn ảnh</button>
        <input id="thumbnailImage" name="images" class="form-control" type="hidden" name="filepath"
            value="{{ old('images') }}">
        <x-input-error :messages="$errors->get('images')" class="mt-2" />
        <img id="holder" style="margin-top:15px;max-height:100px;">

    </div>

    <div class="card-body">
        <h4 class="mb-5">Danh sách hình ảnh:</h4>

        <div class="row" id="image-preview-container">
            {{-- @if ($post->getMedia('images') != null)
      @foreach ($post->getMedia('images') as $index => $image)
      <div class="col-md-4 mb-3 position-relative">
        <a href="{{ $image->getUrl() }}" data-lightbox="image-library" data-title="Ảnh {{ $index+1 }}">
          <img src="{{ $image->getUrl() }}" alt="Image Preview" class="img-fluid"></a>
        <button type="button" class="btn btn-danger waves-effect waves-light btn-sm remove-image-btn"
          onclick="removeImage(this, '{{ $image->getUrl() }}')">X</button>
      </div>
      @endforeach
      @endif --}}
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
                <input type="checkbox" name="show_home_page" class="switch-input"
                    {{ $post->show_home_page === '1' ? 'checked' : '' }} />
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
    @if ($post->post_type === App\Enums\PostType::IMAGE->value || $post->post_type === App\Enums\PostType::PAGE->value)
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
                            @if ($post->post_type === App\Enums\PostType::PAGE->value)
                                {{ __('Trang cha') }}
                            @else
                                {{ __('Thư viện cha') }}
                            @endif
                        </label>
                    </div>
                    <div class="form-floating form-floating-outline mt-5">
                        <input class="form-control" name="order" type="number" value="{{ old('order') ?? $post->order }}"
                            placeholder="0" id="html5-number-input" />
                        <label for="html5-number-input">{{ __('Sắp xếp') }}</label>
                    </div>


                </div>
            </div>
        </div>
    @endif
@endsection
