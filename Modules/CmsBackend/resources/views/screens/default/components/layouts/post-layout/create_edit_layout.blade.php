{{-- Param: --}}
{{-- + resource: tên resource để generate link --}}
{{-- + post: đối tượng bài viết hiện tại --}}
{{-- + user: người dùng hiện tại --}}
{{-- + postStatus: danh sách trạng thái bài viết --}}
{{-- + publishType: danh sách loại xuất bản --}}
{{-- + permissions: danh sách quyền của người dùng hiện tại --}}
{{-- + titleName: tên tiêu đề của form, nếu không có thì sẽ hiển thị là "Thêm mới bài viết" --}}
{{-- + breadcrumbName, breadcrumbParam --}}


@php
    use App\Enums\PostStatus; 
    use App\Enums\PostType;

    // $isEvent = ($post->post_type ?? null) == PostType::EVENT->value; 
    // $isBanner = ($post->post_type ?? null) == PostType::BANNER->value; 
     // Các post_type mà nút __('admin.ui.xuat_ban') sẽ trở thành __('admin.ui.luu_chinh_thuc')
    $officialSaveTypes = [
        PostType::EVENT->value,
        PostType::BANNER->value,
    ];
    $isOfficialSaveType = in_array($post->post_type ?? null, $officialSaveTypes);
    $actionButtons = [
        [
            'group' => 'drafting',
            'show' =>
                ($permissions['canSaveDraft'] ?? false) &&
                !in_array($post->status, [PostStatus::PUBLISHED->value, PostStatus::APPROVED->value]),
            'type' => 'submit',
            'class' => 'btn btn-secondary',
            'name' => 'action',
            'value' => 'save_draft',
            'id' => 'saveDraftButton',
            'label' => __('admin.ui.luu_nhap'),
        ],
        // [
        //     'group' => 'drafting',
        //     'show' =>
        //         ($permissions['canUpdate'] ?? false) &&
        //         in_array($post->status, [PostStatus::PUBLISHED->value, PostStatus::APPROVED->value]),
        //     'type' => 'submit',
        //     'class' => 'btn btn-primary',
        //     'name' => 'action',
        //     'value' => 'submit',
        //     'id' => 'submitButton',
        //     'label' => __('admin.ui.luu'),
        // ],
        [
            'group' => 'drafting',
            'show' =>
                ($permissions['canUpdate'] ?? false) && (
                    // vẫn cho Lưu nếu đang ở trạng thái ĐÃ PHÊ DUYỆT
                    $post->status == PostStatus::APPROVED->value
                    // nếu đang XUẤT BẢN thì bắt buộc có cả 2 quyền POST_APPROVE & POST_PUBLISHING
                    || (
                        $post->status == PostStatus::PUBLISHED->value
                        && ($permissions['canApprove'] ?? false)
                        && ($permissions['canPublishNow'] ?? false)
                    )
                    // tài khoản được Developer bật "sửa trực tiếp bài đã đăng": được Lưu, bài vẫn giữ trạng thái xuất bản
                    || (
                        $post->status == PostStatus::PUBLISHED->value
                        && ($permissions['canEditPublished'] ?? false)
                    )
                ),
            'type' => 'submit',
            'class' => 'btn btn-primary',
            'name'  => 'action',
            'value' => 'submit',
            'id'    => 'submitButton',
            'label' => __('admin.ui.luu'),
        ],
        [
            'group' => 'publishing',
            'show' =>
                ($permissions['canApprove'] ?? false) &&
                // !($permissions['canPublishNow'] ?? false) &&
                $post->status == PostStatus::PENDING_APPROVAL->value,
            'type' => 'submit',
            'class' => 'btn btn-label-success waves-effect',
            'name' => 'action',
            'value' => 'approved',
            'label' => __('admin.ui.approval'),
        ],
        [
            'group' => 'publishing',
            'show' =>
                ($permissions['canPublishNow'] ?? false) &&
                in_array($post->status, [
                    PostStatus::APPROVED->value,
                    PostStatus::DRAFT->value,
                    PostStatus::STOP_PUBLISHING->value,
                ]),
            'type' => 'publishing',
            'class' => 'btn btn-primary',
            'name' => 'action',
            'value' => 'publish_now',
            // 'label' => __('admin.ui.xuat_ban'),
            // 'label' => $isEvent ? __('admin.ui.luu_chinh_thuc') : __('admin.ui.xuat_ban'),
            'label' => $isOfficialSaveType ? __('admin.ui.luu_chinh_thuc') : __('admin.ui.xuat_ban'),

        ],
        [
            'group' => 'drafting',
            'show' =>
                ($permissions['canSubmitForApproval'] ?? false) &&
                !($permissions['canApprove'] ?? false) &&
                in_array($post->status, [
                    PostStatus::DRAFT->value,
                    PostStatus::NOT_APPROVED->value,
                    PostStatus::STOP_PUBLISHING->value,
                ]),
            'type' => 'submit',
            'class' => 'btn btn-label-primary waves-effect',
            'name' => 'action',
            'value' => 'submit_for_approval',
            'label' => __('admin.ui.trinh_duyet'),
        ],
        [
            'group' => 'status_management',
            'show' => ($permissions['canReject'] ?? false) && $post->status == PostStatus::PENDING_APPROVAL->value,

            'type' => 'button',
            'class' => 'btn btn-outline-danger waves-effect',
            'name' => 'action',
            'value' => 'reject',
            'attrs' => 'data-bs-toggle="modal" data-bs-target="#addFeedBackModal"',
            'label' => __('admin.ui.rejected'),
        ],
        [
            'group' => 'status_management',
            'show' => true,
            'type' => 'link',
            'class' => 'btn btn-outline-secondary',
            'name' => 'cancel',
            'value' => 'return',
            'label' => __('admin.ui.tro_ve'),
            'href' => \Modules\CmsBackend\Support\ModuleUrl::route("$resource.index"),
        ],
        [
            'group' => 'status_management',
            'show' => ($permissions['canStopPublishing'] ?? false) && $post->status == PostStatus::PUBLISHED->value,

            'type' => 'submit',
            'class' => 'btn btn-danger waves-effect',
            'name' => 'action',
            'value' => 'stop_publishing',
            'label' => __('admin.ui.ngung_xuat_ban'),
        ],
    ];

    $slugValue = old('slug') ?? ($post->slug ?? '');
    // dump($slugValue);
    $isSlugLocked = !empty($post->slug);
    $breadcrumbName = $breadcrumbName ?? $resource . '.' . ($post->id ? 'edit' : 'create');
