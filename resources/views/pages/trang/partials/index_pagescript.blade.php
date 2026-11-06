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
    // svgColor: 'rgb(253 181 40)',
    // });
    const form = $('#searchFormTrang');
const formData = form.serialize(); // Lấy dữ liệu từ form

$.ajax({
    url: form.attr('action') || '{{ \Modules\CmsBackend\Support\ModuleUrl::route("trang.index") }}', // Sử dụng action của form hoặc route index
    type: 'GET',
    data: formData,
    success: function(response) {
    $('#table-trang-container').html(response); // Thay thế nội dung bảng
    _init(); // Khởi tạo lại các sự kiện
    },
    error: function(xhr) {
    toastr.error('Có lỗi xảy ra, không thể tìm kiếm');
    }
});
    // Notiflix.Loading.remove();
}

// Event listener for form submission
$('#searchFormTrang').on('submit', function (e) {
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
            reloadTable();
            toastr.success(data.message);
        })
        .catch(er => {
            console.log('er',er);
            toastr.error('Có lỗi xảy ra, không thể xóa');
        });
    };
    });
});
}
_init();

</script>
