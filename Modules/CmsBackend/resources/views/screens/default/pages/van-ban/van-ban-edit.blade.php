@php
    $configData = Helper::appClasses();
@endphp
@extends('cmsbackend::shared.layouts/layoutMaster')
@section('title', __('admin.menu.quan_ly_van_ban'))

@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.scss']))
@endsection

@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.js', 'assets/vendor/libs/bootstrap-select/bootstrap-select.js']))
@endsection

@section('page-script')
    @vite(['resources/assets/js/forms-selects.js', 'resources/assets/js/table-confirm-delete.js'])
    <script src="/vendor/laravel-filemanager/js/stand-alone-button.js" type="module"></script>
    {{-- <script>
        document.addEventListener('DOMContentLoaded', function() {
            const unlimitedCheckbox = document.getElementById('unlimited_storage');
            const input = document.getElementById('vb_thoi_gian_luu_tru_input');

            function toggleInput() {
                const luuTru = {{ (int) ($item->vb_thoi_gian_luu_tru ?? 1) }};
                if (unlimitedCheckbox.checked) {
                    input.type = 'text';
                    input.value = 'Vô hạn';
                    input.readOnly = true;
                } else {
                    input.type = 'number';
                    input.value = luuTru;
                    input.readOnly = false; 
                }
            }

            toggleInput(); 
            unlimitedCheckbox.addEventListener('change', toggleInput); 
            const form = input.closest('form');
            if (form) {
                form.addEventListener('submit', function() {
                    if (input.value === 'Vô hạn') {
                        input.value = -1;
                    }
                });
            }
        });
    </script> --}}
 <script>
document.addEventListener('DOMContentLoaded', () => {
  const chk = document.getElementById('unlimited_storage');
  const display = document.getElementById('vb_thoi_gian_luu_tru_display'); // chỉ để hiển thị
  const hidden  = document.getElementById('vb_thoi_gian_luu_tru_input');   // gửi lên server

  let lastNumeric = parseInt(hidden.value, 10);
  if (isNaN(lastNumeric) || lastNumeric < 1) lastNumeric = 1;

  function applyUnlimited() {
    if (chk.checked) {
      display.readOnly = true;
      display.value = @json(__('admin.js.vo_han'));
      hidden.value = -1;              // <-- server luôn nhận số
    } else {
      display.readOnly = false;
      display.value = lastNumeric;
      hidden.value = lastNumeric;
    }
  }

  display.addEventListener('input', () => {
    const v = parseInt(display.value, 10);
    if (!isNaN(v)) { lastNumeric = v; hidden.value = v; }
  });

  chk.addEventListener('change', applyUnlimited);
  applyUnlimited();
});

</script>


@endsection