@endphp

@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', $titleName ? $titleName : __('admin.ui.them_moi_bai_viet'))

@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/libs/quill/typography.scss', 'assets/vendor/libs/quill/katex.scss', 'assets/vendor/libs/quill/editor.scss', 'assets/vendor/libs/select2/select2.scss', 'assets/vendor/libs/tagify/tagify.scss', 'assets/vendor/libs/bootstrap-select/bootstrap-select.scss', 'assets/vendor/libs/typeahead-js/typeahead.scss', 'assets/vendor/libs/dropzone/dropzone.scss', 'assets/vendor/libs/flatpickr/flatpickr.scss', 'assets/vendor/libs/ckeditor5/ckeditor5.css', 'assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss', 'assets/vendor/libs/notiflix/notiflix-3.2.7.min.css', 'assets/vendor/libs/spinkit/spinkit.scss']))
    <link rel="stylesheet" href="{{ asset('plugins/dropzone/dropzone.min.css') }}">
@endsection
@section('page-style')
    @vite(['resources/assets/css/ckeditor.css', 'resources/assets/css/bai-viet-be.css'])
@endsection
@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/libs/quill/katex.js', 'assets/vendor/libs/select2/select2.js', 'assets/vendor/libs/bootstrap-select/bootstrap-select.js', 'assets/vendor/libs/typeahead-js/typeahead.js', 'assets/vendor/libs/dropzone/dropzone.js', 'assets/vendor/libs/jquery-repeater/jquery-repeater.js', 'assets/vendor/libs/flatpickr/flatpickr.js', 'assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js', 'assets/vendor/libs/notiflix/notiflix-3.2.7.min.js', 'assets/vendor/libs/sortablejs/sortable.js', 'assets/js/forms-selects.js']))
    <script src="{{ asset('plugins/dropzone/dropzone.min.js') }}"></script>
