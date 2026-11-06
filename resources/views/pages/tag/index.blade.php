@extends('layouts/layoutMaster')
@section('title', 'Chuyên mục')
@section('vendor-style')
@vite(Theme::asset([
'assets/vendor/libs/select2/select2.scss',
]))
@endsection

@section('vendor-script')
@vite(Theme::asset([
'assets/vendor/libs/select2/select2.js',
'assets/vendor/libs/bootstrap-select/bootstrap-select.js'
]))
@endsection

@section('page-script')
<script type="module">
  const swalWithBootstrapButtons = Swal.mixin({
      customClass: {
        confirmButton: "btn btn-danger",
        cancelButton: "btn btn-default"
      },
      buttonsStyling: false
    });

  function reloadTable() {
    $("#table-cm").load(location.href + ' #table-cm', _init);
  }

  const _init = () => {
    $('a[data-action=menu-delete]').on('click', function () {
      // Init value
      var id = $(this).data('id');
      var url = $(this).data('route');
      var csrf = $('meta[name=csrf-token]').attr('content');
      var formData = new FormData();
      formData.append('_token', csrf);
      formData.append('_method', 'delete');

      swalWithBootstrapButtons.fire({
        showClass: {
          popup: `
            animate__animated
            animate__fadeInUp
            animate__faster
          `
        },
        hideClass: {
          popup: `
            animate__animated
            animate__fadeOutDown
            animate__faster
          `
        },
        title: "Bạn có chắc muốn xóa?",
        text: "Dữ liệu đã xóa sẽ không thể phục hồi!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Đồng ý",
        cancelButtonText: "Hủy",
        reverseButtons: false
      }).then((result) => {
        if (result.isConfirmed) {
          fetch(url, { method: 'post', body: formData })
            .then(res => res.json())
            .then(data => {
              if(data.status===0){
                toastr.error(data.message);
              }else{
                reloadTable();
                toastr.success(data.message);
              }
              
            })
            .catch(er => {
              toastr.error('Có lỗi xảy ra, không thể xóa');
            });
        };
      });
    });
  }

  _init();
</script>

@endsection
@section('page-style')
@endsection
@section('content')


<div class="card">
  <h5 class="card-header">{{ __('Quản lý chuyên mục') }}</h5>
  <div class="card-body">
    <div>
      <form action="" method="get">
        <div class="input-group input-group-merge mb-6">
          <span class="input-group-text" id="basic-addon-search31"><i class="ri-search-line"></i></span>
          <input type="text" id="filter" name="filter" class="form-control" placeholder="Tìm kiếm..."
            aria-label="Tìm kiếm..." value="{{ request()->filter }}">
          {{-- <a href="#advanced-filter" class="btn btn-outline-secondary waves-effect" type="button"
            data-bs-toggle="collapse">Nâng cao</a> --}}
        </div>

        <div class="row">
          <div class="mb-6">
            <button type="submit" class="btn btn-primary waves-effect waves-light">Tìm</button>
            <a href="{{ route('chuyen-muc.create') }}" type="button" class="btn btn-outline-secondary waves-effect">Thêm
              mới</a>
          </div>
        </div>
      </form>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table table-bordered table-chuyen-muc" id="table-cm">
        <thead>
          <tr>
            <th style="width:5%">STT</th>
            <th>Tên chuyên mục</th>
            <th>Chuyên mục cha</th>
            <th style="width:10%">{{ __('Hành động') }}</th>
            {{-- <th>#</th> --}}
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @if($categories)
          @foreach ($categories as $index=> $cate)
          <tr>
            <th scope="row" class="text-center">{{$index+1 }}</th>
            <td>
              {{ $cate->term->name }}
            </td>
            <td>
              @if($cate->parent)
              {{ $cate->parent->term->name }}
              @endif
            </td>

            <td class="text-center">
              <div class="gap-50">
                <a href="{{ route('chuyen-muc.edit', $cate) }}"
                  class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect" data-bs-toggle="tooltip"
                  title="Xem">
                  <i class="ri-eye-line ri-20px"></i>
                </a>
                <button
                  class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow"
                  data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="ri-more-2-line ri-20px"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end m-0" style="">
                  <a href="{{ route('chuyen-muc.edit', $cate) }}" class="dropdown-item">
                    <i class="ri-eye-line me-2"></i><span>Xem</span>
                  </a>
                  <a href="{{ route('chuyen-muc.edit', $cate) }}" class="dropdown-item">
                    <i class="ri-edit-box-line me-2"></i><span>Sửa</span>
                  </a>
                  <a href="javascript:void(0)" class="dropdown-item delete-record" data-action="menu-delete"
                    data-id="{{ $cate->id }}" data-route="{{ route('chuyen-muc.destroy', $cate) }}">
                    <i class="ri-delete-bin-7-line me-2"></i><span>Xóa</span>
                  </a>
                </div>
              </div>

            </td>
          </tr>
          @endforeach
          @endif
          @if ($categories->isEmpty())
          <tr>
            <td colspan="100">Không tìm thấy dữ liệu</td>
          </tr>
          @endif

        </tbody>
      </table>
      <div class="form-layout-footer" style="margin-top: 20px; margin-right: 20px;margin-left: 20px;">
        {{ $categories->onEachSide(2)->links('layouts.paginate') }}
      </div>
    </div>
  </div>
</div>

@endsection