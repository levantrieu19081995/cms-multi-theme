@extends('layouts/layoutMaster')

@section('title', 'Nhật ký người dùng')

@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.scss']))
@endsection

@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.js', 'assets/vendor/libs/bootstrap-select/bootstrap-select.js']))
@endsection

@section('page-script')
    @vite([
        'resources/assets/js/forms-selects.js',
        // 'resources/assets/js/activity-list.js'
    ])
@endsection

@section('content')
    <!-- activities List Table -->
    {{ Breadcrumbs::render('activities.index', '') }}
    <div class="card">
        <h5 class="card-header bg-primary text-white text-uppercase">{{ __('Nhật ký người dùng') }}</h5>
        <div class="card-body mt-5">
            <div>
                {{-- <form action="" method="get"> --}}
                <form action="{{ route('activities.index') }}" method="get"> 
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
                        <div class="input-group input-group-merge bg-white">
                            <span class="input-group-text" id="basic-addon-search31">
                                <i class="ri-search-line"></i>
                            </span>
                            <input type="search" id="filter" name="filter" class="form-control"
                                placeholder="Tìm kiếm..." aria-label="Tìm kiếm..." value="{{ request()->filter }}">
                            <a href="#advanced-filter" class="btn btn-outline-secondary waves-effect bg-white text-none"
                                type="button" data-bs-toggle="collapse" id="toggleFilter">Nâng cao</a>
                        </div>


                        <button type="submit" class="btn btn-outline-secondary waves-effect waves-light bg-white">
                            <span class="tf-icons ri-search-line ri-16px me-2"></span>Tìm
                        </button>
                    </div>
                    {{-- <div class="collapse row @if (request()->users) show @endif" id="advanced-filter"> --}}
                    <div class="collapse row @if (request()->hasAny(['filter','from_date','to_date','users'])) show @endif" id="advanced-filter">
                        <div class="col-md-2 mb-5">
                            <div class="form-floating form-floating-outline">
                                {{-- <input type="datetime-local" id="from_date" name="from_date" class="form-control"
                                    placeholder="Từ ngày" value="{{ $fromDate }}"> --}}
                                <input type="datetime-local" id="from_date" name="from_date" class="form-control"
                                    value="{{ $fromDate }}">
                                <label for="roles">Từ ngày</label>
                            </div>
                        </div>
                        <div class="col-md-2 mb-5">
                            <div class="form-floating form-floating-outline">
                                <input type="datetime-local" id="to_date" name="to_date" class="form-control"
                                    placeholder="Từ ngày" value="{{ $toDate }}">
                                <input type="datetime-local" id="to_date" name="to_date" class="form-control"
                                    value="{{ $toDate }}">
                                <label for="roles">Đến ngày</label>
                            </div>
                        </div>
                        <div class="col-md-8 mb-5">
                            <div class="form-floating form-floating-outline">
                                <select id="users" name="users[]" class="select2 form-select form-select-lg"
                                    data-allow-clear="true" multiple>
                                    @isset($users)
                                        @foreach ($users as $user)
                                            <option value="{{ Illuminate\Support\Str::toBase64($user->id) }}"
                                                @if ($user->selected) selected @endif>{{ $user->name }}</option>
                                        @endforeach
                                    @endisset
                                </select>
                                <label for="users">Người dùng</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="table-responsive text-nowrap">
                <table id="activity-table" class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 1%">STT</th>
                            <th>Hoạt động</th>
                            <th style="width: 200px">Người thực hiện</th>
                            <th style="width: 200px">Thời gian thực hiện</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($activities as $activity)
                            <tr>
                                <td>{{ ($activities->currentPage() - 1) * $activities->perPage() + $loop->index + 1 }}
                                </td>
                                <td>{{ $activity->description }}</td>
                                <td>
                                    @if ($activity->causer)
                                        {{ $activity->causer->name }}
                                    @else
                                        Khách vãng lai
                                    @endif
                                </td>
                                <td>
                                    {{ $activity->created_at->format('d/m/Y H:i:s') }}
                                </td>
                            </tr>
                        @endforeach
                        @if ($activities->isEmpty())
                            <tr>
                                <td colspan="100">Không tìm thấy dữ liệu</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            {{-- <div class="form-layout-footer" style="margin-top: 20px; margin-right: 20px;margin-left: 20px;">
                {{ $activities->onEachSide(2)->links('layouts.paginate') }}
            </div> --}}
            <div class="form-layout-footer" style="margin-top: 20px; margin-right: 20px;margin-left: 20px;">
            {{ $activities->onEachSide(2)
                            ->appends(request()->query())   
                            ->links('layouts.paginate') }}
            </div>
        </div>
    </div>
@endsection
