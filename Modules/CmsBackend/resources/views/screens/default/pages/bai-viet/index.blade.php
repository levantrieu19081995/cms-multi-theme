@php
    use Modules\CmsBackend\Enums\PermissionsEnum;
    use App\Enums\RolesEnum;
@endphp

@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.ui.quan_ly_bai_viet'))

@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.scss']))
@endsection

@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.js', 'assets/vendor/libs/bootstrap-select/bootstrap-select.js', 'assets/vendor/libs/tagify/tagify.js']))
@endsection

{{-- @section('page-script')
    @vite(['Modules/CmsBackend/resources/assets/js/app.js', 
    'Modules/CmsBackend/resources/assets/js/confirm-delete.js', 
    'resources/assets/js/forms-selects.js', 
    'Modules/CmsBackend/resources/assets/js/bai-viet.js'])
@endsection --}}

@section('page-script')
    @vite([
        'Modules/CmsBackend/resources/assets/js/app.js',
        'Modules/CmsBackend/resources/assets/js/confirm-delete.js',
        'resources/assets/js/forms-selects.js',
        'Modules/CmsBackend/resources/assets/js/bai-viet.js'
    ])

    <script>
      document.addEventListener("DOMContentLoaded", function () {
        const modeSel = document.getElementById("export_time_mode");
        const btnPreview = document.getElementById("btnPreviewExport");

        function applyModeUI() {
          if (!modeSel) return;
          const mode = modeSel.value;

          document.querySelectorAll(".export-mode-range")
            .forEach(el => el.classList.toggle("d-none", mode !== "range"));

          document.querySelectorAll(".export-mode-quarter")
            .forEach(el => el.classList.toggle("d-none", mode !== "quarter"));

          document.querySelectorAll(".export-mode-year")
            .forEach(el => el.classList.toggle("d-none", mode !== "year"));
        }

        function escapeHtml(s) {
          return (s ?? "").replace(/[&<>"']/g, (m) => ({
            "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;"
          }[m]));
        }

        applyModeUI();
        modeSel?.addEventListener("change", applyModeUI);

        btnPreview?.addEventListener("click", async () => {
          const form = document.getElementById("exportForm");
          const tbody = document.getElementById("export_preview_tbody");
          const meta = document.getElementById("export_preview_meta");

          try {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">${@json(__('admin.ui.dang_tai'))}</td></tr>`;
            meta.textContent = "";

            const previewUrl = form?.dataset?.previewUrl;
            if (!previewUrl) throw new Error(@json(__('admin.js.thieu_data_preview_url_tren_form')));

            const params = new URLSearchParams(new FormData(form));
            const url = previewUrl + "?" + params.toString();

            const res = await fetch(url, { headers: { "Accept": "application/json" } });
            if (!res.ok) throw new Error("Preview API error: " + res.status);

            const json = await res.json();
            const rows = json.data || [];
            meta.textContent = @json(__('admin.js.rows_preview')).replace(':total', json.total ?? rows.length).replace(':shown', rows.length);

            if (!rows.length) {
              tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">${@json(__('admin.ui.khong_co_du_lieu'))}</td></tr>`;
              return;
            }

            tbody.innerHTML = rows.map(r => `
            <tr>
                <td>${r.tt ?? ""}</td>
                <td>${escapeHtml(r.title ?? "")}</td>
                <td>${escapeHtml(r.type ?? "")}</td>
                <td>${escapeHtml(r.category ?? "")}</td>
                <td>${escapeHtml(r.author_name ?? "")}</td>
                <td>${escapeHtml(r.publisher_name ?? "")}</td>
                <td>${escapeHtml(r.published_at ?? "")}</td>
                <td>
                ${r.link ? `<a href="${r.link}" target="_blank" rel="noopener">${escapeHtml(r.link)}</a>` : ""}
                </td>
            </tr>
            `).join("");


          } catch (e) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">${@json(__('admin.ui.preview_failed'))}</td></tr>`;

          }
        });

        // Optional: mỗi lần mở modal thì reset preview
        const exportModal = document.getElementById("exportModal");
        exportModal?.addEventListener("show.bs.modal", () => {
          applyModeUI();
          const tbody = document.getElementById("export_preview_tbody");
          const meta = document.getElementById("export_preview_meta");
          if (tbody) tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">${@json(__('admin.ui.preview_hint'))}</td></tr>`;
          if (meta) meta.textContent = @json(__('admin.js.0_dong'));
        });
      });
    </script>
