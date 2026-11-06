const swalWithBootstrapButtons = Swal.mixin({
  customClass: {
    confirmButton: 'btn btn-danger',
    cancelButton: 'btn btn-default'
  },
  buttonsStyling: false
});

function reloadTable(id) {
  $('#' + id)
    .parent()
    .load(location.href + ' #' + id, _init);
}

const _init = () => {
  $('a[data-action=action-delete]').on('click', function () {
    // Init value
    var idTable = $(this).data('reload-table');
    var id = $(this).data('id');
    var url = $(this).data('route');
    var csrf = $('meta[name=csrf-token]').attr('content');
    var formData = new FormData();
    formData.append('_token', csrf);
    formData.append('_method', 'delete');

    swalWithBootstrapButtons
      .fire({
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
        title: 'Bạn có chắc muốn xóa?',
        text: 'Dữ liệu đã xóa sẽ không thể phục hồi!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Đồng ý',
        cancelButtonText: 'Hủy',
        reverseButtons: false
      })
      .then(result => {
        if (result.isConfirmed) {
          fetch(url, { method: 'post', body: formData })
            .then(res => res.json())
            .then(data => {
              if (data.status == 1) {
                // reloadTable(idTable);
                window.location.reload();
                toastr.success(data.message);
              } else {
                toastr.error(data.message);
              }
            })
            .catch(er => {
              toastr.error('Có lỗi xảy ra, không thể xóa');
            });
        }
      });
  });
};

_init();
