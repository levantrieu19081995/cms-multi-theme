@extends('layouts/layoutMaster')
@php
$colors = ['text-primary', 'text-success', 'text-danger', 'text-info', 'text-secondary', 'text-warning'];

@endphp
{{-- @extends('layouts/layoutMaster') --}}

@section('title', 'Dashboard - Analytics')
@section('vendor-style')

@endsection

@section('page-style')

@endsection

@section('vendor-script')
@vite(Theme::asset(['assets/vendor/libs/apex-charts/apexcharts.js']))
@endsection

@section('page-script')
@vite(['Modules/DaoTaoLaiXe/resources/assets/js/dashboard.js'])
@endsection

@section('content')
<h1>Dashboard</h1>
<div class="row g-6 mt-6">
    <div class="col-lg-12 col-sm-12">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title m-0 me-2">Số lượng học viên đang theo học</h5>
                <div class="dropdown">
                    <button class="btn btn-text-secondary rounded-pill text-muted border-0 p-1" type="button"
                        id="topic" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="ri-more-2-line ri-20px"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="topic">
                        <a class="dropdown-item" href="#" onclick="getSoLuongHocVienTheoHangGPLX(false)">Mặc
                            định</a>
                        <a class="dropdown-item" href="#" onclick="getSoLuongHocVienTheoHangGPLX(true)">Xem tất
                            cả</a>
                    </div>
                </div>
            </div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <div id="horizontalBarChart"></div>
                </div>
                <div class="col-md-6">
                    <div id="list-hoc-vien-theo-hang" class="row"></div>

                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-12 col-sm-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                @php
                $now = now();

                $quy = $quy ?? ceil($now->month / 3);

                $nam = $now->year;
                @endphp
                <div id="doanh-thu"></div>
                <div class="dropdown">
                    <button type="button" class="btn dropdown-toggle px-0" data-bs-toggle="dropdown"
                        aria-expanded="false"><i class="ri-calendar-2-line"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @for ($i = $quy - 1; $i <= $quy; $i++)
                            <li>
                            <a href="#" class="dropdown-item d-flex align-items-center"
                                onclick="getDoanhThuTheoQuy({{ $i }}, {{ $nam }})">
                                Quý {{ $i }} - năm {{ $nam }}
                            </a>
                            </li>
                            @endfor

                            <li>
                                <hr class="dropdown-divider">
                            </li>

                            @for ($i = $nam; $i >= $nam - 4; $i--)
                            <li>
                                <a href="#" class="dropdown-item d-flex align-items-center"
                                    onclick="getDoanhThuTheoQuy(null,{{ $i }})">
                                    {{ $i }}
                                </a>
                            </li>
                            @endfor


                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div id="horizontalBarChart1"></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-6 mt-6">
    <div class="col-sm-6 col-xl-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="me-1">
                        <p class="text-heading mb-1">Tổng số học viên</p>
                        <div class="d-flex align-items-center">
                            <h4 class="mb-1 me-2">{{ $tongSoHocVien ?? '' }}</h4>
                        </div>
                        <small class="mb-0">Cập nhật 1 phút trước</small>
                    </div>
                    <div class="avatar">
                        <div class="avatar-initial bg-label-primary rounded-3">
                            <div class="ri-group-line ri-26px"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="me-1">
                        <p class="text-heading mb-1">Học viên nhập học trong tháng</p>
                        <div class="d-flex align-items-center">
                            <h4 class="mb-1 me-1">{{ $slHocVienNhapHocTrongThang['slHocVienThangHienTai'] ?? '' }}</h4>
                            {{-- @if ($slHocVienNhapHocTrongThang['percent'] < 0)
                                    <p class="text-danger mb-1">({{ $slHocVienNhapHocTrongThang['percent'] }}%)</p>
                            @else
                            <p class="text-success mb-1">({{ $slHocVienNhapHocTrongThang['percent'] }}%)</p>
                            @endif --}}
                        </div>
                        <small class="mb-0">Cập nhật 1 phút trước</small>
                    </div>
                    <div class="avatar">
                        <div class="avatar-initial bg-label-danger rounded-3">
                            <div class="ri-user-add-line ri-26px"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="me-1">
                        <p class="text-heading mb-1">Học viên thi sát hạch trong tháng</p>
                        <div class="d-flex align-items-center">
                            <h4 class="mb-1 me-1">545</h4>
                            <p class="text-success mb-1">(+14%)</p>
                        </div>
                        <small class="mb-0">Cập nhật 1 ngày trước</small>
                    </div>
                    <div class="avatar">
                        <div class="avatar-initial bg-label-warning rounded-3">
                            <div class="ri-user-search-line ri-26px"></div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="me-1">
                        <p class="text-heading mb-1">Học viên đã hoàn thành khóa học</p>
                        <div class="d-flex align-items-center">
                            <h4 class="mb-1 me-1">2,376</h4>

                        </div>
                        <small class="mb-0">Cập nhật 1 tuần trước</small>
                    </div>
                    <div class="avatar">
                        <div class="avatar-initial bg-label-success rounded-3">
                            <div class="ri-user-follow-line ri-26px"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection