const swalWithBootstrapButtons = Swal.mixin({
  customClass: {
    confirmButton: "btn btn-danger",
    cancelButton: "btn btn-default"
  },
  buttonsStyling: false
});

function reloadTable() {
  Notiflix.Loading.standard( {
    svgColor: 'rgb(253 181 40)',
  });
  const form = $('#searchForm');
const formData = form.serialize(); // Lấy dữ liệu từ form

  $.ajax({
    url: form.attr('action') || '{{ \Modules\CmsBackend\Support\ModuleUrl::route("cms.dashboard.su-kien.index") }}', // Sử dụng action của form hoặc route index
    type: 'GET',
    data: formData,
    success: function(response) {
      $('#table-su-kien-container').html(response); // Thay thế nội dung bảng
      _init(); // Khởi tạo lại các sự kiện
    },
    error: function(xhr) {
      toastr.error(window.cmsAdminTranslations.co_loi_xay_ra_khong_the_tim_kiem);
    }
  });
 Notiflix.Loading.remove();
}

// Event listener for form submission
$('#searchForm').on('submit', function (e) {
e.preventDefault(); // Prevent normal form submission

reloadTable(); // Call reload function with search

});
const _init = () => {
$('a[data-action=su-kien-delete]').on('click', function () {
  // Init value
  console.log('>>>>>>>>>>>!11111111111111111111111');
  
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
    title: window.cmsAdminTranslations.ban_co_chac_muon_xoa,
    text: window.cmsAdminTranslations.du_lieu_da_xoa_se_khong_the_phuc_hoi,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: window.cmsAdminTranslations.dong_y,
    cancelButtonText: window.cmsAdminTranslations.cancel_short,
    reverseButtons: false
  }).then((result) => {
    if (result.isConfirmed) {
      fetch(url, { method: 'post', body: formData })
        .then(res => res.json())
        .then(data => {
            reloadTable();
            toastr.success(data.message);
        })
        .catch(er => {
          console.log('er',er);
          toastr.error(window.cmsAdminTranslations.co_loi_xay_ra_khong_the_xoa);
        });
    };
  });
});
}
_init();
