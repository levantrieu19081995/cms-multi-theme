@extends('layouts/layoutMaster')

@section('title', __('Quảng cáo') )

@section('vendor-style')
@endsection

@section('vendor-script')
@endsection

 


@section('content')

    <!-- Users List Table -->
    <div class="card">
        <h5 class="card-header">{{ __('Quảng cáo') }}</h5>
        <div class="card-body">
            <div>
              <form action="" method="get">
                <div class="input-group input-group-merge mb-6">
                  <span class="input-group-text" id="basic-addon-search31"><i class="ri-search-line"></i></span>
                  <input type="text" id="filter" name="filter" class="form-control" placeholder="Tìm kiếm..." aria-label="Tìm kiếm..."
                    value="{{ request()->filter }}">
                </div>
                <div class="row">
                  <div class="mb-6">
                    <button type="submit" class="btn btn-primary waves-effect waves-light">Tìm</button>
                    <a href="{{ route('quang-cao.create') }}" type="button" class="btn btn-outline-secondary waves-effect">Thêm mới</a>
                  </div>
                </div>
              </form>
            </div>
            <div class="table-responsive text-nowrap">
                <table id="role-table" class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 1%">STT</th>
                            <th>{{ __('Tiêu đề quảng cáo') }}</th>
                            <th>{{ __('Mô tả') }}</th>
                            <th>{{ __('Vị trí hiển thị') }}</th>
                            <th>{{ __('Ngày bắt đầu') }}</th>
                            <th>{{ __('Ngày kết thúc') }}</th>
                            <th style="width: 80px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ads as $ad)
                            <tr>
                                <td>{{ (($ads->currentPage() - 1) * $ads->perPage()) + $loop->index + 1 }}</td>
                                <td>{{ $ad->title }}</td>
                                <td>{{ $ad->description }}</td>
                                <td>
                                  @if ($ad->position)
                                  {{  $ad->position }}
                                  @endif
                                </td>
                                <td>{{ $ad->start_date->format('H:i d/m/Y') }}</td>
                                <td>{{ $ad->end_date->format('H:i d/m/Y') }}</td>
                                <td>
                                  <div class="d-flex align-items-center gap-50">
                                    {{-- <a href="{{ route('menus.edit', $ad) }}"
                                      class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect"
                                      data-bs-toggle="tooltip"
                                      title="Xem">
                                      <i class="ri-eye-line ri-20px"></i>
                                    </a> --}}
                                    <button
                                      class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow"
                                      data-bs-toggle="dropdown"
                                      aria-expanded="false">
                                      <i class="ri-more-2-line ri-20px"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end m-0" style="">
                                      <a href="{{ route('quang-cao.edit', $ad) }}" class="dropdown-item">
                                        <i class="ri-edit-box-line me-2"></i><span>Sửa</span>
                                      </a>
                                      <a href="javascript:void(0)" class="dropdown-item delete-record"
                                          data-action="role-delete"
                                          data-id="{{ $ad->id }}"
                                          data-route="{{ route('quang-cao.destroy', $ad) }}">
                                        <i class="ri-delete-bin-7-line me-2"></i><span>Xóa</span>
                                      </a>
                                    </div>
                                  </div>
                                </td>
                            </tr>
                        @endforeach
                        @if ($ads->isEmpty())
                          <tr>
                            <td colspan="100">Không tìm thấy dữ liệu</td>
                          </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="form-layout-footer" style="margin-top: 20px; margin-right: 20px;margin-left: 20px;">
              {{ $ads->onEachSide(2)->links('layouts.paginate') }}
            </div>
        </div>
    </div>
@endsection