@endsection

@section('page-script')
   <script src="/vendor/laravel-filemanager/js/stand-alone-button.js" type="module"></script>
    @vite(['Modules/CmsBackend/resources/assets/js/screens/default/editor.js', 
    'resources/assets/js/form-input-group.js', 
    'resources/assets/js/extended-ui-perfect-scrollbar.js', 
    'resources/assets/js/lightbox-import.js'])
    @vite(['Modules/CmsBackend/resources/assets/js/post.js', 
    'Modules/CmsBackend/resources/assets/js/custom-forms-pickers.js'])
    @yield('_page-script')


<script>
document.addEventListener('DOMContentLoaded', function () {
    const titleInput = document.getElementById('title');
    const errorDiv = document.getElementById('title-error');

    const draftBtn = document.querySelector('button[name="action"][value="save_draft"]');
    const publishBtn = document.querySelector('button[name="action"][value="publish_now"]');

    if (titleInput && errorDiv) {
        titleInput.addEventListener('input', function () {
            const maxLength = 255;
            const currentLength = titleInput.value.length;

            if (currentLength > maxLength) {
                errorDiv.innerText = @json(__('admin.js.max_length_exceeded')).replace(':max', maxLength).replace(':current', currentLength);
                errorDiv.style.display = 'block';

                if (draftBtn) draftBtn.disabled = true;
                if (publishBtn) publishBtn.disabled = true;
            } else {
                errorDiv.innerText = '';
                errorDiv.style.display = 'none';

                if (draftBtn) draftBtn.disabled = false;
                if (publishBtn) publishBtn.disabled = false;
            }
        });

        // Cập nhật trạng thái ban đầu nếu nội dung đã quá dài
        titleInput.dispatchEvent(new Event('input'));
    }
});
</script>

</script>

@endsection