@endsection


@section('page-style')
    @vite(['resources/assets/css/bai-viet-be.css'])
    <style>
    /* full màn hình (chừa 12px viền cho đẹp) */
    .export-modal-full .export-modal-dialog{
        width: calc(100vw - 24px);
        max-width: calc(100vw - 24px);
        height: calc(100vh - 24px);
        margin: 12px auto;
        padding: 0;
    }

    .export-modal-full .modal-content{
        height: calc(100vh - 24px);
        display: flex;
        flex-direction: column;
        border-radius: 12px;
        overflow: hidden; /* quan trọng: tránh lòi/che */
    }

    /* header/footer sticky để không bị che khi scroll */
    .export-modal-full .modal-header{
        position: sticky;
        top: 0;
        z-index: 5;
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,.08);
    }

    .export-modal-full .modal-footer{
        position: sticky;
        bottom: 0;
        z-index: 5;
        background: #fff;
        border-top: 1px solid rgba(0,0,0,.08);
    }

    /* chỉ modal-body scroll */
    .export-modal-full .modal-body{
        flex: 1 1 auto;
        overflow: auto;
        min-height: 0; /* cực quan trọng để flex + overflow hoạt động đúng */
    }

    /* table responsive vẫn ok (scroll ngang nếu cần) */
    .export-modal-full .table-responsive{
        overflow: auto;
    }

    /* Scroll riêng cho danh sách xem trước */
    .export-modal-full .export-preview-scroll{
    max-height: 55vh;            /* chỉnh tuỳ ý: 50-65vh */
    overflow: auto;
    border: 1px solid rgba(0,0,0,.08);
    border-radius: 10px;
    }

    /* Sticky header của bảng khi scroll */
    .export-modal-full .export-preview-scroll thead th{
    position: sticky;
    top: 0;
    z-index: 2;
    background: #f8f9fa;         /* giống bootstrap table head */
    }

    /* Optional: tránh bị “nhảy” khi có scrollbar */
    .export-modal-full .export-preview-scroll{
    scrollbar-gutter: stable;
    }

    </style> 
@endsection


