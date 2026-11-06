@php
$configData = Helper::appClasses();
@endphp
@extends('layouts/layoutMaster')

@section('title', 'Dashboard - Analytics')
@section('vendor-style')
{{-- @vite(['resources/assets/vendor/?.css']) --}}
@endsection

@section('page-style')
<!-- Page -->
{{-- @vite(['resources/assets/vendor/scss/?.css']) --}}
@endsection

@section('vendor-script')
{{-- @vite(['resources/assets/vendor/?.js']) --}}
@endsection

@section('page-script')
<script src="/vendor/laravel-filemanager/js/stand-alone-button.js" type="module"></script>
@endsection

@section('content')
<h1>Chọn tập tin</h1>
@php
    $fileOldValue = 'http://cms.io/storage/photos/shares/6721f8a7a91e7.PNG';
@endphp
<x-input-file-select id="file_single" type="file" name="file_single" :value=$fileOldValue :multiple="false">
    <div style="margin-left: 20px">
        <div id="floatingInputHelp" class="form-text">- Chỉ chọn một thôi bạn nhé</div>
    </div>
</x-input-file-select>

<script>
    document.getElementById('file_single').addEventListener('change', function () {
        document.getElementById('file_single_output').innerText = this.value;
    });
</script>

<pre><output id="file_single_output"></output></pre>

<h1>Chọn nhiều tập tin</h1>
@php
    $fileOldValue = '["http://cms.io/storage/photos/shares/6721f8a7a91e7.PNG","http://cms.io/storage/photos/shares/a600ae390977fb29a266.jpg"]';
@endphp
<x-input-file-select id="file_multiple" type="file" name="file_multiple" :value=$fileOldValue :multiple="true">
    <div style="margin-left: 20px">
        <div id="floatingInputHelp" class="form-text">- Bản scan đơn xin gia nhập Hội xây dưng TP Cần Thơ</div>
        <div id="floatingInputHelp2" class="form-text">- Bản sao Giấy phép thành lập/ Quyết định thành lập hoặc
            Giấy chứng nhận đăng ký kinh doanh của tổ chức</div>
        <div id="floatingInputHelp3" class="form-text">- Báo cáo hoạt động trong ba năm gần nhất</div>
    </div>
</x-input-file-select>

<script>
    document.getElementById('file_multiple').addEventListener('change', function () {
        console.log(this.value)
        document.getElementById('file_multiple_output').innerText = this.value;
    });
</script>

<pre><output id="file_multiple_output"></output></pre>
@endsection