@section('content')
    {{ $item->vb_id ? Breadcrumbs::render('cms.dashboard.cdn.van-ban.edit', '') : Breadcrumbs::render('cms.dashboard.cdn.van-ban.create', '') }}
    <div class="card">
        <div class="card-header bg-primary d-flex flex-column justify-content-center">
            <h4 class="text-white text-uppercase mb-0">{{ $item->vb_id ? __('admin.ui.cap_nhat') : __('admin.ui.add_new') }} {{ __('admin.resource.document') }}</h4>
        </div>
        <div class="card-body">
            <div class="app-ecommerce">
                <form novalidate
                    action="{{ $item->vb_id ? \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.van-ban.update', $item) : \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.van-ban.store') }}"
                    method="post"
                    enctype="multipart/form-data">
                    @csrf
                    @if ($item->vb_id)
                        @method('put')
                    @endif
                    <div class="row">
                        <div class='col-12 my-5'>
                            <div class="d-flex justify-content-end align-items-center gap-3">
                                <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.van-ban.index') }}"
                                    class="btn btn-outline-secondary waves-effect text-none">
                                    {{ __('admin.ui.tro_ve') }}
                                </a>
                                <button class="btn btn-primary waves-effect text-none"
                                    type="submit">{{ $item->vb_id ? __('admin.ui.cap_nhat') : __('admin.ui.them') }}</button>
                            </div>
                        </div>
                        <div class="col-12 col-lg-8 mb-6 ">
                            <div class='card'>
                                <div class="card-header">
                                    <h5 class="card-title mb-0">{{ __('admin.ui.can_cu_phap_ly') }}</h5>
                                </div>
                                <div class='card-body'>
                                    <div class="">
                                        <div class="form-floating form-floating-outline mb-6">
                                            <select name="nvb_id" id="nvb_id" class="form-control">
                                                @foreach ($listNhomVanBan as $nhomVanBan)
                                                    <option value="{{ $nhomVanBan->nvb_id }}"
                                                        @if ((old('nvb_id') ?? $item->nvb_id) == $nhomVanBan->nvb_id) selected @endif>
                                                        {{ $nhomVanBan->nvb_name }}
                                                    </option>
                                                @endforeach
                                                @if ($listNhomVanBan->isEmpty())
                                                    <option value="0">{{ __('admin.ui.khong_tim_thay_nhom_van_ban') }}</option>
                                                @endif
                                            </select>
                                            <label for="nvb_ten">{{ __('admin.ui.nhom_van_ban') }}</label>
                                            <x-input-error :messages="$errors->get('nvb_id')" class="mt-2" />
                                        </div>

                                        <div class="form-floating form-floating-outline mb-6">
                                            <select name="lvb_id" id="lvb_id" class="form-control">
                                                @foreach ($listLoaiVanBan as $loaiVanBan)
                                                    <option value="{{ $loaiVanBan->lvb_id }}"
                                                        @if ((old('lvb_id') ?? $item->lvb_id) == $loaiVanBan->lvb_id) selected @endif>
                                                        {{ $loaiVanBan->lvb_ma }} - {{ $loaiVanBan->lvb_ten }}
                                                    </option>
                                                @endforeach
                                                @if ($listLoaiVanBan->isEmpty())
                                                    <option value="0">{{ __('admin.ui.khong_tim_thay_loai_van_ban') }}</option>
                                                @endif
                                            </select>
                                            <label for="lvb_ten">{{ __('admin.ui.loai_van_ban') }}</label>
                                            <x-input-error :messages="$errors->get('lvb_id')" class="mt-2" />
                                        </div>

                                        <div class="form-floating form-floating-outline mb-6">
                                            <input type="text" class="form-control" id="vb_so_hieu" name="vb_so_hieu"
                                                placeholder="{{ __('admin.ui.so_hieu') }}" autocomplete="off"
                                                value="{{ old('vb_so_hieu') ?? $item->vb_so_hieu }}">
                                            <label for="vb_so_hieu">{{ __('admin.ui.so_hieu') }}</label>
                                            <x-input-error :messages="$errors->get('vb_so_hieu')" class="mt-2" />
                                        </div>
                                        <div class="form-floating form-floating-outline mb-6">
                                            <textarea type="text" class="form-control h-px-100" id="vb_trich_yeu" name="vb_trich_yeu" placeholder="{{ __('admin.ui.trich_yeu') }}"
                                                autocomplete="off">{{ old('vb_trich_yeu') ?? $item->vb_trich_yeu }}</textarea>
                                            <label for="vb_trich_yeu">{{ __('admin.ui.trich_yeu') }}</label>
                                            <x-input-error :messages="$errors->get('vb_trich_yeu')" class="mt-2" />
                                        </div>
                                        <div class="form-floating form-floating-outline mb-6">
                                            <input type="text" class="form-control" id="vb_co_quan_ban_hanh"
                                                name="vb_co_quan_ban_hanh" placeholder="{{ __('admin.ui.co_quan_ban_hanh') }}" autocomplete="off"
                                                value="{{ old('vb_co_quan_ban_hanh') ?? $item->vb_co_quan_ban_hanh }}">
                                            <label for="vb_co_quan_ban_hanh">{{ __('admin.ui.co_quan_ban_hanh') }}</label>
                                            <x-input-error :messages="$errors->get('vb_co_quan_ban_hanh')" class="mt-2" />
                                        </div>
                                        <div class="form-floating form-floating-outline mb-3">
                                            <p>{{ __('admin.ui.nhap_duong_dan_tap_tin_van_ban') }}</p>
                                        </div>
                                        <div class="form-floating form-floating-outline">
                                            <input type="text" class="form-control text-primary" id="vb_file_url"
                                                name="vb_file_url" placeholder="{{ __('admin.ui.duong_dan_den_van_ban_online') }}" autocomplete="off"
                                                value="{{ old('vb_file_url') ?? $item->vb_file_url }}">
                                            <label for="vb_file_url">{{ __('admin.ui.duong_dan_van_ban_url') }}</label> 
                                        </div>
                                        <div class="form-floating form-floating-outline mt-3">
                                            <p>{{ __('admin.ui.hoac_chon_tap_tin_tu_may_tinh') }}</p>
                                        </div>
                                        <div>
                                            @php
                                                $fileDinhKemValue = old('fileDinhKem');
                                                if ($fileDinhKemValue === null) {
                                                    $fileDinhKemValue = $item->fileDinhKem instanceof \Illuminate\Support\Collection
                                                        ? $item->fileDinhKem->toJson()
                                                        : json_encode($item->fileDinhKem ?? []);
                                                }
                                                $val = html_entity_decode($fileDinhKemValue);
                                                $btnname = __('admin.ui.dinh_kem_hinh_anh');
                                            @endphp
                                            <x-input-file-select id="file_multiple"   name="fileDinhKem"
                                                :value=$val :multiple="true">

                                            </x-input-file-select>
                                        </div> 
                                        <x-input-error :messages="$errors->get('vb_file_url')" class="mt-2" />
                                    <div> 
                                        </div>  

                                    </div>

                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4 mb-6 ">
                            <div class='card mb-6'>
                                <div class="card-header">
                                    <h5 class="card-title mb-0">{{ __('admin.ui.thoi_han_van_ban') }}</h5>
                                </div>
                                <div class='card-body'>
                                    <div class='row'>
                                        <div class='col-12 col-lg-6'>
                                            <div class="form-floating form-floating-outline mb-6">
                                                <input type="date" class="form-control" id="vb_ngay_ban_hanh"
                                                    name="vb_ngay_ban_hanh" placeholder="{{ __('admin.ui.ngay_ban_hanh') }}" autocomplete="off"
                                                    value="{{ old('vb_ngay_ban_hanh') ?? $item->vb_ngay_ban_hanh->format('Y-m-d') }}">
                                                <label for="vb_ngay_ban_hanh">{{ __('admin.ui.ngay_ban_hanh') }}</label>
                                                <x-input-error :messages="$errors->get('vb_ngay_ban_hanh')" class="mt-2" />
                                            </div>
                                            
                                        </div>
                                        <div class='col-12 col-lg-6'>
                                            <div class="form-floating form-floating-outline mb-6">
                                                <input type="date" class="form-control" id="vb_ngay_hieu_luc"
                                                    name="vb_ngay_hieu_luc" placeholder="{{ __('admin.ui.ngay_hieu_luc') }}"
                                                    autocomplete="off"
                                                    value="{{ old('vb_ngay_hieu_luc') ?? $item->vb_ngay_hieu_luc->format('Y-m-d') }}">
                                                <label for="vb_ngay_hieu_luc">{{ __('admin.ui.ngay_hieu_luc') }}</label>
                                                <x-input-error :messages="$errors->get('vb_ngay_hieu_luc')" class="mt-2" />
                                            </div>
                                            
                                        </div>
                                    </div>

                                    <div class='row'>
                                        <div class='col-12 col-lg-6'>
                                            <div class="form-floating form-floating-outline mb-6">
                                                <input type="date" class="form-control" id="vb_ngay_cong_bo"
                                                    name="vb_ngay_cong_bo" placeholder="{{ __('admin.ui.ngay_cong_bo') }}" autocomplete="off"
                                                    value="{{ old('vb_ngay_cong_bo') ?? $item->vb_ngay_cong_bo->format('Y-m-d') }}">
                                                <label for="vb_ngay_cong_bo">{{ __('admin.ui.ngay_cong_bo') }}</label>
                                                <x-input-error :messages="$errors->get('vb_ngay_cong_bo')" class="mt-2" />
                                            </div>
                                        </div>
                                        <div class='col-12 col-lg-6'>
                                            {{-- <div class="form-floating form-floating-outline mb-6">
                                                <input type="number" class="form-control" id="vb_thoi_gian_luu_tru"
                                                    name="vb_lan_ban_hanh" placeholder="Lần ban hành" min="1"
                                                    step="1" autocomplete="off"
                                                    value="{{ old('vb_thoi_gian_luu_tru', 1) }}">
                                                <label for="vb_lan_ban_hanh">Lần ban hành</label>
                                                <x-input-error :messages="$errors->get('vb_lan_ban_hanh')" class="mt-2" />
                                            </div>  --}}
                                            <div class="form-floating form-floating-outline mb-6">
                                                <input  type="number"
                                                    class="form-control"
                                                    id="vb_lan_ban_hanh"
                                                    name="vb_lan_ban_hanh"
                                                    placeholder="{{ __('admin.ui.lan_ban_hanh') }}"
                                                    min="1" step="1" autocomplete="off"
                                                    value="{{ old('vb_lan_ban_hanh', $item->vb_lan_ban_hanh ?? 1) }}"
                                                    oninvalid="this.setCustomValidity('')"  {{-- chặn message mặc định --}}
                                                    oninput="this.setCustomValidity('')">   {{-- xoá message mặc định --}}
                                                <label for="vb_lan_ban_hanh">{{ __('admin.ui.lan_ban_hanh') }}</label>
                                                <x-input-error :messages="$errors->get('vb_lan_ban_hanh')" class="mt-2" />
                                            </div>
                                            
                                        </div>
                                    </div>

                                    <div class='row'>
                                        <div class='col-8 col-lg-6'>
                                            {{-- <div class="form-floating form-floating-outline mb-6">
                                                <input class="form-control" id="vb_thoi_gian_luu_tru_input"
                                                    name="vb_thoi_gian_luu_tru" placeholder="Số năm lưu trữ"
                                                    autocomplete="off"
                                                    value="{{ (old('vb_thoi_gian_luu_tru') ?? $item->vb_thoi_gian_luu_tru) == -1 ? 'Vô hạn' : old('vb_thoi_gian_luu_tru') ?? $item->vb_thoi_gian_luu_tru }}">
                                                <label for="vb_thoi_gian_luu_tru">Thời gian lưu trữ (Năm)</label>
                                                <x-input-error :messages="$errors->get('vb_thoi_gian_luu_tru')" class="mt-2" />
                                            </div> --}}
                                            <div class="form-floating form-floating-outline mb-6">
                                                {{-- <input  class="form-control"
                                                        id="vb_thoi_gian_luu_tru_input"
                                                        name="vb_thoi_gian_luu_tru"
                                                        placeholder="Số năm lưu trữ"
                                                        autocomplete="off"
                                                        value="{{ (old('vb_thoi_gian_luu_tru') ?? $item->vb_thoi_gian_luu_tru) == -1
                                                                    ? 'Vô hạn'
                                                                    : old('vb_thoi_gian_luu_tru', $item->vb_thoi_gian_luu_tru) }}"> --}}

                                                    {{-- Hiển thị cho người dùng (KHÔNG có name) --}}
                                                    <input class="form-control" id="vb_thoi_gian_luu_tru_display"
                                                        value="{{ ($item->vb_thoi_gian_luu_tru ?? old('vb_thoi_gian_luu_tru')) == -1 ? __('admin.js.vo_han') : old('vb_thoi_gian_luu_tru', $item->vb_thoi_gian_luu_tru) }}">

                                                    {{-- Input thật gửi lên server --}}
                                                    <input type="hidden" id="vb_thoi_gian_luu_tru_input" name="vb_thoi_gian_luu_tru"
                                                        value="{{ old('vb_thoi_gian_luu_tru', $item->vb_thoi_gian_luu_tru ?? 1) }}">

                                                <label for="vb_thoi_gian_luu_tru_input">{{ __('admin.ui.thoi_gian_luu_tru_nam') }}</label>
                                                <x-input-error :messages="$errors->get('vb_thoi_gian_luu_tru')" class="mt-2" />
                                            </div>

                                        </div>
                                        <div class='col-4 col-lg-6'>
                                            <div class="row mb-2">
                                                <div class="col-lg-8">
                                                    <b>{{ __('admin.ui.vo_thoi_han') }}</b>
                                                </div>
                                                <div class="col-lg-4">
                                                    <label class="switch switch-success">
                                                        <input type="checkbox" id="unlimited_storage"
                                                            name="show_home_page" class="switch-input"
                                                            {{ $item->vb_thoi_gian_luu_tru == -1 ? 'checked' : '' }} />
                                                        <span class="switch-toggle-slider">
                                                            <span class="switch-on">
                                                                <i class="ri-check-line"></i>
                                                            </span>
                                                            <span class="switch-off">
                                                                <i class="ri-close-line"></i>
                                                            </span>
                                                        </span>

                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
