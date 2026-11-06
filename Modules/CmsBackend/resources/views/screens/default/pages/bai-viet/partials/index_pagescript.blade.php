@vite([
  'resources/assets/js/forms-selects.js',
])
<script type="module">
  const swalWithBootstrapButtons = Swal.mixin({
      customClass: {
        confirmButton: "btn btn-danger",
        cancelButton: "btn btn-default"
      },
      buttonsStyling: false
    });

    function reloadTable() {
      // Notiflix.Loading.standard( {
      //   svgColor: 'rgb(253 181 40)',
      // });
      const form = $('#searchForm');
    const formData = form.serialize(); // Lấy dữ liệu từ form

    $.ajax({
      url: form.attr('action') || '{{ \Modules\CmsBackend\Support\ModuleUrl::route('bai-viet.index', ['mode' => request()->mode, 'state' => request()->state, 'pageSize' => request()->pageSize,'page'=> request()->page]) }}', // Sử dụng action của form hoặc route index
      type: 'GET',
      data: formData,
      success: function(response) {
        $('#table-bai-viet-container').html(response); // Thay thế nội dung bảng
        _init(); // Khởi tạo lại các sự kiện
      },
      error: function(xhr) {
        toastr.error(@json(__('admin.js.co_loi_xay_ra_khong_the_tim_kiem')));
      }
    });
    //  Notiflix.Loading.remove();
  }

  // Event listener for form submission
  $('#searchForm').on('submit', function (e) {
    e.preventDefault(); // Prevent normal form submission

    reloadTable(); // Call reload function with search

  });
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
        title: @json(__('admin.js.ban_co_chac_muon_xoa')),
        text: @json(__('admin.js.du_lieu_da_xoa_se_khong_the_phuc_hoi')),
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: @json(__('admin.js.dong_y')),
        cancelButtonText: @json(__('admin.ui.huy')),
        reverseButtons: false
      }).then((result) => {
        if (result.isConfirmed) {
          fetch(url, { method: 'post', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.status ==1){
                  reloadTable();
                  toastr.success(data.message);
                }else{
                  toastr.error(data.message);
                }
                 
            })
            .catch(er => {
              console.log('er',er);
              toastr.error(@json(__('admin.js.co_loi_xay_ra_khong_the_xoa')));
            });
        };
      });
    });

    $('.switch-input').on('change', function () {
        const postId = $(this).data('post-id'); // Lấy ID bài viết

        // Gửi yêu cầu AJAX để cập nhật
        $.ajax({
            url: '{{ route('api.update-homepage') }}', // URL API
            method: 'POST', // Phương thức HTTP
            data: {
                id: postId,
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // CSRF token cho Laravel
            },
            success: function (response) {
                // Hiển thị thông báo thành công bằng Toastr.js
                toastr.success(response.message || @json(__('admin.js.cap_nhat_thanh_cong')));
                reloadTable();
            },
            error: function (xhr) {
                // Hiển thị thông báo lỗi
                toastr.error(xhr.responseJSON?.message || @json(__('admin.js.co_loi_xay_ra_vui_long_thu_lai')));
                reloadTable();
            }
        });
    });

    // Cho phép bình luận bài viết
    $('.switch-input-comment').on('change', function () {
        const postId = $(this).data('post-id'); // Lấy ID bài viết

        // Gửi yêu cầu AJAX để cập nhật
        $.ajax({
            url: '{{ route('api.update-commentpage') }}', // URL API
            method: 'POST', // Phương thức HTTP
            data: {
                id: postId,
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // CSRF token cho Laravel
            },
            success: function (response) {
                // Hiển thị thông báo thành công bằng Toastr.js
                toastr.success(response.message || @json(__('admin.js.cap_nhat_thanh_cong')));
                // reloadTable();
            },
            error: function (xhr) {
                // Hiển thị thông báo lỗi
                toastr.error(xhr.responseJSON?.message || @json(__('admin.js.co_loi_xay_ra_vui_long_thu_lai')));
                // reloadTable();
            }
        });
    });
  }



  
  _init();

</script>
