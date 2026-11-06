@extends('cmsfrontend::screens.components.layouts.blankLayout')
@section('title', __('pages.lien_he_gop_y'))
@section('description', __('pages.ket_noi_gui_thong_tin_va_gop_y_den_truong_dai_ho_2'))
@section('content')
<div class="cms-container sbut-contact"><section class="sbut-contact-intro"><span class="sbut-eyebrow">{{ __('frontend.ui.lang_nghe_se_chia') }}</span><h1>{{ __('frontend.ui.ket_noi') }}<br><em>{{ __('frontend.ui.cung_sbut') }}</em></h1><p>{{ __('frontend.ui.nha_truong_luon_san_sang_lang_nghe_thong_tin_cau') }}</p><a class="sbut-text-link" href="{{ route('cms.frontend.hoidap.index') }}">{{ __('frontend.ui.tham_khao_muc_hoi_dap') }}</a><div class="sbut-contact-mark" aria-hidden="true">SBUT</div></section><section class="sbut-contact-form" aria-labelledby="sbut-contact-title"><h2 id="sbut-contact-title">{{ __('frontend.ui.gui_loi_nhan_den_nha_truong') }}</h2>@if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
                <form method="POST" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.lien-he.store') }}">
                    @csrf
@include('cmsfrontend::screens.components.contact-honeypot')
                    {{-- Họ tên --}}
                    <div class="form-group mt-3">
                        <label for="name" class="form-label">{{ __('frontend.ui.ho_ten') }}</label>
                        <input type="text" name="name" id="name" maxlength="255" value="{{ old('name') }}"
                            class="form-control @error('name') is-invalid @enderror" required
                            placeholder="{{ __('frontend.ui.nhap_ho_ten_cua_ban') }}">
                        @error('name')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class='row'>
                        {{-- Email --}}
                        <div class="form-group mt-3 col-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" maxlength="255" value="{{ old('email', request('email')) }}"
                                class="form-control @error('email') is-invalid @enderror" required
                                placeholder="{{ __('pages.nhap_email_cua_ban') }}">
                            @error('email')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Điện thoại --}}
                        <div class="form-group mt-3 col-6">
                            <label for="phone" class="form-label">{{ __('frontend.ui.dien_thoai') }}</label>
                            <input type="text" name="phone" id="phone" maxlength="30" value="{{ old('phone') }}"
                                class="form-control @error('phone') is-invalid @enderror" required
                                placeholder="{{ __('pages.nhap_so_dien_thoai_cua_ban') }}">
                            @error('phone')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    {{-- Địa chỉ --}}
                    <div class="form-group mt-3">
                        <label for="address" class="form-label">{{ __('frontend.ui.dia_chi') }}</label>
                        <input type="text" name="address" id="address" maxlength="255" value="{{ old('address') }}"
                            class="form-control @error('address') is-invalid @enderror" required
                            placeholder="{{ __('pages.nhap_dia_chi_cua_ban') }}">
                        @error('address')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Tiêu đề --}}
                    <div class="form-group mt-3">
                        <label for="subject" class="form-label">{{ __('frontend.ui.tieu_de') }}</label>
                        <input type="text" name="subject" id="subject" maxlength="255" value="{{ old('subject') }}"
                            class="form-control @error('subject') is-invalid @enderror" required placeholder="{{ __('pages.nhap_tieu_de') }}">
                        @error('subject')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Nội dung --}}
                    <div class="form-group mt-3">
                        <label for="message" class="form-label">{{ __('frontend.ui.noi_dung') }}</label>
                        <textarea name="message" id="message" minlength="10" maxlength="5000" rows="4" class="form-control @error('message') is-invalid @enderror"
                            required placeholder="{{ __('pages.nhap_noi_dung_loi_nhan') }}">{{ old('message') }}</textarea>
                        @error('message')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="d-flex justify-content-end mt-6 ">
                        <button type="submit" class="btn fancy-hover-btn text-none btn-lg">
                            <span>{{ __('frontend.ui.gui_gop_y') }}</span>
                        </button>
                    </div>
                </form>
</section></div>
@endsection
