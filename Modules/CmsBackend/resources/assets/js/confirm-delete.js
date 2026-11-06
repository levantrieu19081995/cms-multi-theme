const swalWithBootstrapButtons = Swal.mixin({
  customClass: {
    cancelButton: 'btn btn-default text-none',
    confirmButton: 'btn btn-danger text-none'
  },
  buttonsStyling: false
});

// Hàm confirm xóa
const _init = (tableId, dataAction) => {
  $(document).off('click', 'a[data-action=' + dataAction + ']'); // Xóa sự kiện cũ để tránh trùng lặp
  $(document).on('click', 'a[data-action=' + dataAction + ']', function () {
    let id = $(this).data('id');
    let url = $(this).data('route');
    let csrf = $('meta[name=csrf-token]').attr('content');

    swalWithBootstrapButtons
      .fire({
        title: window.cmsAdminTranslations.ban_co_chac_muon_xoa,
        text: window.cmsAdminTranslations.du_lieu_da_xoa_se_khong_the_phuc_hoi,
        icon: 'warning',
        showCancelButton: true,
        cancelButtonText: window.cmsAdminTranslations.cancel,
        confirmButtonText: window.cmsAdminTranslations.dong_y,
        reverseButtons: false
      })
      .then(result => {
        if (result.isConfirmed) {
          fetch(url, {
            method: 'DELETE',
            headers: {
              'X-CSRF-TOKEN': csrf,
              'X-Requested-With': 'XMLHttpRequest',
              Accept: 'application/json', // Chắc chắn rằng client yêu cầu JSON
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({ _token: csrf })
          })
            .then(res => {
              if (!res.ok) {
                // Kiểm tra header có phải JSON không
                const contentType = res.headers.get('content-type');
                if (contentType && contentType.indexOf('application/json') !== -1) {
                  return res.json().then(err => {
                    console.error('------------------> trieuj khùng lol1111'.err);
                    throw new Error(err.message || window.cmsAdminTranslations.error_occurred);
                  });
                } else {
                  return res.text().then(text => {
                    console.error('------------------> trieuj khùng lolsss'.err);
                    throw new Error(text || window.cmsAdminTranslations.error_occurred);
                  });
                }
              }
              return res.json();
            })
            .then(data => {
              if (data.message) {
                // toastr.success(data.message);
                // // reloadTable(tableId); // Cập nhật bảng
                // location.reload();
                toastr.success(data.message);
                setTimeout(() => {
                  location.reload(); // reload trang sau khi thành công
                }, 1500); // 1000 ms = 1 giây
              } else {
                console.error('------------------> trieuj khùng lol'.err);
                throw new Error(window.cmsAdminTranslations.invalid_server_response);
              }
            })
            .catch(err => {
              console.error('------------------> trieuj khùng lol'.err);
              toastr.error(err.message || window.cmsAdminTranslations.delete_error);
            });
        }
      });
  });
};

// Áp dụng với tất cả bảng có data-action
$('table').each(function () {
  let tableId = $(this).data('table-id');
  let dataAction = $(this).data('action');
  _init(tableId, dataAction);
});
