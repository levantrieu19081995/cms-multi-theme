// 'use strict';
// $(function () {
//   console.log('jsTree is running');
//   let theme = $('html').hasClass('light-style') ? 'default' : 'default-dark',
//     checkboxTree = $('#jstree-checkbox'),
//     userId = checkboxTree.data('user-id') !== '' ? checkboxTree.data('user-id') : -1,
//     selectedRoles = [],
//     treeData = [];

//   async function fetchData() {
//     try {
//       if (userId !== '') {
//         await getDsVaiTroByUserId(userId);
//       }
//       await getDsVaiTro();
//     } catch (error) {
//       console.error('Lỗi khi tải dữ liệu:', error);
//     }
//   }
//   //console.log('[]userId', userId);
//   // Danh sách tất cả quyền
//   async function getDsVaiTro() {
//     try {
//       const response = await fetch(`/api/vai-tro/getDsVaiTro`);
//       const data = await response.json();

//       if (data.code === 200) {
//         console.log('[]getDsVaiTro', data.data);
//         treeData = await buildTree(data.data); // ⏳
//         initJsTree(); // ✅ Khi có treeData
//       }
//     } catch (error) {
//       console.error('Lỗi khi tải dữ liệu ds Vai trò:', error);
//     }
//   }

//   // Lấy thông tin quyền mà user có
//   function getDsVaiTroByUserId(userId) {
//     return fetch(`/api/vai-tro/getDsVaiTroByUserId/${userId}`)
//       .then(response => response.json())
//       .then(data => {
//         if (data.code === 200) {
//           selectedRoles = data.data;
//           //console.log('[]selectedRoles', selectedRoles);
//         }
//       })
//       .catch(error => console.log('Error loading user roles:', error));
//   }

//   fetchData(); // Gọi hàm fetch dữ liệu đồng bộ

//   async function buildTree(data, parentId = null) {
//     const result = [];
//     for (const item of data.filter(d => {
//       // Nếu parentId là null hoặc -1, lấy các node root (parent_id null hoặc -1)
//       if (parentId === null || parentId === -1) {
//         return d.parent_id === null || d.parent_id === -1;
//       }
//       // Ngược lại, so sánh bình thường
//       return d.parent_id === parentId;
//     })) {
//       const children = await buildTree(data, item.id);
//       result.push({
//         id: item.id_encrypt,
//         id_encrypt: item.id,
//         text: item.name,
//         state: {
//           opened: children.length > 0,
//           selected: selectedRoles.some(obj => obj.id == item.id)
//         },
//         children: children
//       });
//     }
//     return result;
//   }

//   function initJsTree() {
//     //console.log('treeData:', treeData);
//     if (checkboxTree.length > 0 && treeData.length > 0) {
//       checkboxTree.jstree('destroy').empty();
//       checkboxTree.jstree({
//         core: {
//           themes: { name: theme, dots: true, icons: false },
//           data: treeData
//         },
//         plugins: ['types', 'checkbox'],
//         types: {
//           default: { icon: '' }
//         }
//       });

//       checkboxTree.on('changed.jstree', function (e, data) {
//         console.log('Tree changed:', data);

//         selectedRoles = data.selected.map(id => {
//           let node = data.instance.get_node(id);
//           return node.id;
//         });

//         //console.log('Selected Nodes:', selectedRoles);
//       });
//     }
//   }

//   document.getElementById('role-form').addEventListener('submit', function (e) {
//     e.preventDefault(); // Prevent normal form submission
//     save(); // Call reload function with search
//   });

//   function save() {
//     const roleForm = $('#role-form')[0];
//     let formData = new FormData(roleForm);
//     formData.append('permissions', JSON.stringify(selectedRoles));

//     console.log('Form Data:', selectedRoles);
//     // for (let [key, value] of formData.entries()) {
//     //   console.log(`${key}:`, value);
//     // }

//     // return;
//     $.ajax({
//       url: `/api/vai-tro/save/${userId}`,
//       type: 'POST',
//       data: formData,
//       processData: false,
//       contentType: false,
//       success: function () {
//         //console.log('Lưu thành công!');
//         let message =
//           userId !== -1 && userId !== null && userId !== ''
//             ? 'Cập nhật vai trò thành công!'
//             : 'Thêm mới vai trò thành công!';
//         toastr.success(message);
//       },
//       error: function (error) {
//         console.error('Lỗi khi lưu dữ liệu:', error);
//         // toastr.error('Lỗi khi lưu dữ liệu.');
//       }
//     });
//   }
//   // function save() {
//   //   const roleForm = $('#role-form')[0];
//   //   let formData = new FormData(roleForm);
//   //   formData.append('permissions', JSON.stringify(selectedRoles));

//   //   for (let [key, value] of formData.entries()) {
//   //     console.log(`${key}:`, value);
//   //   }

//   //   let userId = $('#jstree-checkbox').data('user-id');
//   //   let isUpdate = !!userId;

//   //   $.ajax({
//   //     url: `/api/vai-tro/save/${userId}`,
//   //     type: 'POST',
//   //     data: formData,
//   //     processData: false,
//   //     contentType: false,
//   //     success: function () {
//   //       let message = isUpdate ? 'Cập nhật vai trò thành công!' : 'Thêm mới vai trò thành công!';
//   //       toastr.success(message);
//   //     },
//   //     error: function (error) {
//   //       console.error('Lỗi khi lưu dữ liệu:', error);
//   //       // toastr.error('Lỗi khi lưu dữ liệu.');
//   //     }
//   //   });
//   // }
// });

