@extends('cmsbackend::shared.layouts/layoutMaster')
@section('title', __('admin.ui.admin_site_title'))
@php
    $colors = ['text-primary', 'text-success', 'text-danger', 'text-info', 'text-secondary', 'text-warning'];

@endphp
@section('vendor-style')

@endsection

@section('page-style')
    @vite('Modules/CmsBackend/resources/assets/sass/dashboard.scss')
@endsection
@php
    // Bổ sung tìm theo năm
    $currentYear = now()->year;
    $years = range($currentYear, $currentYear - 10); // 10 năm gần nhất
@endphp


@section('page-script')
    @vite('Modules/CmsBackend/resources/assets/js/chart-career.js')

    {{-- bài viết --}}
    @vite('Modules/CmsBackend/resources/assets/js/post-chart.js')
    {{-- @vite('Modules/CmsBackend/resources/assets/js/feedback-donut.js') --}}

    {{-- Bình  luận --}}
    @vite('Modules/CmsBackend/resources/assets/js/comment-radial.js')

    {{-- Liên hệ và góp ý --}}
    @vite('Modules/CmsBackend/resources/assets/js/post-timeline.js')
    <script>
        function updateTimeElapsed() {
            const timeElements = document.querySelectorAll('.time-elapsed');
            const now = new Date();

            timeElements.forEach(el => {
                const updatedAt = new Date(el.dataset.updatedAt);
                const diffInSeconds = Math.floor((now - updatedAt) / 1000);

                let display = '';
                if (diffInSeconds < 60) {
                    display = @json(__('admin.js.seconds_ago')).replace(':n', diffInSeconds);
                } else if (diffInSeconds < 3600) {
                    const mins = Math.floor(diffInSeconds / 60);
                    display = @json(__('admin.js.minutes_ago')).replace(':n', mins);
                } else if (diffInSeconds < 86400) {
                    const hours = Math.floor(diffInSeconds / 3600);
                    display = @json(__('admin.js.hours_ago')).replace(':n', hours);
                } else {
                    const days = Math.floor(diffInSeconds / 86400);
                    display = @json(__('admin.js.days_ago')).replace(':n', days);
                }

                el.innerText = display;
            });
        }

        updateTimeElapsed(); // Gọi khi load lần đầu
        setInterval(updateTimeElapsed, 60000); // Cập nhật mỗi phút
    </script>
@endsection




