<script type="module">
const swalWithBootstrapButtons = Swal.mixin({
    customClass: {
        confirmButton: "btn btn-danger",
        cancelButton: "btn btn-default"
    },
    buttonsStyling: false
    });

function reloadTable() {
    $("#table-cm").load(location.href + '#table-cm', _init);
}

const _init = () => {
    console.log('=============== object :>> ');
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
            console.log('===========> result :>> ', result); 
            console.log('===========> url :>> ', url); 
        // fetch(url, { method: 'post', body: formData })
        //     .then(res => res.json())
        //     .then(data => {
        //     if(data.status===0){
        //         toastr.error(data.message);
        //     }else{
        //         reloadTable();
        //         toastr.success(data.message);
        //     }

        //     })
        //     .catch(er => {
        //     toastr.error('Có lỗi xảy ra, không thể xóa');
        //     });
            fetch(url, { 
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 0) {
                    toastr.error(data.message);
                } else {
                    // reloadTable();
                    location.reload();
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
