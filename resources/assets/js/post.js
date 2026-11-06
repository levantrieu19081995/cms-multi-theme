
// Hàm cho phép người dùng chỉnh sửa slug
// function enableSlugEdit() {
//   const slugInput = document.getElementById('slug');
//   const editButton = document.getElementById('edit-slug-btn');

//   slugInput.readOnly = false; // Kích hoạt input để người dùng có thể chỉnh sửa
//   slugInput.focus(); // Tự động đưa con trỏ vào trường slug để chỉnh sửa
//   // editButton.innerText = 'Khóa lại'; // Thay đổi text của nút thành 'Khóa lại'
// }

// Hàm tự động vô hiệu hóa input slug khi người dùng rời khỏi (blur)
// function disableSlugEdit() {
//   const slugInput = document.getElementById('slug');
//   const editButton = document.getElementById('edit-slug-btn');
//   // Nếu slug bị xóa rỗng, tự động render lại theo title
//   if (slugInput.value.trim() === '') {
//     generateSlug(); // Tự động tạo lại slug từ title
//   }
//   slugInput.readOnly = true; // Vô hiệu hóa lại sau khi chỉnh sửa xong

//   //editButton.innerText = 'Chỉnh sửa'; // Đổi text lại thành 'Chỉnh sửa'
// }
// Hàm loại bỏ dấu khi người dùng gõ trong slug input
document.getElementById('slug').addEventListener('input', function () {
  const slugInput = document.getElementById('slug');
  slugInput.value = createSlug(slugInput.value); // Tự động loại bỏ dấu và ký tự không hợp lệ
});
document.getElementById('slug').addEventListener('blur', function () {
  const slugInput = document.getElementById('slug');
  slugInput.value = lamDep(slugInput.value); // Tự động loại bỏ dấu và ký tự không hợp lệ
});

document.addEventListener('DOMContentLoaded', function () {
  // Lấy tất cả các nút submit
  const submitButtons = document.querySelectorAll('.submit-btn');
  submitButtons.forEach(button => {
    button.addEventListener('click', function (event) {
      event.preventDefault(); // Ngăn chặn submit mặc định
      const form = this.closest('form'); // Lấy form chứa nút được click
      form.submit(); // Submit form đó
    });
  });
});

document.addEventListener('DOMContentLoaded', function () {
  const allCategoriesCheckboxes = document.querySelectorAll('#navs-top-home input[type="checkbox"]');
  const topCategoriesCheckboxes = document.querySelectorAll('#navs-top-profile input[type="checkbox"]');

  // Hàm để đồng bộ checkbox
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

  // Gọi hàm loadCategories ngay khi DOM đã sẵn sàng hoặc tại thời điểm thích hợp khác
  loadCategories();
// Hàm chuyển đổi tiêu đề thành slug
function createSlug(input) {
 // Chuyển đổi thành chữ thường
 let slug = input.toLowerCase();

 // Thay thế ký tự 'đ' và 'Đ' trước khi normalize
 slug = slug.replace(/đ/g, 'd').replace(/Đ/g, 'd');

 // Thay thế các ký tự có dấu thành không dấu
 slug = slug.normalize('NFD').replace(/[\u0300-\u036f]/g, '');

 // Thay thế các khoảng trắng hoặc ký tự không phải chữ cái và số bằng dấu '-'
 slug = slug.replace(/\s+/g, '-').replace(/[^a-z0-9\-]/g, '');

 // Xóa dấu gạch ngang dư thừa ở đầu hoặc cuối
 slug = slug.replace(/^-+|-+$/g, '');

 return slug;
}
function lamDep(input) {
  // Chuyển đổi thành chữ thường
  let slug = input.toLowerCase();
  slug = slug.replace(/^-+|-+$/g, '');
  return slug;
}

// Hàm được gọi mỗi khi người dùng gõ trên trường title
function generateSlug() {
  const title = document.getElementById('title').value;
  const slug = createSlug(title);
  document.getElementById('slug').value = slug;
}



  window.syncCheckboxes = syncCheckboxes;
  window.loadCategories = loadCategories;
  window.generateSlug = generateSlug;
  window.lamDep = lamDep;
  window.createSlug = createSlug;
});
// Lắng nghe sự kiện change trên select
document.getElementById('publish_type_select').addEventListener('change', function () {
  const selectedValue = this.value;
  const div = document.querySelector('.ngay-xuat-ban');

  // Kiểm tra nếu value là 'schedule_publication'
  if (selectedValue === 'schedule_publication') {
    div.style.display = 'flex'; // Hiển thị div
  } else {
    div.style.display = 'none'; // Ẩn div
  }
});

