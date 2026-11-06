@php
$configData = Helper::appClasses();
@endphp
@extends('layouts/layoutMaster')

@section('title', 'Dashboard - Analytics')
@section('vendor-style')
@vite(Theme::asset([
  'assets/vendor/libs/quill/typography.scss',
  'assets/vendor/libs/quill/katex.scss',
  'assets/vendor/libs/quill/editor.scss',
  'assets/vendor/libs/select2/select2.scss',
  'assets/vendor/libs/tagify/tagify.scss',
  'assets/vendor/libs/bootstrap-select/bootstrap-select.scss',
  'assets/vendor/libs/typeahead-js/typeahead.scss',
  'assets/vendor/libs/dropzone/dropzone.scss',
  'assets/vendor/libs/flatpickr/flatpickr.scss',
  'assets/vendor/libs/ckeditor5/ckeditor5.css',
  'assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss',
  'assets/vendor/libs/notiflix/notiflix-3.2.7.min.css'

]))
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css">

@endsection

@section('page-style')
@vite([
  'resources/assets/css/ckeditor.css',
  'resources/assets/css/bai-viet-be.css'
])
@endsection

@section('vendor-script')
@vite(Theme::asset([
  'assets/vendor/libs/quill/katex.js',
  'assets/vendor/libs/select2/select2.js',
  'assets/vendor/libs/tagify/tagify.js',
  'assets/vendor/libs/bootstrap-select/bootstrap-select.js',
  'assets/vendor/libs/typeahead-js/typeahead.js',
  'assets/vendor/libs/dropzone/dropzone.js',
  'assets/vendor/libs/jquery-repeater/jquery-repeater.js',
  'assets/vendor/libs/flatpickr/flatpickr.js',
  'assets/vendor/libs/form-basic-inputs.js',
  'assets/vendor/libs/forms-typeahead.js',
  'assets/vendor/libs/forms-selects.js',
  'assets/vendor/libs/forms-tagify.js',
  'assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js',
  'assets/vendor/libs/notiflix/notiflix-3.2.7.min.js'
]))

<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
@endsection

@section('page-script')
<script src="/vendor/laravel-filemanager/js/stand-alone-button.js"></script>
<script src="/vendor/unisharp/laravel-ckeditor/adapters/jquery.js"></script>
@vite([
  'resources/assets/js/ckeditor5-cus.js',
  'resources/assets/js/form-input-group.js',
  'resources/assets/js/extended-ui-perfect-scrollbar.js'
])
@endsection

@section('content')
<div class="row">
  <div class="col-lg-12 mb-6">
    <textarea id="ckeditor5" name="content" class="form-control">{!! old('content', 'test editor content') !!}</textarea>
  </div>
</div>
@endsection

