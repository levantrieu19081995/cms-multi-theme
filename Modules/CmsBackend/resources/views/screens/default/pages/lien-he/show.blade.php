@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.ui.chi_tiet_lien_he'))

@section('vendor-style')
@endsection

@section('page-script')
    {{-- Nội dung người dùng chỉ đi qua thuộc tính data-* (đã escape) và clipboard; không chèn vào JavaScript. --}}
    <script>
        const copyFailedMessage = @json(__('admin.contact.copied_failed'));

        function fallbackCopy(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                return document.execCommand('copy');
            } catch (err) {
                return false;
            } finally {
                document.body.removeChild(textarea);
            }
        }

        document.querySelectorAll('[data-copy]').forEach(function (element) {
            element.addEventListener('click', function () {
                const status = element.nextElementSibling;
                const done = function () {
                    if (status) {
                        status.style.display = 'inline';
                        setTimeout(function () { status.style.display = 'none'; }, 2000);
                    }
                };
                const text = element.dataset.copy;
                const fallback = function () {
                    if (fallbackCopy(text)) {
                        done();
                    } else {
                        alert(copyFailedMessage);
                    }
                };
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(done).catch(fallback);
                } else {
                    fallback();
                }
            });
        });

        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.confirm(form.dataset.confirm)) {
                    event.preventDefault();
                }
            });
        });
    </script>
@endsection

@section('content')
    {{ Breadcrumbs::render('cms.dashboard.lienhe.show', '') }}
    <div class='row'>
        <div class='col-12 col-lg-6'>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary d-flex flex-row justify-content-between">
                    <h4 class="text-white text-uppercase mb-0">{{ __('admin.ui.chi_tiet_lien_he') }}</h4>
                    <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.lien-he.index') }}" class="btn btn-light text-none">
                        <i class="ri-arrow-left-double-line"></i> {{ __('admin.ui.quay_lai_danh_sach_lien_he') }}</a>
                </div>
                <div class="card-body mt-6">
                    @if ($gopy->trashed())
                        <div class="alert alert-warning py-2" role="status">{{ __('admin.contact.in_trash') }} {{ $gopy->deleted_at->format('d/m/Y H:i') }}</div>
                    @endif
                    <div class="mb-3">
                        <p class="text-muted mb-0">{{ __('admin.ui.ho_va_ten') }}</p>
                        <b>{{ $gopy->name }}</b>
                    </div>

                    <div class='row mb-3'>
                        <div class="col-12 col-lg-6">
                            <p class="text-muted mb-0">{{ __('admin.ui.so_dien_thoai') }}</p>
                            <i class="ri-phone-line"></i>
                            <span style="cursor: pointer;" data-copy="{{ $gopy->phone }}">{{ $gopy->phone }}</span><small class="text-muted ms-2" style="display:none;">{{ __('admin.ui.da_sao_chep') }}</small>
                        </div>
                        <div class="col-12 col-lg-6">
                            <p class="text-muted mb-0">Email</p>
                            <i class="ri-mail-line"></i>
                            <span style="cursor: pointer;" data-copy="{{ $gopy->email }}">{{ $gopy->email }}</span><small class="text-muted ms-2" style="display:none;">{{ __('admin.ui.da_sao_chep') }}</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <p class="text-muted mb-0">{{ __('admin.contact.address') }}</p>
                        @if (filled($gopy->address))
                            <span>{{ $gopy->address }}</span>
                        @else
                            <span class="text-muted">{{ __('admin.contact.no_address') }}</span>
                        @endif
                    </div>

                    <div class="mb-3">
                        <p class="text-muted mb-0">{{ __('admin.ui.tieu_de') }}</p>
                        <span>{{ $gopy->subject }}</span>
                    </div>

                    <div class="mb-3">
                        <p class="text-muted mb-0">{{ __('admin.ui.noi_dung') }}</p>
                        <div style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $gopy->message }}</div>
                    </div>

                    <div class="mb-3">
                        <i class="ri-timer-line"></i>
                        <small>{{ __('admin.ui.thoi_gian_gui') }} {{ $gopy->created_at?->format('d/m/Y H:i') }}</small>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="mailto:{{ $gopy->email }}" class="btn btn-outline-primary" title="{{ __('admin.ui.gui_email_tra_loi') }}"><i class="ri-mail-send-line me-1"></i>{{ __('admin.ui.gui_email_tra_loi') }}</a>
                        @if ($canDelete)
                            @if ($gopy->trashed())
                                <form method="POST" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.lien-he.restore', $gopy->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-success"><i class="ri-arrow-go-back-line me-1"></i>{{ __('admin.contact.restore') }}</button>
                                </form>
                                <form method="POST" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.lien-he.purge', $gopy->id) }}" data-confirm="{{ __('admin.contact.confirm_purge') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger"><i class="ri-delete-bin-7-line me-1"></i>{{ __('admin.contact.purge') }}</button>
                                </form>
                            @else
                                <form method="POST" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.lien-he.destroy', $gopy->id) }}" data-confirm="{{ __('admin.contact.confirm_delete') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger"><i class="ri-delete-bin-7-line me-1"></i>{{ __('admin.contact.delete') }}</button>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
