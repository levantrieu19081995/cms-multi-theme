{{-- Menu loại "Chức năng hệ thống": chọn chức năng + điểm đến từ Feature Registry. Chỉ lưu tham chiếu (feature_key, feature_target); URL sinh từ route khi hiển thị. --}}
<script>
    window.featureChoices = @json($features ?? []);
    window.featureText = @json(__('admin.menu_feature'));
</script>
<div id="modal-feature-div">
    <div class="modal fade" id="create_feature_menu_modal" tabindex="-1" aria-modal="true" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"><span class="action-mode">{{ __('admin.menu_feature.add') }}</span> {{ __('admin.menu_feature.modal_title') }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col mb-6 mt-2">
                            <div class="form-floating form-floating-outline">
                                <input type="hidden" name="url" value="">
                                <input type="hidden" name="id">
                                <input type="hidden" name="type">
                                <input type="hidden" name="ref_id" value="0">
                                <input type="text" class="form-control" placeholder="{{ __('admin.menu_feature.text') }}" name="title" maxlength="255">
                                <label>{{ __('admin.menu_feature.text') }}</label>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col mb-6 mt-2">
                            <div class="form-floating form-floating-outline">
                                <select name="feature_key" class="form-control">
                                    @foreach (($features ?? []) as $feature)
                                        <option value="{{ $feature['key'] }}">{{ $feature['name'] }}@unless ($feature['enabled']) {{ __('admin.menu_feature.off_suffix') }}@endunless</option>
                                    @endforeach
                                </select>
                                <label>{{ __('admin.menu_feature.feature') }}</label>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col mb-6 mt-2">
                            <div class="form-floating form-floating-outline">
                                <select name="feature_target" class="form-control"></select>
                                <label>{{ __('admin.menu_feature.target') }}</label>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col mb-6 mt-2">
                            <div class="form-floating form-floating-outline">
                                <select class="form-control" name="target">
                                    <option value="_self">{{ __('admin.menu_feature.self') }}</option>
                                    <option value="_blank">{{ __('admin.menu_feature.blank') }}</option>
                                </select>
                                <label>{{ __('admin.menu_feature.open_in') }}</label>
                            </div>
                        </div>
                    </div>
                    @include('pages.menu.partials.icon-picker')
                    @include('pages.menu.partials.english-fields', ['withUrl' => false])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary waves-effect" data-bs-dismiss="modal">{{ __('admin.menu_feature.cancel') }}</button>
                    <button type="button" class="btn btn-primary waves-effect waves-light create_menu_item" data-form="create_feature_menu_modal">{{ __('admin.menu_feature.save') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>