@section('content')
    <div class='row align-items-center mb-5 cms-reveal'>
        <div class='col-12 col-md-8 '>
            <h1 class='mb-0'>{{ __('admin.ui.tong_quan_noi_dung_va_tuong_tac') }}</h1>
        </div>
        <div class='col-12 col-md-4'>
            <form action="" method="get">
                <div class="row justify-content-between align-items-center">
                    <div class='col-6 col-md-3'>
                        <label for="year" class="form-label col-form-label fw-bold fs-5">{{ __('admin.ui.nam_thong_ke') }}</label>
                    </div>
                    <div class='col-6 col-md-9'>
                        <select name="year" id="year" class="form-control" onchange="this.form.submit()">
                            <option value="">{{ __('admin.ui.tat_ca_cac_nam') }}</option>
                            @foreach ($years as $year)
                                <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>
                                    {{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>


    {{-- Thông kê chữ --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="p-3 bg-white rounded shadow-sm cms-reveal" data-reveal-col="0">
                <p class="text-heading mb-1">{{ __('admin.ui.tong_bai_viet') }}</p>
                <h4 class="mb-0 fw-bold text-primary" data-countup>
                    {{ $totalPosts }} {{ __('admin.ui.bai_viet') }}
                </h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 bg-white rounded shadow-sm cms-reveal" data-reveal-col="1">
                <p class="text-heading mb-1">{{ __('admin.ui.tong_lien_he') }}</p>
                <h4 class="mb-0 fw-bold text-success" data-countup>{{ __('admin.ui.count_contacts', ['count' => $soLienHe ?? 0]) }}</h4>
            </div>
        </div>

        <div class="col-md-3">
            <div class="p-3 bg-white rounded shadow-sm cms-reveal" data-reveal-col="2">
                <p class="text-heading mb-1">{{ __('admin.ui.tong_binh_luan') }}</p>
                <h4 class="mb-0 fw-bold text-danger" data-countup>{{ array_sum(array_column($timelineBinhLuan, 'y')) ?? 0 }} {{ __('admin.ui.binh_luan_lower') }}</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 bg-white rounded shadow-sm cms-reveal" data-reveal-col="3">
                <p class="text-heading mb-1">{{ __('admin.ui.so_luot_khao_sat') }}</p>
                <h4 class="mb-0 fw-bold text-warning" data-countup>{{ __('admin.ui.count_surveys', ['count' => $totalKhaoSat ?? 0]) }}</h4>
            </div>
        </div>
    </div>
    {{-- Biểu đồ --}}
    <div class="row g-4 mt-6">
        <div class="col-12">
            <section class="card cms-post-stats cms-reveal" aria-labelledby="post-stats-title">
                <div class="card-body">
                    <header class="cms-post-stats__header">
                        <div>
                            <h2 id="post-stats-title">{{ __('admin.ui.thong_ke_bai_viet_theo_chuyen_muc') }}</h2>
                            <p><i class="ri-information-line" aria-hidden="true"></i> {{ __('admin.ui.chon_mot_chuyen_muc_de_xem_so_luong_bai_viet_chi') }}</p>
                        </div>
                        <div class="cms-post-stats__tabs" role="tablist" aria-label="{{ __('admin.ui.kieu_thong_ke_bai_viet') }}">
                            <button type="button" class="is-active" data-post-view="category" role="tab" aria-selected="true">{{ __('admin.ui.theo_chuyen_muc') }}</button>
                            <button type="button" data-post-view="month" role="tab" aria-selected="false">{{ __('admin.ui.theo_thang') }}</button>
                        </div>
                    </header>

                    <div class="cms-post-stats__layout">
                        <div class="cms-post-stats__chart-panel">
                            <div id="postChart"
                                data-categories='@json($postMonths)'
                                data-series='@json($postDataByCategory)'
                                data-category-status='@json($postStatusByCategory)'
                                data-month-status='@json($postStatusByMonth)'>
                            </div>
                        </div>

                        <aside class="cms-post-stats__detail" aria-live="polite">
                            <h3>{{ __('admin.record.show') }} <span id="postDetailKind">{{ __('admin.resource.category') }}</span></h3>
                            <div class="cms-post-stats__selected">
                                <span class="cms-post-stats__selected-icon"><i class="ri-seedling-line" aria-hidden="true"></i></span>
                                <strong id="postDetailTitle">{{ __('admin.ui.chua_co_du_lieu') }}</strong>
                            </div>
                            <div class="cms-post-stats__total"><strong id="postDetailTotal">0</strong><span>{{ __('admin.ui.bai_viet') }}</span></div>

                            <div class="cms-post-stats__status-grid">
                                <div class="cms-post-stats__status is-published">
                                    <span><i class="ri-checkbox-circle-fill" aria-hidden="true"></i> {{ __('admin.ui.da_xuat_ban') }}</span>
                                    <strong id="postDetailPublished">0</strong>
                                </div>
                                <div class="cms-post-stats__status is-pending">
                                    <span><i class="ri-time-fill" aria-hidden="true"></i> {{ __('admin.ui.pending_approval_short') }}</span>
                                    <strong id="postDetailPending">0</strong>
                                </div>
                                <div class="cms-post-stats__status is-draft">
                                    <span><i class="ri-file-text-fill" aria-hidden="true"></i> {{ __('admin.ui.ban_nhap') }}</span>
                                    <strong id="postDetailDraft">0</strong>
                                </div>
                            </div>

                            <div class="cms-post-stats__trend">
                                <h4 id="postTrendTitle">{{ __('admin.ui.xu_huong_bai_viet_theo_thang') }}</h4>
                                <div id="postTrendChart"></div>
                            </div>
                        </aside>
                    </div>

                    <footer class="cms-post-stats__footer">
                        <span><i class="ri-time-line" aria-hidden="true"></i> {{ __('admin.ui.cap_nhat') }} <span class="time-elapsed" data-updated-at="{{ $updatedAt->toIso8601String() }}">{{ __('admin.ui.0_giay_truoc') }}</span></span>
                        <span><i class="ri-cursor-line" aria-hidden="true"></i> {{ __('admin.ui.nhan_vao_thanh_de_xem_chi_tiet') }}</span>
                    </footer>
                </div>
            </section>
        </div>

        {{-- Box 2: 25% Liên hệ và góp ý --}}
        <div class="col-md-6">
            <div class="card h-100 cms-reveal" data-reveal-col="0">
                <div class="card-body">
                    <p class="text-heading mb-3">{{ __('admin.ui.lien_he_va_gop_y') }}</p>
                        
                        <div id="timelineChart"
                            data-series='@json($timelineData)'
                            style="height: 400px;">
                        </div>
                        <small class="mb-0 text-muted text-center">
                            {{ __('admin.ui.cap_nhat') }} <span class="time-elapsed" data-updated-at="{{ $updatedAt->toIso8601String() }}">{{ __('admin.ui.0_giay_truoc') }}</span>
                        </small>
                </div>
            </div>
        </div>

        {{-- Box 3: 25% Bình luận --}}
        <div class="col-md-6">
            <div class="card h-100 cms-reveal" data-reveal-col="1">
                <div class="card-body d-flex flex-column justify-content-between" style="height: 100%;">
                    <p class="text-heading mb-3">{{ __('admin.ui.binh_luan') }}</p>

                    <div class="d-flex justify-content-center align-items-center flex-grow-1">
                        
                        <div id="commentRadialChart"
                            data-series='@json($timelineBinhLuan)'
                            style="height: 400px;">
                        </div>
                    </div>

                    <small class="mb-0 text-muted text-center">
                        {{ __('admin.ui.cap_nhat') }} <span class="time-elapsed" data-updated-at="{{ $updatedAt->toIso8601String() }}">{{ __('admin.ui.0_giay_truoc') }}</span>
                    </small>
                </div>
            </div>
        </div>

        {{-- Box 4: Full width --}}
        <div class="col-12">
            <div class="card h-100 cms-reveal">
                <div class="card-body">
                    <p class="text-heading mb-1">{{ __('admin.ui.thong_ke_khao_sat_dinh_huong_nghe_nghiep') }}</p>
                    <div id="careerSurveyChart"
                        data-categories='@json($careerCategories)'
                        data-count='@json($careerSeries)'
                        style="height: 400px;">
                    </div>

                    <small class="mb-0 text-muted text-center">
                        {{ __('admin.ui.cap_nhat') }} <span class="time-elapsed" data-updated-at="{{ $updatedAt->toIso8601String() }}">{{ __('admin.ui.0_giay_truoc') }}</span>
                    </small>

                </div>
            </div>
        </div>
    </div>

@endsection
