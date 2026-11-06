{{-- Param: --}}
{{-- + resource: tên resource để generate link --}}

@extends('layouts/layoutMaster')

@section('title', $titleName ? $titleName : __('Thêm mới bài viết'))

@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/libs/quill/typography.scss', 'assets/vendor/libs/quill/katex.scss', 'assets/vendor/libs/quill/editor.scss', 'assets/vendor/libs/select2/select2.scss', 'assets/vendor/libs/tagify/tagify.scss', 'assets/vendor/libs/bootstrap-select/bootstrap-select.scss', 'assets/vendor/libs/typeahead-js/typeahead.scss', 'assets/vendor/libs/dropzone/dropzone.scss', 'assets/vendor/libs/flatpickr/flatpickr.scss', 'assets/vendor/libs/ckeditor5/ckeditor5.css', 'assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss', 'assets/vendor/libs/notiflix/notiflix-3.2.7.min.css', 'assets/vendor/libs/spinkit/spinkit.scss']))
    <link rel="stylesheet" href="{{ asset('plugins/dropzone/dropzone.min.css') }}">
@endsection
@section('page-style')
    @vite(['resources/assets/css/ckeditor.css', 'resources/assets/css/bai-viet-be.css'])
@endsection
@section('vendor-script')
    @vite(
        Theme::asset([
            'assets/vendor/libs/quill/katex.js',
            'assets/vendor/libs/select2/select2.js',
            'assets/vendor/libs/bootstrap-select/bootstrap-select.js',
            'assets/vendor/libs/typeahead-js/typeahead.js',
            'assets/vendor/libs/dropzone/dropzone.js',
            'assets/vendor/libs/jquery-repeater/jquery-repeater.js',
            'assets/vendor/libs/flatpickr/flatpickr.js',
            'assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js',
            'assets/vendor/libs/notiflix/notiflix-3.2.7.min.js',
            'assets/vendor/libs/sortablejs/sortable.js',
            // 'assets/js/form-basic-inputs.js',
            'assets/js/forms-typeahead.js',
            'assets/js/forms-selects.js',
            'assets/js/forms-tagify.js',
        ])
    )
    <script src="{{ asset('plugins/dropzone/dropzone.min.js') }}"></script>
@endsection

