@extends('layouts.layoutMaster')

@section('title', '403 - Access Denied')

@section('page-style')
    {{-- @vite(['Modules/DaoTaoLaiXe/resources/assets/css/customStyle.css']) --}}

@endsection

@section('content')
    <div class="container valign-wrapper">
        <div class="row center-align" style="width: 100%;">
            <div class="col s12 m8 offset-m2 l6 offset-l3">
                <div class="card shadow-none bg-danger-subtle z-depth-5 p-10">
                    <div class="card-content text-center">
                        <span class="card-title">
                            <i class="ri-lock-fill ri-4x text-darken-2"></i>
                        </span>
                        {{-- <h1 class="display-1 mb-2 mt-2 text-danger">403</h1> --}}
                        <h5 class="grey-text text-darken-3 text-danger">Truy cập bị từ chối 🔐</h5>
                        <p class="grey-text">Bạn không có quyền truy cập trang này. Hãy kiểm tra lại hoặc liên hệ quản trị
                            viên.</p>
                    </div>
                    <div class="card-action text-center">
                        <a href="{{ url()->previous() }}"
                            class="btn btn-primary waves-effect waves-light deep-purple darken-3 text-none">
                            <i class="ri-arrow-go-back-line left me-2"></i> Quay lại
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
