@php
    $configData = Helper::appClasses();
@endphp
@extends('layouts/layoutMaster')

@section('title', 'Thêm mới chuyên mục')
@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.scss']))
@endsection

@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.js', 'assets/vendor/libs/bootstrap-select/bootstrap-select.js', 'assets/js/forms-selects.js']))
@endsection

@section('page-script')
    @vite(Theme::asset([]))

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const nameInput = document.getElementById('name');
            const submitBtn = document.querySelector('button[type="submit"]');
            const errorDiv = document.createElement('div');
            errorDiv.classList.add('text-danger', 'mt-1');
            errorDiv.style.display = 'none';

            // chèn vào sau trường nhập
            nameInput.parentNode.appendChild(errorDiv);

            nameInput.addEventListener('input', function () {
                const maxLength = 255;
                const currentLength = nameInput.value.length;

                if (currentLength > maxLength) {
                    errorDiv.innerText = `Không được vượt quá ${maxLength} ký tự. Đã nhập ${currentLength} ký tự.`;
                    errorDiv.style.display = 'block';
                    submitBtn.disabled = true;
                } else {
                    errorDiv.innerText = '';
                    errorDiv.style.display = 'none';
                    submitBtn.disabled = false;
                }
            });

            // kiểm tra nếu ban đầu đã quá dài
            nameInput.dispatchEvent(new Event('input'));
        });
    </script>
@endsection


@php
    use App\Enums\CategoryTypeEnum;
@endphp

@section('content')
    <div class="row">
        <!-- Form controls -->
        <div class="col-md-6">
            <form action="{{ route('chuyen-muc.store') }}" method="post">
                @csrf

                <div class="card mb-6">
                    <div class="card-header bg-primary d-flex flex-column justify-content-center">
                        <h4 class="text-white text-uppercase mb-0">Thêm mới chuyên mục</h4>
                    </div>
                    <div class="card-body">
                        @include('pages.category.partials.name-tabs', ['term' => null])
                        <div class="form-floating form-floating-outline  mb-6">
                            <select id="parent_id" name="parent_id" class="select2 form-select form-select-lg"
                                data-allow-clear="true">
                                <option value>---</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->term_taxonomy_id }}">{{ $category->term->name }}</option>
                                @endforeach
                            </select>
                            <label for="parent_id">Chuyên mục cha</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-6">
                            <select id="catarogy_id" name="catarogy_id" class="select2 form-select form-select-lg"
                                data-allow-clear="true">
                                <option value>---</option>
                                @foreach (CategoryTypeEnum::options() as $option)
                                    <option value="{{ $option->value }}"
                                        {{ old('catarogy_id') === $option->value ? 'selected="selected"' : '' }}>
                                        {{ $option->label() }}
                                    </option>
                                @endforeach
                            </select>
                            <label for="catarogy_id">Loại chuyên mục</label>
                        </div>

                        <div class="form-floating form-floating-outline mb-6">
                            <textarea name="description" class="form-control h-px-100" id="description">{{ old('description') }}</textarea>
                            <label for="description">Mô tả</label>
                        </div>
                        <hr class="m-0">
                        <div class="mt-6 d-flex justify-content-end gap-2">
                            <a href="{{ route('chuyen-muc.index') }}" type="button"
                                class="btn btn-outline-secondary waves-effect text-none">Hủy bỏ</a>
                            <button type="submit" class="btn btn-primary waves-effect waves-light" name="submit"
                                value="cc">Lưu</button>
                        </div>
                    </div>
            </form>
        </div>
    </div>
@endsection
