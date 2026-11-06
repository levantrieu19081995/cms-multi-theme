<!-- @extends('cmsfrontend::layouts.layoutMaster') -->
@extends('cmsbackend::shared.layouts/layoutMaster')
@section('title', __('admin.menu.hoi_dap'))

@section('content')
    <div class="container mt-4">
        <h2>{{ __('admin.ui.gui_cau_hoi') }}</h2>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.frontend.hoidap.store') }}">
            @csrf
            <div class="mb-3">
                <label for="name">{{ __('admin.ui.ho_ten') }}</label>
                <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
            </div>
            <div class="mb-3">
                <label for="phone">{{ __('admin.ui.so_dien_thoai') }}</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
            </div>
            <div class="mb-3">
                <label for="email">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}">
            </div>
            <div class="mb-3">
                <label for="title">{{ __('admin.ui.tieu_de_cau_hoi') }}</label>
                <input type="text" name="title" class="form-control" required value="{{ old('title') }}">
            </div>
            <div class="mb-3">
                <label for="content">{{ __('admin.ui.noi_dung_cau_hoi') }}</label>
                <textarea name="content" class="form-control" required rows="4">{{ old('content') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">{{ __('admin.ui.gui_cau_hoi') }}</button>
        </form>

        <hr>

        <h3 class="mt-5">{{ __('admin.ui.danh_sach_cau_hoi_da_tra_loi') }}</h3>
        @if ($questions->count())
            @foreach ($questions as $q)
                <div class="card mb-3">
                    <div class="card-header">
                        <strong>{{ $q->title }}</strong>
                    </div>
                    <div class="card-body">
                        <p><strong>{{ __('admin.ui.hoi') }}</strong> {{ $q->content }}</p>
                        <p><strong>{{ __('admin.ui.tra_loi_2') }}</strong> {{ $q->answer }}</p>
                        <p class="text-muted"><em>{{ __('admin.ui.nguoi_gui') }} {{ $q->name ?? __('admin.ui.an_danh') }}</em></p>
                    </div>
                </div>
            @endforeach
        @else
            <p>{{ __('admin.ui.hien_chua_co_cau_hoi_nao_duoc_tra_loi') }}</p>
        @endif
    </div>
@endsection
