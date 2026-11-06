<script>

    //video
  const videoInput = document.getElementById('linkVideo');
  const videoPlayer = document.getElementById('plyr-video-player');
  const videoUploadPlayer = document.getElementById('plyr-video-player-up');
  const videoUploadInput = document.getElementById('thumbnailVideo');
  const imgDefault = document.getElementById('img-default');
  let timer;

  function videoChange(time = 1500) {
    clearTimeout(timer);
    timer = setTimeout(() => {
      const url = videoInput.value.trim();
      if(url===''||url===null){
        return;
      }
              const videoId = extractYouTubeId(url);

              if (videoId) {
                console.log('url:',url);
                videoUploadInput.value='';
                  const embedUrl = `https://www.youtube.com/embed/${videoId}`;
                  // Cập nhật src của video và hiển thị video
                  videoPlayer.src = embedUrl;
                  videoPlayer.classList.remove('hidden'); // Bỏ lớp ẩn
                  videoPlayer.classList.add('visible');   // Thêm lớp hiển thị
                  imgDefault.classList.remove('visible'); // Bỏ lớp ẩn
                  imgDefault.classList.add('hidden');   // Thêm lớp hiển thị
                  videoUploadPlayer.classList.remove('visible'); // Bỏ lớp ẩn
                  videoUploadPlayer.classList.add('hidden');   // Thêm lớp hiển thị
              }else if(!videoId&&videoUploadInput!==''){
                  videoPlayer.classList.remove('visible'); // Bỏ lớp ẩn
                  videoPlayer.classList.add('hidden');   // Thêm lớp hiển thị
                  videoUploadPlayer.classList.remove('visible'); // Bỏ lớp ẩn
                  videoUploadPlayer.classList.add('hidden');   // Thêm lớp hiển thị
                  imgDefault.classList.remove('hidden'); // Bỏ lớp ẩn
                  imgDefault.classList.add('visible');   // Thêm lớp hiển thị
              }
    }, time);
  };
  window.addEventListener('DOMContentLoaded', videoChange(500));

  function extractYouTubeId(url) {
          const regex = /(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/;
          const match = url.match(regex);
          return match ? match[1] : null;
      };

  function videoUploadChange(time = 0){
    console.log('videoUploadChange')
    timer = setTimeout(() => {
    let newSrc = videoUploadInput.value.trim();
             if (newSrc) {
              videoInput.value='';
              console.log('newSrc=',newSrc)
                // Đổi src của video
                videoUploadPlayer.querySelector('source').src = newSrc;
                videoUploadPlayer.load(); // Tải lại video với src mới
                videoUploadPlayer.classList.remove('hidden');
                videoUploadPlayer.classList.add('visible');
                videoPlayer.classList.remove('visible');
                videoPlayer.classList.add('hidden');
                imgDefault.classList.remove('visible');
                imgDefault.classList.add('hidden');
              }
              else if(!newSrc && videoInput.value ==='')
              {
                console.log('dô=',newSrc)
                videoPlayer.classList.remove('visible');
                videoPlayer.classList.add('hidden');
                videoUploadPlayer.classList.remove('visible');
                videoUploadPlayer.classList.add('hidden');
                imgDefault.classList.remove('hidden');
                imgDefault.classList.add('visible');
              }
            },time);
  };
  window.addEventListener('DOMContentLoaded', videoUploadChange(500));
</script>