function handleSelectChange() {
  var selectBox = document.getElementById('publish_type_select');
  var selectedValue = selectBox.options[selectBox.selectedIndex].value;
  const div = document.querySelector('.ngay-xuat-ban');
  if (selectedValue === 'schedule_publication') {
    div.style.display = 'flex'; // Hiển thị div
  } else {
    div.style.display = 'none'; // Ẩn div
  }
}

window.onload = function () {
  handleSelectChange(); // Gọi hàm để kiểm tra và hiển thị div tương ứng
};

let ti;


document.addEventListener('DOMContentLoaded', function () {
  let imageObjects = [];
  function removeImage(index) {
    imageObjects.splice(index, 1);
    updateViewImage();
} 
let timer;
  let imagePaths = [];
  
  function updateDescription(input) {
    clearTimeout(timer);
    timer = setTimeout(() => {
    const index = input.getAttribute('data-index');
    imageObjects[index].description = input.value;
   // updateViewImage();
    },0);
  }
  function updateViewImage(){
    if(imageObjects){
      document.getElementById('image-preview-container').innerHTML = ''
      imageObjects.forEach((i, index) => {
            const imageDiv = `
                  <div class="col-md-4 mb-3 position-relative grid-child">
                      <a href="${i.url}" data-lightbox="image-library" data-title="${i.description}">
                      <img src="${i.url}" alt="Image Preview" class="img-fluid">
                    </a>
                    <input type="text" class="form-control mt-2" placeholder="Nhập mô tả" data-index="${index}" value="${i.description}" 
                      onchange="updateDescription(this)">
                        <button type="button" class="btn btn-danger waves-effect waves-light btn-sm position-absolute remove-image-btn"  onclick="removeImage(${index})">X</button>
                  </div>
              `;
          document.getElementById('image-preview-container').insertAdjacentHTML('beforeend', imageDiv);
      })
      document.getElementById('thumbnailImage').value = JSON.stringify(imageObjects);
      console.log('imageObjects',imageObjects)
    }
  }
  function lfm(id, options) {
    console.log('id',id)
    let button = document.getElementById(id);
     button.addEventListener('click', function () {
      var route_prefix = (options && options.prefix) ? options.prefix : '/laravel-filemanager';
      var target_input = document.getElementById(button.getAttribute('data-input'));
      var target_preview = document.getElementById(button.getAttribute('data-preview'));
      var type = button.getAttribute('data-type')??'image';
      let popupWinWidth = 900;
      let popupWinHeight = 600;
      let left = (screen.width - popupWinWidth) / 2;
      let top = (screen.height - popupWinHeight) / 4;
      window.open(route_prefix + '?type=' + type || 'file', 'FileManager', 'width='+popupWinWidth+',height='+ popupWinHeight+',left='+left+',top='+top);
      window.SetUrl = function (items) {
        var file_path = items.map(function (item) {
          return item.url;
        }).join(',');
        console.log('file_path= ',file_path)
        console.log('target_input= ',target_input)
        // set the value of the desired input to image url
        if(button.getAttribute('data-input') ==='thumbnailImage'){
          target_input.value = target_input.value!==''? target_input.value+','+file_path:file_path;
        }else{
          target_input.value = file_path;
        }
       
        target_input.dispatchEvent(new Event('change'));

        if(button.getAttribute('data-input')!=='thumbnailVideo'){
          // clear previous preview
          target_preview.innerHTML = '';
          // set or change the preview image src
          items.forEach(function (item) {
            let img = document.createElement('img')
            // img.setAttribute('style', 'height: 5rem')
            img.setAttribute('src', item.url)
            target_preview.appendChild(img);
            if(button.getAttribute('data-input') ==='thumbnailImage'){
              if (!imageObjects.some(image => item.url === image.url)) {
                  imageObjects.push({index:imageObjects.length, url:item.url, description: '' });
              }
            }
          });
          // trigger change event
          target_preview.dispatchEvent(new Event('change'));
          if(button.getAttribute('data-input') ==='thumbnailImage'){
          updateViewImage();
          }
        }
      };
     });
  };
 
  lfm('lfm',  { prefix: '/laravel-filemanager' });
  // function thumbnail_change11() {
  //   const thump = document.getElementById('thumbnailImage');
    
  //   let inputPath = thump.value.split(',');
  //   console.log('inputPath',inputPath)
  //   if (inputPath) {
  //       inputPath = inputPath.map((url) => ({ url, description: '' }));
  //       imagePaths = imagePaths.concat(inputPath);
  //   }
  //   // Xử lý trùng lặp
  //   imagePaths = imagePaths.filter((value, index, array) => array.indexOf(value) === index);
  //   imagePaths = imagePaths.filter(item => item !== null && item !== undefined && item !== '');
  //   // Làm sạch vùng hiển thị trước khi hiển thị lại
  //   // $('#image-preview-container').empty();
  //   document.getElementById("image-preview-container").innerHTML = '';
  //   // Hiển thị hình ảnh
  //   imagePaths.forEach((i, index) => {
  //     const imageDiv = `
  //                  <div class="col-md-4 mb-3 position-relative grid-child">
  //                     <a href="${i.url}" data-lightbox="image-library" data-title="Ảnh ${index + 1}">
  //                      <img src="${i.url}" alt="Image Preview" class="img-fluid">
  //                    </a>
  //                    <input type="text" class="form-control mt-2" placeholder="Nhập mô tả" value="${i.description??''}" 
  //                      oninput="updateDescription(${index}, this.value)">
  //                       <button type="button" class="btn btn-danger waves-effect waves-light btn-sm position-absolute remove-image-btn"  onclick="removeImage(this, '${i}')">X</button>
  //                  </div>
  //              `;
  //     // $('#image-preview-container').append(imageDiv);
  //     document.getElementById('image-preview-container').insertAdjacentHTML('beforeend', imageDiv);
  //   });
  // }

  
  
  

  imagePaths = $('#thumbnailImage')
    .val()
    .split(',')
    .filter(path => path !== '');
    // thumbnail_change()
  // Khởi tạo chức năng kéo thả với SortableJS
  const container = document.getElementById('image-preview-container');
  Sortable.create(container, {
    animation: 150,
    onEnd: function (event) {
      const [movedItem] = imageObjects.splice(event.oldIndex, 1);
      imageObjects.splice(event.newIndex, 0, movedItem);
        // Cập nhật lại index cho từng mục trong imageObjects
        imageObjects.forEach((item, index) => {
          item.index = index; // Gán giá trị index mới
        });
      updateViewImage();
    }
  });

// //video
// let videoInput = document.getElementById('linkVideo');
// let videoPlayer = document.getElementById('plyr-video-player');
// let timer;
// function linkVideoChange() {

//   clearTimeout(timer);

//   timer = setTimeout(() => {
//       const newSrc = videoInput.value.trim();
//       if (newSrc) {
//           // Đổi src của video
//           videoPlayer.querySelector('source').src = newSrc;
//           videoPlayer.load(); // Tải lại video với src mới
//           videoPlayer.style.display = 'block'; // Hiển thị video
//           // Lấy poster từ video mới nếu có thể, hoặc set lại poster mặc định
//           videoPlayer.poster = `${newSrc.split('.mp4')[0]}-HD.jpg` || "https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-HD.jpg";
//       }
//   }, 3000);
// };

 
  // window.thumbnail_change = thumbnail_change;
  // window.removeImage = removeImage;
  // window.updateDescription = updateDescription;
  // window.linkVideoChange = linkVideoChange;

  async function fetchMediaList() {
    const postId = document.getElementById('post_id').value;
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


fetchMediaList();
window.imageObjects = imageObjects;
window.removeImage = removeImage;
window.updateViewImage = updateViewImage;
window.updateDescription = updateDescription;
window.fetchMediaList = fetchMediaList;
window.lfm = lfm;

});


// Xóa hình ảnh đã chọn
// function removeImage(button, imageUrl) {
//   // Loại bỏ hình ảnh khỏi mảng imagePaths
//   imagePaths = imagePaths.filter(path => path !== imageUrl);

//   // Cập nhật lại giá trị của input thumbnail (đường dẫn của các ảnh đã chọn)
//   document.getElementById('thumbnailImage').value = imagePaths.join(',');

//   // Xóa hình ảnh khỏi giao diện
//   const imageDiv = button.parentElement;
//   imageDiv.remove();
// }