'use strict';
$(function () {
  // // Xử lý xóa vai trò khi click vào nút xóa
  // $('.delete-record').on('click', function (e) {
  //   e.preventDefault(); // Ngừng hành động mặc định

  //   const roleId = $(this).data('id'); // Lấy ID vai trò từ thuộc tính data-id
  //   const deleteRoute = $(this).data('route'); // Lấy đường dẫn xóa từ thuộc tính data-route

  //   // Hiển thị xác nhận trước khi xóa
  //   if (confirm('Bạn có chắc chắn muốn xóa vai trò này không?')) {
  //     // Gửi yêu cầu xóa
  //     $.ajax({
  //       url: deleteRoute, // Đường dẫn đến action destroy trong controller
  //       type: 'DELETE',
  //       data: {
  //         _token: '{{ csrf_token() }}' // CSRF token để bảo vệ khỏi tấn công CSRF
  //       },
  //       success: function (response) {
  //         if (response.status === 1) {
  //           toastr.success('Vai trò đã được xóa thành công!');
  //           window.location.reload(); // Làm mới trang sau khi xóa thành công
  //         } else {
  //           toastr.error('Không thể xóa vai trò này.');
  //         }
  //       },
  //       error: function (xhr, status, error) {
  //         console.error('Lỗi khi xóa vai trò:', error);
  //         toastr.error('Đã xảy ra lỗi khi xóa vai trò.');
  //       }
  //     });
  //   }
  // });

  console.log('============> jsTree is running');
  let theme = $('html').hasClass('light-style') ? 'default' : 'default-dark',
    checkboxTree = $('#jstree-checkbox'),
    userId = checkboxTree.data('user-id') !== '' ? checkboxTree.data('user-id') : -1,
    selectedRoles = [],
    treeData = [];

  async function fetchData() {
    try {
      if (userId !== '') {
        await getDsVaiTroByUserId(userId);
      }
      await getDsVaiTro();
    } catch (error) {
      console.error('Lỗi khi tải dữ liệu:', error);
    }
  }

  // Danh sách tất cả quyền
  async function getDsVaiTro() {
    try {
      const response = await fetch((window.cmsUrls && window.cmsUrls.roleList) || '/cms/api/vai-tro/getDsVaiTro');
      const data = await response.json();

      if (data.code === 200) {
        console.log('[]getDsVaiTro', data.data);
        treeData = await buildTree(data.data); // ⏳
        initJsTree(); // ✅ Khi có treeData
      }
    } catch (error) {
      console.error('Lỗi khi tải dữ liệu ds Vai trò:', error);
    }
  }

  // Lấy thông tin quyền mà user có
  function getDsVaiTroByUserId(userId) {
    return fetch(`${(window.cmsUrls && window.cmsUrls.roleByRole) || '/cms/api/vai-tro/getDsVaiTroByUserId'}/${userId}`)
      .then(response => response.json())
      .then(data => {
        if (data.code === 200) {
          selectedRoles = data.data;
          //console.log('[]selectedRoles', selectedRoles);
        }
      })
      .catch(error => console.log('Error loading user roles:', error));
  }

  fetchData(); // Gọi hàm fetch dữ liệu đồng bộ

  async function buildTree(data, parentId = null) {
    const result = [];
    for (const item of data.filter(d => {
      // Nếu parentId là null hoặc -1, lấy các node root (parent_id null hoặc -1)
      if (parentId === null || parentId === -1) {
        return d.parent_id === null || d.parent_id === -1;
      }
      // Ngược lại, so sánh bình thường
      return d.parent_id === parentId;
    })) {
      const children = await buildTree(data, item.id);
      result.push({
        id: item.id_encrypt,
        id_encrypt: item.id,
        text: item.name,
        state: {
          opened: children.length > 0,
          selected: selectedRoles.some(obj => obj.id == item.id)
        },
        children: children
      });
    }
    return result;
  }

  function initJsTree() {
    if (checkboxTree.length > 0 && treeData.length > 0) {
      checkboxTree.jstree('destroy').empty();
      checkboxTree.jstree({
        core: {
          themes: { name: theme, dots: true, icons: false },
          data: treeData
        },
        plugins: ['types', 'checkbox'],
        types: {
          default: { icon: '' }
        }
      });

      checkboxTree.on('changed.jstree', function (e, data) {
        console.log('Tree changed:', data);

        selectedRoles = data.selected.map(id => {
          let node = data.instance.get_node(id);
          return node.id;
        });
      });
    }
  }

  // Chặn hành động submit nếu không chọn quyền
  document.getElementById('role-form').addEventListener('submit', function (e) {
    e.preventDefault(); // Ngừng hành động submit mặc định
    // Kiểm tra xem có quyền nào được chọn trong jstree không
    let checkedNodes = $('#jstree-checkbox').jstree('get_checked', true);

    if (checkedNodes.length === 0) {
      // alert('Vui lòng chọn ít nhất 1 quyền!');
      return; // Nếu không chọn quyền, ngừng submit
    }

    // Nếu có quyền được chọn, tiếp tục submit
    save(); // Gọi hàm lưu dữ liệu
  });

  function save() {
    const roleForm = $('#role-form')[0];
    let formData = new FormData(roleForm);
    formData.append('permissions', JSON.stringify(selectedRoles));

    $.ajax({
      url: `${(window.cmsUrls && window.cmsUrls.roleSave) || '/cms/api/vai-tro/save'}/${userId}`,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function () {
        let message =
          userId !== -1 && userId !== null && userId !== ''
            ? 'Cập nhật vai trò thành công!'
            : 'Thêm mới vai trò thành công!';
        toastr.success(message);
        // Chuyển hướng về trang danh sách vai trò sau khi thành công
        window.location.href = '/roles'; // Đảm bảo URL này là URL đúng cho danh sách vai trò
      },
      error: function (error) {
        console.error('Lỗi khi lưu dữ liệu:', error);
      }
    });
  }
});