@section('content')
    {{ Breadcrumbs::render('cms.dashboard.cdn.bai-viet.index', '') }}
    <div class="card">
        <h5 class="card-header bg-primary text-white text-uppercase">{{ __('admin.ui.danh_sach_bai_viet') }}</h5>
        <div class="card-body mt-5">
            <div class="card shadow-none custom-card">
                <form id="searchForm"
                    action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.bai-viet.index', ['mode' => request()->mode, 'state' => request()->state, 'pageSize' => request()->pageSize]) }}"
                    method="get">


                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
                        <div class="input-group input-group-merge bg-white">
                            <span class="input-group-text" id="basic-addon-search31">
                                <i class="ri-search-line"></i>
                            </span>
                            <input type="search" id="filter" name="filter" class="form-control"
                                placeholder="{{ __('admin.ui.search_placeholder') }}" aria-label="{{ __('admin.ui.search_placeholder') }}" value="{{ request()->filter }}">
                            <a href="#advanced-filter" class="btn btn-outline-secondary waves-effect bg-white text-none"
                                type="button" data-bs-toggle="collapse" id="toggleFilter">{{ __('admin.ui.nang_cao') }}</a>
                        </div>


                        <button type="submit" class="btn btn-outline-secondary waves-effect waves-light bg-white">
                            <span class="tf-icons ri-search-line ri-16px me-2"></span>{{ __('admin.ui.search') }}
                        </button>
                    </div>
                    <div class="collapse row @if (request()->postStatuses || request()->userStatuses) show @endif " id="advanced-filter">
                        <div class="col-md-6 mb-5">
                            <div class="form-floating form-floating-outline">
                                <select id="postStatus" name="postStatuses[]" class="select2 form-select form-select-lg"
                                    placeholder="{{ __('admin.ui.select_status') }}" data-allow-clear="true" multiple>
                                    @foreach ($postStatuses as $status)
                                        <option value="{{ Illuminate\Support\Str::toBase64($status['enum']->value) }}"
                                            @if ($status['selected']) selected @endif>
                                            {{ $status['enum']->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="postStatus">{{ __('admin.ui.trang_thai') }}</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-5">
                            <div class="form-floating form-floating-outline">
                                <select id="category" name="categories[]" class="select2 form-select form-select-lg"
                                    data-allow-clear="true" multiple>
                                    @foreach ($categories as $cate)
                                        <option value="{{ Illuminate\Support\Str::toBase64($cate->term_taxonomy_id) }}"
                                            @if ($cate['selected']) selected @endif>
                                            {{ $cate->term->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="category">{{ __('admin.ui.chuyen_muc') }}</label>
                            </div>
                        </div>
                    </div>


                </form>
            </div>

            <div class="table-responsive text-nowrap" id="table-trang-container">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
                    @can(PermissionsEnum::BAIVIET_CREATE->label())
                    <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.bai-viet.create') }}"
                        type="button"class="btn btn-primary waves-effect text-none">
                        <span class="tf-icons ri-add-line ri-16px me-2"></span>{{ __('admin.ui.add_new') }}
                    </a>
                    @endcan

                    
                    @php
                        $hasPosts = isset($posts)
                        ? (method_exists($posts, 'total') ? $posts->total() > 0 : $posts->count() > 0)
                        : false;
                    @endphp

                    @if($hasPosts)
                        {{-- <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.bai-viet.export', request()->all()) }}"
                        class="btn btn-primary waves-effect text-none">
                        <span class="tf-icons ri-download-2-line ri-16px me-2"></span>Xuất danh sách
                        </a> --}}

                        <button type="button"
                            class="btn btn-primary waves-effect text-none"
                            data-bs-toggle="modal"
                            data-bs-target="#exportModal">
                            <span class="tf-icons ri-download-2-line ri-16px me-2"></span>{{ __('admin.ui.xuat_danh_sach') }}
                        </button>

                    @endif
                </div>
            </div>

            <!-- Danh sách bài viết -->
            <div class="table-responsive text-nowrap" id="table-bai-viet-container">
                @include('cmsbackend::screens.components.partials.post.table-post', [
                    'mode' => 'normal',
                    'type' => 'post',
                    'route' => 'cms.dashboard.cdn.bai-viet',
                    'tableId' => 'post',
                ])
            </div>
        </div>
    </div>

    <!-- Export Modal --> 
        <div class="modal fade export-modal-full" id="exportModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog export-modal-dialog">
                <div class="modal-content">  
                    <form id="exportForm"
                        method="GET"
                        data-preview-url="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.bai-viet.export.preview') }}"
                        action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.bai-viet.export') }}"
                        >
                        <div class="modal-header">
                        <h5 class="modal-title">{{ __('admin.ui.xuat_danh_sach_bai_viet') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">

                        {{-- giữ lại filter hiện tại (status/category/tag/...) --}}
                        @foreach(request()->all() as $k => $v)
                            @if(is_array($v))
                            @foreach($v as $vv)
                                <input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">
                            @endforeach
                            @else
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                            @endif
                        @endforeach

                        <div class="row g-3 align-items-end mb-3">
                            <div class="col-md-3">
                            <label class="form-label fw-semibold">{{ __('admin.ui.che_do_loc_thoi_gian') }}</label>
                            <select class="form-select" id="export_time_mode" name="time_mode">
                                <option value="range" selected>{{ __('admin.ui.tu_ngay_den_ngay') }}</option>
                                <option value="quarter">{{ __('admin.ui.theo_quy') }}</option>
                                <option value="year">{{ __('admin.ui.theo_nam') }}</option>
                            </select>
                            </div>

                            <div class="col-md-3 export-mode export-mode-range">
                            <label class="form-label fw-semibold">{{ __('admin.ui.tu_ngay') }}</label>
                            <input type="date" class="form-control" name="from_date" id="export_from_date">
                            </div>

                            <div class="col-md-3 export-mode export-mode-range">
                            <label class="form-label fw-semibold">{{ __('admin.ui.den_ngay') }}</label>
                            <input type="date" class="form-control" name="to_date" id="export_to_date">
                            </div>

                            <div class="col-md-2 export-mode export-mode-quarter d-none">
                            <label class="form-label fw-semibold">{{ __('admin.ui.quy') }}</label>
                            <select class="form-select" name="quarter" id="export_quarter">
                                <option value="1">{{ __('admin.ui.quy_1') }}</option>
                                <option value="2">{{ __('admin.ui.quy_2') }}</option>
                                <option value="3">{{ __('admin.ui.quy_3') }}</option>
                                <option value="4">{{ __('admin.ui.quy_4') }}</option>
                            </select>
                            </div>

                            <div class="col-md-2 export-mode export-mode-quarter d-none">
                            <label class="form-label fw-semibold">{{ __('admin.ui.nam') }}</label>
                            <input type="number" class="form-control" name="year_quarter" id="export_year_quarter" placeholder="2026">
                            </div>

                            <div class="col-md-3 export-mode export-mode-year d-none">
                            <label class="form-label fw-semibold">{{ __('admin.ui.nam') }}</label>
                            <input type="number" class="form-control" name="year" id="export_year" placeholder="2026">
                            </div>

                            <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('admin.ui.tim_theo_key') }}</label>
                            <input type="text" class="form-control" name="export_key" id="export_key" placeholder="{{ __('admin.ui.nhap_tieu_de_slug') }}">
                            </div>

                            <div class="col-md-2">
                            <button type="button" class="btn btn-outline-primary w-100" id="btnPreviewExport">
                                {{ __('admin.ui.xem_truoc') }}
                            </button>
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-semibold">{{ __('admin.ui.danh_sach_xem_truoc') }}</div>
                            <div class="text-muted small" id="export_preview_meta">{{ __('admin.js.0_dong') }}</div>
                        </div>

                        <div class="table-responsive export-preview-scroll">
                            <table class="table table-sm table-bordered align-middle">
                                <thead>
                                <tr>
                                    <th style="width:60px">TT</th>
                                    <th>{{ __('admin.ui.ten_tin_bai_trich_yeu') }}</th>
                                    <th style="width:120px">{{ __('admin.ui.loai_tin') }}</th>
                                    <th style="width:180px">{{ __('admin.resource.category') }}</th>
                                    <th style="width:140px">{{ __('admin.ui.tac_gia') }}</th>
                                    <th style="width:140px">{{ __('admin.ui.nguoi_dang') }}</th>
                                    <th style="width:140px">{{ __('admin.ui.ngay_dang') }}</th>
                                    <th style="width:220px">{{ __('admin.ui.duong_link') }}</th>
                                </tr>
                                </thead>

                                <tbody id="export_preview_tbody">
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                    {{ __('admin.ui.nhan_xem_truoc_de_tai_du_lieu') }}
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div> 
                        </div>

                        <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('admin.ui.huy') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="tf-icons ri-download-2-line ri-16px me-2"></span>{{ __('admin.ui.xuat') }}
                        </button>
                        </div>

                    </form>
            </div>
        </div>
    </div>

@endsection

