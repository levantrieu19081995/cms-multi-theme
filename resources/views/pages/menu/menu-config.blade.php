@php
    $configData = Helper::appClasses();
@endphp

@php
    $menuItemTypes = [
        \App\Enums\PostType::POST,
        \App\Enums\PostType::PAGE,
        \App\Enums\PostType::IMAGE,
        \App\Enums\PostType::VIDEO,
    ];
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Cấu hình menu')
@section('page-style')
    @vite(['resources/assets/css/menu-config.css'])
@endsection

@section('vendor-script')
    {{-- https://dbushell.github.io/Nestable/ --}}
    {{-- https://github.com/dbushell/Nestable --}}
    <script type="module" src="/plugins/jquery.nestable/jquery.nestable.js"></script>
@endsection

@section('page-script')
    @vite(['resources/assets/js/menu-config.js'])
@endsection

@section('content')
    {{ $menu->id ? Breadcrumbs::render('roles.edit', '') : Breadcrumbs::render('roles.create', '') }}
    <div class="row" id="app">
        <!-- Form controls -->
        <div class="col-md-4">
            <form action="{{ $menu->id ? route('menus.update', $menu) : route('menus.store') }}" method="post">
                @csrf
                @if ($menu->id)
                    @method('put')
                @endif
                <div class="card mb-6">
                    <div class="card-header bg-primary d-flex flex-column justify-content-center">
                        <h4 class="text-white text-uppercase mb-0">{{ $menu->id ? 'Cập nhật' : 'Thêm mới' }} menu</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-floating form-floating-outline mb-6 mt-6">
                            <input type="text" class="form-control" id="name" name="name" placeholder="Họ và tên"
                                autocomplete="off" value="{{ old('name') ?? $menu->name }}">
                            <label for="name">Tên menu</label>
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div class="form-floating form-floating-outline mb-6">
                            <textarea name="description" cols="30" rows="10" class="form-control h-px-100" placeholder="Mô tả">{{ old('description') ?? $menu->description }}</textarea>
                            <label for="description">Mô tả</label>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>
                        <div class="form-floating form-floating-outline mb-6 d-none">
                            <textarea name="structure" id="structure" cols="30" rows="10" class="form-control h-px-100">{{ old('structure') ?? $menu->structure }}</textarea>
                            <label for="structure">Cấu trúc</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-6">
                            <div>
                                @php
                                    $checkedPositions = old('position') ?? $positions;
                                @endphp
                                @foreach ($moduleThemes as $module)
                                    <small class="text-light fw-medium">Vị trí menu</small>
                                    @foreach ($module->ext->menu as $menuKey => $menuName)
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" value="1"
                                                name="position[{{ $menuKey }}]"
                                                id="{{ $module->getName() }}_{{ $menuKey }}"
                                                @if (isset($checkedPositions[$menuKey]) && $checkedPositions[$menuKey]) checked="" @endif>
                                            <label class="form-check-label"
                                                for="{{ $module->getName() }}_{{ $menuKey }}">{{ $menuName }}</label>
                                        </div>
                                    @endforeach
                                @endforeach
                                <input type="checkbox" name="position[holder]" value="0" checked style="display: none">
                            </div>
                        </div>
                    </div>
                    <hr class="m-0">
                    <div class="card-body">
                        <div class="mt-6 d-flex justify-content-end gap-2">
                            <a href="{{ route('menus.index') }}" type="button"
                                class="btn btn-outline-secondary waves-effect text-none">Hủy bỏ</a>
                            <button type="submit" class="btn btn-primary waves-effect waves-light" name="submit"
                                value="cc">Lưu</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-md-8">
            <div class="card mb-6">
                <h5 class="card-header">Cấu trúc menu</h5>
                <div class="card-body">
                    {{-- Các nút thêm mục: xuống dòng có khoảng cách, không dính nhau khi nhiều nút. --}}
                    <div class="d-flex flex-wrap gap-2 mb-4">
                    <button class="btn btn-primary add-menu-item btn-xs" data-type="url">Thêm liên kết</button>
                    <button class="btn btn-primary add-menu-item btn-xs" data-type="category">Thêm chuyên mục</button>
                    <button class="btn btn-primary add-menu-item btn-xs" data-type="docs">Thêm nhóm văn bản</button>
                    @if (collect($features ?? [])->contains('enabled', true))
                        <button class="btn btn-primary add-menu-item btn-xs" data-type="feature">{{ __('admin.menu_feature.add_button') }}</button>
                    @endif
                    @foreach ($menuItemTypes as $postType)
                        <button class="btn btn-primary add-menu-item btn-xs" data-type="{{ $postType->value }}">Thêm
                            {{ $postType->label() }}</button>
                    @endforeach
                    </div>
                    <div id="nestable" class="dd" style="width: 100%; display: contents;"></div>
                </div>
            </div>
        </div>
        <!-- Template section -->
        @include('pages.menu.partials.drag-and-drop-template')
        <!--/ Template section -->

        <!-- URL menu item -->
        @include('pages.menu.partials.create-url-menu-modal')
        <!--/ URL menu item -->

        <!-- Post menu item -->
        {{-- @foreach ($menuItemTypes as $postType)
            @include('pages.menu.partials.create-post-menu-modal', ['type' => $postType->value])
        @endforeach --}}
        @foreach ($menuItemTypes as $postType)
            @include('pages.menu.partials.create-post-menu-modal', [
                'type' => $postType->value,
                'postLabel' => $postType->label()
            ])
        @endforeach
        <!--/ URL menu item -->
        <!-- URL menu item -->
        @include('pages.menu.partials.create-docs-menu-modal')
        <!--/ URL menu item -->
        <!-- URL menu item -->
        @include('pages.menu.partials.create-category-menu-modal')
        <!--/ URL menu item -->
        <!-- Chức năng hệ thống -->
        @include('pages.menu.partials.create-feature-menu-modal')
        <!--/ Chức năng hệ thống -->


    </div>
@endsection