@section('page-script')
    @vite(['resources/assets/js/ckeditor5-cus.js', 'resources/assets/js/form-input-group.js', 'resources/assets/js/extended-ui-perfect-scrollbar.js', 'resources/assets/js/lightbox-import.js', 'resources/assets/js/post.js'])
    <script src="{{ asset('/vendor/laravel-filemanager/js/stand-alone-button.js') }}"></script>

    <script type="module">
        function updateViewImage() {
            if (imageObjects) {
                imageObjects.forEach((i, index) => {
                        const imageDiv = `
                   <div class="col-md-4 mb-3 position-relative grid-child">
                      <a href="${i.url}" data-lightbox="image-library" data-title="Ảnh ${index + 1}">
                       <img src="${i.url}" alt="Image Preview" class="img-fluid">
                     </a>
                     <input type="text" class="form-control mt-2" placeholder="Nhập mô tả" value="${i.description??''}"
                       oninput="updateDescription(${index}, this.value)">
                        <button type="button" class="btn btn-danger waves-effect waves-light btn-sm position-absolute remove-image-btn"  onclick="removeImage(this, '${i}')">X</button>
                   </div>
               `;
                        // $('#image-preview-container').append(imageDiv);
                        document.getElementById('image-preview-container').insertAdjacentHTML('beforeend', imageDiv);
                    }\
                }
            }

            document.addEventListener("DOMContentLoaded", function() {
                document.getElementById('lfm').addEventListener('click', function() {
                    console.log('dô')

                });
                document.getElementById('lfm_image').addEventListener('click', function() {
                    console.log('dô lfm_image')
                    lfm('lfm_image', 'image', {
                        prefix: '/laravel-filemanager'
                    });
                });
            });
    </script>

    @yield('_page-script')
@endsection

@section('content')
    <form id="postCreate" action="{{ $post->id ? route("$resource.update", $post) : route("$resource.store") }}" method="POST"
        onsubmit="updateViewImage()">
        @csrf
        @if ($post->id)
            @method('put')
        @endif
        <input type="hidden" name="post_type" value="{{ $post->post_type }}" id="post_type">
        <div class="app-ecommerce">
            <div
                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">

                <div class="d-flex flex-column justify-content-center">
                    <h4 class="mb-1">{{ $titleName ? $titleName : __('Thêm mới bài viết') }}</h4>
                </div>
                <div class="d-flex align-content-center flex-wrap gap-4">
                    <a href="{{ route("$resource.index") }}" class="btn btn-outline-secondary"> {{ __('Trở về') }}</a>
                    @if (
                        $permissions['canSaveDraft'] &&
                            ($post->status !== App\Enums\PostStatus::PUBLISHED->value &&
                                $post->status !== App\Enums\PostStatus::APPROVED->value))
                        <button type="submit" class="btn btn-outline-primary" name="action" value="save_draft"
                            id="saveDraftButton">{{ __('Lưu nháp') }}</button>
                    @endif
                    @if (
                        $permissions['canUpdate'] &&
                            ($post->status == App\Enums\PostStatus::PUBLISHED->value ||
                                $post->status == App\Enums\PostStatus::APPROVED->value))
                        <button type="submit" class="btn btn-primary" name="action" value="submit"
                            id="submitButton">{{ __('Lưu') }}
                        </button>
                    @endif
                    @if (
                        $permissions['canApprove'] &&
                            !$permissions['canPublishNow'] &&
                            $post->status == App\Enums\PostStatus::PENDING_APPROVAL->value)
                        <!-- Nút Phê duyệt bài viết (chỉ hiển thị nếu trạng thái bài là đang chờ duyệt) -->
                        <button name="action" value="approved" class="btn btn-success"> {{ __('Phê duyệt') }}</button>
                    @endif
                    @if (
                        $permissions['canPublishNow'] &&
                            ($post->status == App\Enums\PostStatus::APPROVED->value ||
                                $post->status == App\Enums\PostStatus::DRAFT->value ||
                                $post->status == App\Enums\PostStatus::STOP_PUBLISHING->value))
                        <!-- Nút Phê duyệt bài viết (chỉ hiển thị nếu trạng thái bài là đang chờ duyệt) -->
                        <button type="submit" name="action" value="publish_now" class="btn btn-success">
                            {{ __('Xuất bản') }}</button>
                    @endif
                    @if (
                        $permissions['canSubmitForApproval'] &&
                            !$permissions['canApprove'] &&
                            ($post->status == App\Enums\PostStatus::DRAFT->value ||
                                $post->status == App\Enums\PostStatus::NOT_APPROVED->value ||
                                $post->status == App\Enums\PostStatus::STOP_PUBLISHING->value))
                        <!-- Nút Phê duyệt bài viết (chỉ hiển thị nếu trạng thái bài là đang chờ duyệt) -->
                        <button type="submit" name="action" value="submit_for_approval" class="btn btn-success">
                            {{ __('Trình duyệt') }}</button>
                    @endif
                    @if ($permissions['canReject'] && $post->status == App\Enums\PostStatus::PENDING_APPROVAL->value)
                        <!-- Nút Phê duyệt bài viết (chỉ hiển thị nếu trạng thái bài là đang chờ duyệt) -->
                        <button type="button" class="btn btn-outline-danger waves-effect" name="action" value="reject"
                            data-bs-toggle="modal" data-bs-target="#addFeedBackModal">{{ __('Không duyệt') }}</button>
                    @endif
                    @if ($permissions['canStopPublishing'] && $post->status == App\Enums\PostStatus::PUBLISHED->value)
                        <!-- Nút Phê duyệt bài viết (chỉ hiển thị nếu trạng thái bài là đang chờ duyệt) -->
                        <button type="submit" type="button" class="btn btn-outline-danger waves-effect" name="action"
                            value="stop_publishing">{{ __('Ngừng xuất bản') }}</button>
                    @endif
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-lg-8">
                    <div class="card mb-6">
                        @if (isset($feedback) && $feedback)
                            <div class="alert alert-warning">
                                <strong>Phản hồi từ biên tập viên:</strong> {{ $feedback->feedback }}
                            </div>
                        @endif
                        <div class="card-body">
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="hidden" class="form-control" id="post_id"
                                    value="{{ old('post_id') ?? $post->id }}">
                                <input type="text" class="form-control" id="title" name="title"
                                    value="{{ old('title') ?? $post->title }}" placeholder="{{ __('Nhập tiêu đề') }}"
                                    aria-describedby="defaultFormControlHelp" oninput="generateSlug()">
                                <label for="title">{{ __('Tiêu đề') }}</label>
                                <x-input-error :messages="$errors->get('title')" class="mt-2" />
                            </div>
                            <div class="input-group input-group-merge mb-5">
                                <span class="input-group-text">https://{{ request()->getHost() }}/</span>
                                <div class="form-floating form-floating-outline">
                                    <input type="text" style="color: blue;" id="slug" name="slug"
                                        value="{{ old('slug') ?? $post->slug }}" class="form-control"
                                        placeholder="{{ __('Đường dẫn tĩnh') }}" id="basic-url14">
                                    <label for="basic-url14">{{ __('Đường dẫn tĩnh') }}</label>
                                </div>
                                <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                            </div>
                            {{-- Trích yếu của bài viết (nếu có) --}}
                            @yield('_excerpt')
                            {{-- / Trích yếu của bài viết --}}
                        </div>
                    </div>
                    <div class="card mb-6">
                        {{-- Nội dung chính của bài viết, khác nhau tương ứng PostType --}}
                        @yield('_content')
                        {{-- / Nội dung chính của bài viết --}}
                    </div>
                    <div class="card mb-6">
                        {{-- Nội dung chính của bài viết, khác nhau tương ứng PostType --}}
                        @yield('_link-dtda')
                        {{-- / Nội dung chính của bài viết --}}
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="card mb-6">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('Thông tin xuất bản') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-lg-3 col-md-12 col-sm-12">
                                    <b>Tác giả:</b>
                                </div>
                                <div class="col-lg-9 col-md-12 col-sm-12">
                                    <span>{{ $post->id ? $post->author->name : $user->name }}</span>
                                </div>
                            </div>
                            {{-- Thông tin khác của bài viết, hiển thị gốc trên bên phải --}}
                            @yield('_infos')
                            {{-- / Thông tin khác của bài viết, hiển thị gốc trên bên phải --}}
                            <div class="row mb-2 loai-xuat-ban">
                                <div class="col-lg-3 col-md-12 col-sm-12">
                                    <b>Trạng thái</b>
                                </div>
                                <div class="col-lg-9 col-md-12 col-sm-12">
                                    <select class="form-select" id="publish_type_select1" name="publish_type"
                                        aria-label="Default select example" disabled>
                                        @if ($postStatus)
                                            @foreach ($postStatus as $status)
                                                <option value="{{ $status->value }}"
                                                    {{ $status->value == old('status', $post->status ?? null) ? 'selected' : '' }}>
                                                    {{ $status->label }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            {{-- @if ($post->post_type !== App\Enums\PostType::VIDEO->value) --}}
                            <div class="row mb-2 loai-xuat-ban">
                                <div class="col-lg-3 col-md-12 col-sm-12">
                                    <b>Loại xuất bản:</b>
                                </div>
                                <div class="col-lg-9 col-md-12 col-sm-12">
                                    <div class="">
                                        <select class="form-select" onchange="handleSelectChange()"
                                            id="publish_type_select" name="publish_type"
                                            aria-label="Default select example">
                                            @if ($publishType)
                                                @foreach ($publishType as $type)
                                                    <option value="{{ $type->value }}"
                                                        {{ $type->value == old('publish_type', $post->publish_type ?? null) ? 'selected' : '' }}>
                                                        {{ $type->label }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>

                                    </div>
                                </div>

                            </div>
                            <div class="row mb-2 loai-xuat-ban ngay-xuat-ban" style="display: none;">
                                <div class="col-lg-3 col-md-12 col-sm-12">
                                    <b>Từ:</b>
                                </div>
                                <div class="col-lg-9 col-md-12 col-sm-12 mb-2">
                                    <input class="form-control" name="published_at"
                                        value="{{ old('published_at') ?? $post->published_at }}" type="datetime-local"
                                        id="html5-datetime-local-input">
                                </div>
                                <div class="col-lg-3 col-md-12 col-sm-12">
                                    <b>Đến:</b>
                                </div>
                                <div class="col-lg-9 col-md-12 col-sm-12">
                                    <input class="form-control" name="publication_date_expires"
                                        value="{{ old('publication_date_expires') ?? $post->publication_date_expires }}"
                                        type="datetime-local" id="html5-datetime-local-input">
                                </div>

                            </div>
                            {{-- @endif --}}
                        </div>
                    </div>
                    <div class="accordion mt-4" id="accordionWithIcon">
                        {{-- Thuộc tính của bài viết, hiển thị gốc dưới bên phải --}}
                        @yield('_attributes')
                        {{-- / Thuộc tính của bài viết, hiển thị gốc dưới bên phải --}}
                    </div>
                </div>
            </div>
        </div>
        </div>
    </form>

    @if ($post->id)
        @include('_partials.feedbacks.modal-feedback-reject-bai-viet')
    @endif
@endsection
