@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.ui.tra_loi_cau_hoi'))

@section('vendor-style')
@endsection

@section('page-script')
    @vite(['Modules/CmsBackend/resources/assets/js/confirm-delete.js'])
@endsection

@section('content')
    {{ Breadcrumbs::render('cms.dashboard.hoidap.edit', '') }}
    <!-- Users List Table -->
    <div class="row">
        <div class='col-12 col-lg-4'>
            <div class="card">
                <h4 class="card-header bg-primary text-white text-uppercase">{{ __('admin.ui.tra_loi_cau_hoi') }}</h4>
                <div class="card-body mt-6">
                    <form action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.hoi-dap.update', $question->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3 d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-0">{{ __('admin.ui.ho_va_ten') }}</p>
                                <b>{{ $question->name }}</b>
                            </div>

                        </div>

                        <div class='row mb-3'>
                            <div class="col-12 col-lg-6">
                                <p class="text-muted mb-0">{{ __('admin.ui.so_dien_thoai') }}</p>
                                <div class='row'>
                                    <div class='col-1'>
                                        <i class="ri-phone-line "></i>
                                    </div>
                                    <div class='col-11'>
                                        <span style="cursor: pointer;"
                                            onclick="copyToClipboard('{{ $question->phone }}', this.nextElementSibling)">{{ $question->phone }}</span><small
                                            class="text-muted ms-2" style="display:none;">{{ __('admin.ui.da_sao_chep') }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <p class="text-muted mb-0">Email</p>
                                <div class='row'>
                                    <div class='col-1'>
                                        <i class="ri-mail-line"></i>
                                    </div>
                                    <div class='col-11'>
                                        <span style="cursor: pointer;"
                                            onclick="copyToClipboard('{{ $question->email }}', this.nextElementSibling)">{{ $question->email }}</span><small
                                            class="text-muted ms-2" style="display:none;">{{ __('admin.ui.da_sao_chep') }}</small>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="mb-3">
                            <p class="text-muted  mb-0">{{ __('admin.ui.tieu_de') }}</p>
                            <div class='row'>
                                <span>{{ $question->title }}</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <p class="text-muted mb-0">{{ __('admin.ui.noi_dung') }}</p>
                            <span>{{ $question->content }}</span>
                        </div>

                        <div>
                            <i class="ri-timer-line"></i>
                            <small>{{ __('admin.ui.thoi_gian_gui') }} {{ $question->created_at->format('d/m/Y H:i') }}</small>
                        </div>
                        <div class="mb-3 mt-3">
                            <label for="answer" class="form-label fw-bold fs-5">{{ __('admin.ui.tra_loi') }}</label>
                            <textarea class="form-control" id="answer" name="answer" rows="5" required>{{ old('answer', $question->answer) }}</textarea>
                        </div>
                        <div class='card-body d-flex  align-items-center justify-content-end gap-2'>
                            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.hoi-dap.index') }}" type="button"
                                class="btn btn-outline-secondary waves-effect text-none">{{ __('admin.ui.huy_bo') }}</a>
                            <button type="submit" class="btn btn-primary waves-effect waves-light text-none" name="submit"
                                value="cc">{{ __('admin.ui.cap_nhat') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
