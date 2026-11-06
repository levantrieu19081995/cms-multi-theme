import axios from "axios";

class UploadAdapter {
  constructor(loader) {
      this.loader = loader;
      this.url = '/laravel-filemanager/upload'; // Đường dẫn tới Laravel File Manager upload API
  }

  upload() {
      return this.loader.file
          .then(file => new Promise((resolve, reject) => {
              const data = new FormData();
              data.append('upload', file);

              axios.post(this.url, data)
                  .then(response => {
                      resolve({
                          default: response.data.url // URL của file đã upload
                      });
                  })
                  .catch(error => {
                      reject(error);
                  });
          }));
  }

  abort() {
      // Thêm logic hủy bỏ upload nếu cần thiết
  }
}

export default UploadAdapter;
