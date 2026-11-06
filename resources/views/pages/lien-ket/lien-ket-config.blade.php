@php
    $configData = Helper::appClasses();
@endphp



@extends('layouts/layoutMaster')

@section('title',  __('Liên kết website') )
@section('vendor-style')
@vite(Theme::asset([
  'assets/vendor/libs/dropzone/dropzone.scss'
]))
@endsection
@section('page-style')
@vite(['resources/assets/css/menu-config.css'])
<Style>
  .img-avt img{
  /* width: 100%;
    max-height: 300px;
    object-fit: cover; */
}
.aspect-ratio-16-9 {
  position: relative;
  width: 100%; /* Adjust the width as needed */
  padding-top: 162px; /* 9:16 ratio = 16 / 9 * 100 */
  /* overflow: hidden; */
}

.aspect-ratio-16-9 img {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  /* height: 100%;
  object-fit: cover; */
}
</Style>
@endsection

@section('vendor-script')
{{-- https://dbushell.github.io/Nestable/ --}}
{{-- https://github.com/dbushell/Nestable --}}
@vite(Theme::asset([
'assets/vendor/libs/dropzone/dropzone.js'
]))

<script type="module" src="/plugins/jquery.nestable/jquery.nestable.js"></script>
@endsection

@section('page-script')
@vite(['resources/assets/js/forms-file-upload.js'])
<script>
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
        target_input.value = file_path;
        target_input.dispatchEvent(new Event('change'));
          target_preview.innerHTML = '';
          // set or change the preview image src
          items.forEach(function (item) {
            let img = document.createElement('img')
            // img.setAttribute('style', 'height: 5rem')
            img.setAttribute('src', item.url)
            target_preview.appendChild(img);
          });
          // trigger change event
          target_preview.dispatchEvent(new Event('change'));
     
      };
     });
  };
 
  lfm('lfm',  { prefix: '/laravel-filemanager' });
</script>
@endsection

@section('content')
   
      <form action="{{ $link->id ? \Modules\CmsBackend\Support\ModuleUrl::route('lien-ket.update', $link) : \Modules\CmsBackend\Support\ModuleUrl::route('lien-ket.store') }}" method="post">
        <input type="hidden" name="id" value="{{ $link->id }}">
        <div class="row" id="app">
        <!-- Form controls -->
        <div class="col-md-4">
            <div class="card mb-6">
           
                @csrf
                @if ($link->id)
                  @method('put')
                @endif

                <h5 class="card-header">{{ $link->id ? 'Cập nhật' : 'Thêm mới' }} {{ __('liên kết website') }}</h5>
                <div class="card-body">
                  <div class="form-floating form-floating-outline mb-6">
                    <input type="text" class="form-control" id="name" name="title" placeholder="Sở Y tế" autocomplete="off" value="{{ old('title') ?? $link->title }}">
                    <label for="title">{{ __('Tên website liên kết') }}</label>
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                  </div>
                  <div class="form-floating form-floating-outline mb-6">
                    <input type="link" class="form-control" id="redirect_url" name="redirect_url" placeholder="https://" autocomplete="off" value="{{ old('redirect_url') ?? $link->redirect_url }}">
                    <label for="redirect_url">{{ __('Đường dẫn đích') }}</label>
                    <x-input-error :messages="$errors->get('redirect_url')" class="mt-2" />
                  </div>
               
                </div>
                <hr class="m-0">
                <div class="card-body">
                  <button type="submit" class="btn btn-primary waves-effect waves-light" name="submit">Lưu</button>
                  <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('lien-ket.index') }}" type="button" class="btn btn-outline-secondary waves-effect">Hủy bỏ</a>
                </div>
             
            </div>
        </div>
        <div class="col-md-4">
          <div class="card mb-6">
            <div class="card-header">  
              <h5>{{ __('Logo website') }}
              </h5>
               <button type="button" id="lfm" data-input="thumbnail" data-preview="holder"
              style="border: thin dashed #cfd0d6;height: 35px; width: 100%;">
              <i class="fa fa-picture-o"></i> Chọn ảnh
          </button>
            </div>
        
              <input id="thumbnail" name="anhQuangCao" class="form-control" type="hidden" name="filepath"
              value="{{ old('anhQuangCao') ??$link->getFirstMediaUrl('anhQuangCaos') }}">
              <div id="holder" style="" class="img-avt aspect-ratio-16-9">
                @if( old('anhQuangCao') || $link->getFirstMediaUrl('anhQuangCaos'))
                <img src="{{ old('anhQuangCao') ??$link->getFirstMediaUrl('anhQuangCaos') }}" alt="Xem trước quảng cáo">
                @endif
              </div>
               <x-input-error :messages="$errors->get('avatar')" class="mt-2" />
              
              
          </div>
        </div>
        <!--/ URL menu item -->
      </div>
      </form>
   
@endsection
