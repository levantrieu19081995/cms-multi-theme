@extends('cmsfrontend::screens.components.layouts.blankLayout')
@section('title', __('pages.dat_cau_hoi'))
@section('description', __('pages.gui_cau_hoi_va_trao_doi_thong_tin_voi_truong_dai_2'))
@section('content')
<div class="cms-container sbut-contact"><section class="sbut-contact-intro"><span class="sbut-eyebrow">{{ __('frontend.ui.hoi_dap_cung_nha_truong') }}</span><h1>{{ __('frontend.ui.ban_hoi') }}<br><em>{{ __('frontend.ui.sbut_lang_nghe') }}</em></h1><p>{{ __('frontend.ui.gui_cau_hoi_de_duoc_nha_truong_tiep_nhan_va_giai') }}</p><a class="sbut-text-link" href="{{ route('cms.frontend.hoidap.index') }}">{{ __('frontend.ui.xem_cac_cau_hoi_da_giai_dap') }}</a><div class="sbut-contact-mark" aria-hidden="true">SBUT</div></section><section class="sbut-contact-form" aria-labelledby="sbut-question-title"><h2 id="sbut-question-title">{{ __('frontend.ui.gui_cau_hoi_den_nha_truong') }}</h2>@if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
                <form method="POST" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.frontend.hoidap.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('frontend.ui.ho_ten') }}</label>
                        <input id="name" type="text" name="name" class="form-control"   value="{{ old('name') }}"
                            placeholder="{{ __('frontend.ui.nhap_ho_ten_cua_ban') }}">
                        @error('name')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class='row'>
                        <div class="mb-3 col-12 col-md-6">
                            <label for="phone" class="form-label">{{ __('frontend.ui.so_dien_thoai') }}</label>
                            {{-- <input id="phone" type="number" name="phone" class="form-control" value="{{ old('phone') }}"
                                placeholder='Nhập số điện thoại của bạn'> --}}
                                <input id="phone"
                                type="tel"
                                name="phone"
                                class="form-control"
                                value="{{ old('phone') }}"
                                placeholder="{{ __('frontend.ui.nhap_so_dien_thoai_cua_ban') }}"
                                inputmode="numeric"
                                autocomplete="tel"
                                pattern="^(0|\+84)\d{9,10}$"
                                title="{{ __('frontend.ui.so_dien_thoai_phai_bat_dau_bang_0_hoac_84_va_co_') }}"
                                />

                            @error('phone')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3 col-12 col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input id="email" type="email" name="email" class="form-control" value="{{ old('email') }}"
                                placeholder="{{ __('pages.nhap_email_cua_ban') }}">
                            @error('email')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label">{{ __('frontend.ui.tieu_de_cau_hoi') }}</label>
                        <input id="title" type="text" name="title" class="form-control"   value="{{ old('title') }}"
                            placeholder="{{ __('pages.nhap_tieu_de_cau_hoi') }}">
                        @error('title')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="content" class="form-label">{{ __('frontend.ui.noi_dung_cau_hoi') }}</label>
                        <textarea id="content" name="content" class="form-control"   rows="4" placeholder="{{ __('pages.nhap_noi_dung_cau_hoi') }}">{{ old('content') }}</textarea>
                        @error('content')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="d-flex align-items-center">
                        <span style="font-size: 1rem; margin-right: 0.5rem;">*</span>
                        <div>
                            <p class="text-muted mb-0 fst-italic">
                                {{ __('frontend.ui.chu_y_se_mat_mot_thoi_gian_de_chung_toi_tra_loi_') }}
                            </p>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end ">
                        <button type="submit" class="btn fancy-hover-btn text-none btn-lg">
                            <span>{{ __('frontend.ui.gui_cau_hoi') }}</span>
                        </button>
                    </div>
                </form>
</section></div>
@endsection