@section('content')
    {{ Breadcrumbs::render($post->id ? $resource . '.edit' : $resource . '.create', $post) }}
    <div class="card">
        <div class="card-header bg-primary d-flex flex-column justify-content-center">
            <h4 class="text-white text-uppercase mb-0">{{ $titleName ? $titleName : __('admin.ui.them_moi_bai_viet') }}</h4>
        </div>

        {{-- DEBUG: remove khi xong --}}
        {{-- <div class="p-2 mb-2 border rounded bg-light small">
        <div>canApprove: {{ var_export($permissions['canApprove'] ?? null, true) }}</div>
        <div>canPublishNow: {{ var_export($permissions['canPublishNow'] ?? null, true) }}</div>
        <div>post->status: {{ $post->status }}</div>
        <div>PENDING_APPROVAL value: {{ \App\Enums\PostStatus::PENDING_APPROVAL->value }}</div>
        <div>isPending? {{ $post->status == \App\Enums\PostStatus::PENDING_APPROVAL->value ? 'YES' : 'NO' }}</div>
        <div>shouldShowApprove?
            {{
            (($permissions['canApprove'] ?? false)
            && !($permissions['canPublishNow'] ?? false)
            && $post->status == \App\Enums\PostStatus::PENDING_APPROVAL->value)
            ? 'YES' : 'NO'
            }}
        </div>
        </div> --}}
         {{-- end--}}

        <div class="card-body">
            {{-- {{ $post->status}} --}}
            <form id="postCreate" action="{{ $post->id ? \Modules\CmsBackend\Support\ModuleUrl::route("$resource.update", $post) : \Modules\CmsBackend\Support\ModuleUrl::route("$resource.store") }}"
                method="POST" onsubmit="updateViewImage()">
                @csrf
                @if ($post->id)
                    @method('put')
                @endif
                <input type="hidden" name="post_type" value="{{ $post->post_type }}" id="post_type">
                <div class="app-ecommerce">
                    <div class="row">
                        <div class='col-12 my-3'>
                            <div class='d-flex justify-content-end align-items-center gap-3'>
                                {{-- Status Management Actions --}}
                                @php $statusBtns = collect($actionButtons)->where('group', 'status_management')->where('show', true); @endphp
                                @if ($statusBtns->count())
                                    <div class="d-flex flex-row flex-wrap align-items-center gap-2 mt-2 mb-2">
                                        @foreach ($statusBtns as $btn)
                                            @if ($btn['type'] === 'link')
                                                <a href="{{ $btn['href'] }}"
                                                    class="{{ $btn['class'] }} text-none">{{ $btn['label'] }}</a>
                                            @else
                                                {{-- <button type="{{ $btn['type'] }}" class="{{ $btn['class'] }} text-none"
                                                    name="{{ $btn['name'] ?? '' }}" value="{{ $btn['value'] ?? '' }}">
                                                    {{ $btn['label'] }}
                                                </button> --}}

                                                <button type="{{ $btn['type'] }}" class="{{ $btn['class'] }} text-none"
                                                    name="{{ $btn['name'] ?? '' }}" value="{{ $btn['value'] ?? '' }}"
                                                    {!! $btn['attrs'] ?? '' !!}>
                                                    {{ $btn['label'] }}
                                                </button>

                                            @endif
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Drafting Actions --}}
                                @php $draftingBtns = collect($actionButtons)->where('group', 'drafting')->where('show', true); @endphp
                                @if ($draftingBtns->count())
                                    <div class="d-flex flex-row flex-wrap align-items-center gap-2 mt-2 mb-2">
                                        @foreach ($draftingBtns as $btn)
                                            <button type="{{ $btn['type'] }}" class="{{ $btn['class'] }} text-none"
                                                name="{{ $btn['name'] ?? '' }}" value="{{ $btn['value'] ?? '' }}"
                                                {{ $btn['id'] ?? '' ? 'id=' . $btn['id'] : '' }}
                                                {!! $btn['attrs'] ?? '' !!}>{{ $btn['label'] }}</button>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Publishing Actions --}}
                                @php $publishingBtns = collect($actionButtons)->where('group', 'publishing')->where('show', true); @endphp
                                @if ($publishingBtns->count())
                                    <div class="d-flex flex-row flex-wrap align-items-center gap-2 mt-2 mb-2">
                                        @foreach ($publishingBtns as $btn)
                                            <button type="{{ $btn['type'] }}" class="{{ $btn['class'] }} text-none"
                                                name="{{ $btn['name'] ?? '' }}" value="{{ $btn['value'] ?? '' }}"
                                                {{ $btn['id'] ?? '' ? 'id=' . $btn['id'] : '' }}
                                                {!! $btn['attrs'] ?? '' !!}>{{ $btn['label'] }}</button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-12 col-lg-8">
                            <div class="card mb-6">
                                @if (isset($feedback) && $feedback)
                                    <div class="alert alert-warning">
                                        <strong>{{ __('admin.ui.phan_hoi_tu_bien_tap_vien') }}</strong> {{ $feedback->feedback }}
                                    </div>
                                @endif
                                <div class="card-body">
                                    <input type="hidden" class="form-control" id="post_id"
                                        value="{{ old('post_id', $post->id) ?? $post->id }}">

                                    <div class="form-floating form-floating-outline mb-5">
                                        <input type="text" class="form-control" id="title" name="title"
                                            value="{{ old('title') ?? $post->title }}"
                                            placeholder="{{ __('admin.ui.nhap_tieu_de') }}"
                                            aria-describedby="defaultFormControlHelp" oninput="generateSlug()">
                                        <label for="title">{{ __('admin.ui.tieu_de') }}</label>
                                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                                    </div>
                                    <div id="title-error" class="text-danger mt-1" style="display: none;"></div>


                                    <div class="input-group input-group-merge mb-5">
                                        {{-- <span class="input-group-text">https://{{ request()->getHost() }}/</span> --}}
                                        <div class="form-floating form-floating-outline">
                                            <input data-default="{{ $slugValue }}" type="text" id="slug"
                                                name="slug" value="{{ $slugValue }}"
                                                class="form-control text-primary"
                                                placeholder="https://{{ request()->getHost() }}"
                                                @if ($isSlugLocked) readonly data-slug-locked="1" @endif>
                                            <label for="basic-url14">{{ __('admin.ui.duong_dan_tinh') }}</label>
                                        </div>
                                        <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                                    </div>

                                    {{-- Trích yếu của bài viết (nếu có) --}}
                                    @yield('_excerpt')
                                    {{-- / Trích yếu của bài viết --}}
                                </div>
                            </div>
                            @include('cmsbackend::screens.components.translation.legacy-alternate-locales')
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
                                    <h5 class="card-title mb-0">{{ __('admin.ui.thong_tin_xuat_ban') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-2">
                                        <div class="col-lg-4 col-md-12 col-sm-12">
                                            <b>{{ __('admin.ui.tac_gia_2') }}</b>
                                        </div>
                                        <div class="col-lg-8 col-md-12 col-sm-12">
                                            <span>{{ $post->id ? $post->author->name : $user->name }}</span>
                                        </div>
                                    </div>
                                    {{-- Thông tin khác của bài viết, hiển thị gốc trên bên phải --}}
                                    @yield('_infos')
                                    {{-- / Thông tin khác của bài viết, hiển thị gốc trên bên phải --}}
                                    <div class="row mb-2 loai-xuat-ban">
                                        <div class="col-lg-4 col-md-12 col-sm-12">
                                            <b>{{ __('admin.ui.trang_thai_2') }}</b>
                                        </div>
                                        <div class="col-lg-8 col-md-12 col-sm-12">
                                            <select class="form-select select2" id="publish_type_select1"
                                                name="publish_type" aria-label="Default select example" disabled>
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
                                        <div class="col-lg-4 col-md-12 col-sm-12">
                                            <b>{{ __('admin.ui.loai_xuat_ban') }}</b>
                                        </div>
                                        <div class="col-lg-8 col-md-12 col-sm-12">
                                            <div class="">
                                                <select class="form-select select2" onchange="handleSelectChange()"
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
                                        <div class="col-lg-4 col-md-12 col-sm-12">
                                            <b>{{ __('admin.ui.tu_ngay_2') }}</b>
                                        </div>
                                        <div class="col-lg-8 col-md-12 col-sm-12 mb-2"> 
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" name="published_at" class="form-control"
                                                    value="{{ old('published_at') ?? $post->published_at }}"
                                                    placeholder="DD-MM-YYYY HH:MM" id="flatpickr-datetime-from" />
                                            </div> 
                                        </div>
                                        <div class="col-lg-4 col-md-12 col-sm-12">
                                            <b>{{ __('admin.ui.den_ngay_3') }}</b>
                                        </div>
                                        <div class="col-lg-8 col-md-12 col-sm-12">
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" name="publication_date_expires"
                                                    class="form-control"
                                                    value="{{ old('publication_date_expires') ?? $post->publication_date_expires }}"
                                                    placeholder="DD-MM-YYYY HH:MM" id="flatpickr-datetime-to" />
                                            </div>
                                        </div>
                                    </div>
                                    {{-- @endif --}}
                                </div>
                            </div>
                            {{-- Thuộc tính của bài viết, hiển thị gốc dưới bên phải --}}
                            <div class="accordion mt-4 mb-6" id="accordionWithIcon">
                                @yield('_attributes')
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if ($post->id)
        @include('cmsbackend::screens.components.shared.feedbacks.modal-feedback-reject-bai-viet')
    @endif
@endsection

