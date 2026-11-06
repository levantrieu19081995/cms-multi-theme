'use strict';
(function () {
  // ===== SLUG HANDLING =====

  // Hàm chuyển đổi tiêu đề thành slug
  function createSlug(input) {
    const value = input.trim();
    if (value.startsWith('http://') || value.startsWith('https://')) {
      return value; // Trả nguyên URL nếu là link đầy đủ
    }

    let slug = value.toLowerCase();
    slug = slug.replace(/đ/g, 'd').replace(/Đ/g, 'd');
    slug = slug.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    slug = slug.replace(/\s+/g, '-').replace(/[^a-z0-9\-]/g, '');
    slug = slug.replace(/^-+|-+$/g, '');

    const baseUrl = window.location.origin + '/';
    return slug;
  }

  // Làm đẹp slug (loại bỏ dấu gạch ngang đầu/cuối)
  function lamDep(input) {
    const value = input.trim();
    if (value.startsWith('http://') || value.startsWith('https://')) return value;
    return value.toLowerCase().replace(/^-+|-+$/g, '');
  }

  // Hàm được gọi mỗi khi người dùng gõ trên trường title (Tạo slug từ tiêu đề)
  function generateSlug() {
    const title = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    if (!title || !slugInput) return;

    const isSlugLocked = slugInput.hasAttribute('data-slug-locked') || slugInput.readOnly;
    if (isSlugLocked) return;

    const slug = createSlug(title.value);

  }

  // ===== CATEGORY CHECKBOX SYNC =====

  // Hàm để đồng bộ checkbox (khi người dùng chọn hoặc bỏ chọn checkbox trong tab Tất cả chuyên mục, nó sẽ tự động cập nhật trạng thái của checkbox tương ứng trong tab Sử dụng nhiều và ngược lại)
  function syncCheckboxes(sourceCheckboxes, targetCheckboxes) {
    sourceCheckboxes.forEach(sourceCheckbox => {
      sourceCheckbox.addEventListener('change', function () {
        const targetCheckbox = Array.from(targetCheckboxes).find(cb => cb.value === sourceCheckbox.value);
        if (targetCheckbox) {
          targetCheckbox.checked = sourceCheckbox.checked;
        }
      });
    });
  }

  // Hàm để load danh sách chuyên mục và đồng bộ checkbox giữa hai tab
  function loadCategories() {
    // Giả sử load xong, sau đó bạn cập nhật danh sách các checkbox
    // Đây là ví dụ, thực tế bạn sẽ xử lý AJAX hoặc các thao tác tải dữ liệu khác.

    // Sau khi load xong và render checkbox vào DOM
    const allCategoriesCheckboxes = document.querySelectorAll('#navs-top-home input[type="checkbox"]');
    const topCategoriesCheckboxes = document.querySelectorAll('#navs-top-profile input[type="checkbox"]');

    // Đồng bộ từ tab Tất cả chuyên mục đến Sử dụng nhiều
    syncCheckboxes(allCategoriesCheckboxes, topCategoriesCheckboxes);

    // Đồng bộ từ tab Sử dụng nhiều đến Tất cả chuyên mục
    syncCheckboxes(topCategoriesCheckboxes, allCategoriesCheckboxes);
  }

  // ===== IMAGE MANAGEMENT =====
  let imageObjects = [];
  let timer;

  // Hàm để xóa hình ảnh đã chọn
  function removeImage(index) {
    imageObjects.splice(index, 1);
    updateViewImage();
  }
  // Hàm cập nhật mô tả hình ảnh (Khi người dùng thay đổi mô tả trong ô input, hàm này sẽ được gọi để cập nhật mô tả tương ứng trong mảng imageObjects)
  function updateDescription(input) {
    clearTimeout(timer);
    timer = setTimeout(() => {
      const index = input.getAttribute('data-index');
      imageObjects[index].description = input.value;
      // updateViewImage();
    }, 0);
  }

  // Hàm cập nhật giao diện hiển thị hình ảnh (Khi người dùng chọn hình ảnh, hàm này sẽ được gọi để cập nhật giao diện hiển thị hình ảnh đã chọn)
  function updateViewImage() {
    if (imageObjects) {
      const imagePreviewContainer = document.getElementById('image-preview-container');
      if (!imagePreviewContainer) return;
      imagePreviewContainer.innerHTML = '';
      imageObjects.forEach((i, index) => {
        const imageDiv = `
                  <div class="col-md-4 mb-3 position-relative grid-child">
                      <a href="${i.url}" data-lightbox="image-library" data-title="${i.description}">
                      <img src="${i.url}" alt="Image Preview" class="img-fluid">
                    </a>
                    <input type="text" class="form-control mt-2" placeholder="${window.cmsAdminTranslations.enter_description}" data-index="${index}" value="${i.description}" 
                      onchange="updateDescription(this)">
                        <button type="button" class="btn btn-danger waves-effect waves-light btn-sm position-absolute remove-image-btn"  onclick="removeImage(${index})">X</button>
                  </div>
              `;
        document.getElementById('image-preview-container').insertAdjacentHTML('beforeend', imageDiv);
      })

      document.getElementById('thumbnailImage').value = JSON.stringify(imageObjects);
      console.log('imageObjects', imageObjects)
    }
  }

  // Hàm để mở File Manager (Khi người dùng click vào nút chọn ảnh, hàm này sẽ mở File Manager để người dùng có thể chọn ảnh)
  function lfm(id, options) {
    // console.log('[post.js] lfm', id)
    let button = document.getElementById(id);
    if (!button) return;
    button.addEventListener('click', function () {
      var route_prefix = (options && options.prefix) ? options.prefix : '/laravel-filemanager';
      var target_input = document.getElementById(button.getAttribute('data-input'));
      var target_preview = document.getElementById(button.getAttribute('data-preview'));
      var type = button.getAttribute('data-type') ?? 'image';
      let popupWinWidth = 900;
      let popupWinHeight = 600;
      let left = (screen.width - popupWinWidth) / 2;
      let top = (screen.height - popupWinHeight) / 4;
      window.open(route_prefix + '?type=' + type || 'file', 'FileManager', 'width=' + popupWinWidth + ',height=' + popupWinHeight + ',left=' + left + ',top=' + top);
      window.SetUrl = function (items) {
        var file_path = items.map(function (item) {
          return item.url;
        }).join(',');
        console.log('file_path= ', file_path)
        console.log('target_input= ', target_input)
        // set the value of the desired input to image url
        if (button.getAttribute('data-input') === 'thumbnailImage') {
          target_input.value = target_input.value !== '' ? target_input.value + ',' + file_path : file_path;
        } else {
          target_input.value = file_path;
        }

        target_input.dispatchEvent(new Event('change'));

        if (button.getAttribute('data-input') !== 'thumbnailVideo') {
          // clear previous preview
          target_preview.innerHTML = '';
          // set or change the preview image src
          items.forEach(function (item) {
            let img = document.createElement('img')
            // img.setAttribute('style', 'height: 5rem')
            img.setAttribute('src', item.url)
            target_preview.appendChild(img);
            if (button.getAttribute('data-input') === 'thumbnailImage') {
              if (!imageObjects.some(image => item.url === image.url)) {
                imageObjects.push({ index: imageObjects.length, url: item.url, description: '' });
              }
            }
          });
          // trigger change event
          target_preview.dispatchEvent(new Event('change'));
          if (button.getAttribute('data-input') === 'thumbnailImage') {
            updateViewImage();
          }
        }
      };
    });
  };

  // ===== MEDIA FETCHING =====

  // Hàm để lấy danh sách media từ server (Khi người dùng click vào nút lấy danh sách media, hàm này sẽ gọi API để lấy danh sách media và cập nhật giao diện hiển thị hình ảnh)
  async function fetchMediaList() {
    const postId = document.getElementById('post_id')?.value;
    if (!document.getElementById('image-preview-container')) return;
    if (!postId) {
      return;
    }
    try {
      const response = await fetch(`/media-list/${postId}`);
      const mediaList = await response.json();

      if (response.ok) {
        imageObjects = mediaList.map(media => ({
          url: media.url,
          description: media.description || '',
          index: media.index || 0
        }));
        updateViewImage();
      } else {
        console.error(mediaList.message);
      }
    } catch (error) {
      console.error('Error fetching media list:', error);
    }
  }

  // ===== PUBLISH TYPE SELECT HANDLING =====

  // Hiển thị/ẩn trường ngày xuất bản theo loại xuất bản
  function handleSelectChange() {
    console.log('[post.js] handleSelectChange');
    var selectBox = document.getElementById('publish_type_select');
    const div = document.querySelector('.ngay-xuat-ban');
    if (!selectBox || !div) return;
    var selectedValue = selectBox.options[selectBox.selectedIndex]?.value;
    if (selectedValue === 'schedule_publication') {
      div.style.display = 'flex'; // Hiển thị div
    } else {
      div.style.display = 'none'; // Ẩn div
    }
  }

  // ===== INIT ON DOM READY =====

  document.addEventListener('DOMContentLoaded', function () {
    const slugInput = document.getElementById('slug');
    const titleInput = document.getElementById('title');


    if (slugInput) {
      slugInput.addEventListener('input', function () {
        if (this.hasAttribute('data-slug-locked') || this.readOnly) return;
        this.value = createSlug(this.value);
      });

      slugInput.addEventListener('blur', function () {
        if (this.hasAttribute('data-slug-locked') || this.readOnly) return;
        this.value = lamDep(this.value);
      });
    }

    if (titleInput) {
      titleInput.addEventListener('input', function () {
        if (!slugInput || slugInput.hasAttribute('data-slug-locked') || slugInput.readOnly) return;
        slugInput.value = createSlug(titleInput.value);
      });
    }

    // Custom submit buttons
    const submitButtons = document.querySelectorAll('.submit-btn');
    submitButtons.forEach(button => {
      button.addEventListener('click', function (event) {
        console.log('submit button clicked', this);
        return;
        event.preventDefault(); // Ngăn chặn submit mặc định
        const form = this.closest('form'); // Lấy form chứa nút được click
        form.submit(); // Submit form đó
      });
    });

    // Category checkboxes
    loadCategories();

    // File manager
    lfm('lfm', { prefix: '/laravel-filemanager' });

    // SortableJS for image preview
    const container = document.getElementById('image-preview-container');
    if (container) {
      Sortable.create(container, {
        animation: 150,
        onEnd: function (event) {
          const [movedItem] = imageObjects.splice(event.oldIndex, 1);
          imageObjects.splice(event.newIndex, 0, movedItem);
          imageObjects.forEach((item, index) => { item.index = index; });
          updateViewImage();
        }
      });
    }

    // Fetch media list for post
    fetchMediaList();

    // Publish type select
    document.getElementById('publish_type_select')?.addEventListener('change', handleSelectChange);
    handleSelectChange();
  });

  // ===== GLOBAL EXPOSURE =====
  window.createSlug = createSlug;
  window.lamDep = lamDep;
  window.generateSlug = generateSlug;
  window.syncCheckboxes = syncCheckboxes;
  window.loadCategories = loadCategories;
  window.removeImage = removeImage;
  window.updateDescription = updateDescription;
  window.updateViewImage = updateViewImage;
  window.fetchMediaList = fetchMediaList;
  window.handleSelectChange = handleSelectChange;
  window.lfm = lfm;

})();


