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
      url: form.attr('action') || '{{ route('binh-luan.index') }}', // Sử dụng action của form hoặc route index
      type: 'GET',
      data: formData,
      success: function(response) {
        $('#table-bai-viet-container').html(response); // Thay thế nội dung bảng
        _init(); // Khởi tạo lại các sự kiện
      },
      error: function(xhr) {
        toastr.error('Có lỗi xảy ra, không thể tìm kiếm');
      }
    });
    // Notiflix.Loading.remove();
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
    $('a[data-action=menu-approval]').on('click', function () {
      // Init value
      var id = $(this).data('id');
      var url = $(this).data('route');
      var csrf = $('meta[name=csrf-token]').attr('content');
      var formData = new FormData();
      formData.append('_token', csrf);
      formData.append('_method', 'put');
      fetch('binh-luan/'+id+'/approve', { method: 'post', body: formData })
        .then(res => res.json())
        .then(data => {
            reloadTable();
            toastr.success(data.message);
        })
        .catch(er => {
          console.log('er',er);
          toastr.error('Có lỗi xảy ra, không thể duyệt');
        });
    });
    $('a[data-action=menu-reject]').on('click', function () {
      console.log('áđá');
      // Init value
      var id = $(this).data('id');
      var url = $(this).data('route');
      var csrf = $('meta[name=csrf-token]').attr('content');
      var formData = new FormData();
      formData.append('_token', csrf);
      formData.append('_method', 'put');
      fetch('binh-luan/'+id+'/reject', { method: 'post', body: formData })
        .then(res => res.json())
        .then(data => {
            reloadTable();
            toastr.success(data.message);
        })
        .catch(er => {
          console.log('er',er);
          toastr.error('Có lỗi xảy ra');
        });
    });
 document.querySelectorAll('.btn-reply').forEach(button => {
    button.addEventListener('click', function () {
      const commentId = this.getAttribute('data-comment-id');
      document.getElementById('comment_id').value = commentId;
      const trangThai = $(this).data('trang-thai');
      const replyModal = new bootstrap.Modal(document.getElementById('replyModal'), {});
      const checkApproval = document.getElementById('check-approved');
      if(trangThai==='approved'){
        checkApproval.style.display = 'none';
      }else{
        checkApproval.style.display = 'inline-block';
      }
      replyModal.show();
    });
  });
document.querySelector('#replyModal form').addEventListener('submit', function (event) {
      event.preventDefault();
      const modal = document.getElementById('replyModal')

    const commentId = document.getElementById('comment_id').value;
    const replyContent = document.querySelector('textarea[name="reply_content"]').value;
    const approveComment = document.querySelector('input[name="approve_comment"]').checked;
      const replyUrl = @json(route('binh-luan.reply', ':id'));
      fetch(replyUrl.replace(':id', commentId), {
          method: 'post',
          headers: {
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'Content-Type': 'application/json',
          },
          body: JSON.stringify({
               _method: 'put',
               _token: '{{ csrf_token() }}',
              reply_content: replyContent,
              approve_comment: approveComment
          })
      })
      .then(response => {
          if (response.ok) {
              return response.json();
          }
          return Promise.reject('Failed to load');
      })
      .then(data => {
        console.log(data);
          if (data.status===1) {
              // Đóng modal sau khi trả lời thành công
              const replyModal = bootstrap.Modal.getInstance(document.getElementById('replyModal'));
              if (replyModal) {
                  replyModal.hide();
              }

              // Xử lý UI hoặc thông báo thành công
              // alert('Đã gửi trả lời thành công');
              toastr.success(data.message);
              reloadTable();
              // Cập nhật danh sách bình luận nếu cần
          } else {
              // alert('Đã xảy ra lỗi khi gửi trả lời.');
          }
      })
      .catch(error => {
          console.error('Error:', error);
          console.log(error);
          toastr.error('Có lỗi xảy ra',error);
          // alert('Đã xảy ra lỗi khi gửi trả lời.');
      });
  });
  

  }
  _init();

</script>