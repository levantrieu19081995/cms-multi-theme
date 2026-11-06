@php
    $url = fn($suffix) => \Modules\CmsBackend\Support\ModuleUrl::route($resource.'.'.$suffix, $post->exists ? $post->id : []);
    $listUrl = \Modules\CmsBackend\Support\ModuleUrl::route($resource.'.index');
    $chosen = collect(old('category_ids', $post->taxonomies->where('taxonomy','category')->pluck('term_taxonomy_id')->all()))->map(fn($id)=>(int)$id);
    $chosenTags = collect(old('tag_ids',$post->taxonomies->where('taxonomy',\App\Enums\Taxonomy::POSTTAG->value)->pluck('term_taxonomy_id')->all()))->map(fn($id)=>(int)$id);
    $can = fn($action) => ($allowedActions[$action] ?? false);
    $stamp = fn($date) => $date ? \Carbon\Carbon::parse($date)->format('Y-m-d\TH:i') : '';
    $avatar = old('avatar',$post->getFirstMediaUrl('avatars'));
    $editorContent = \Modules\CmsBackend\Support\PublicContent::forEditor(old('translations.vi.content', old('content', $post->content)));
    $usesPostUpdate = $post->exists && $resource === 'cms.dashboard.cdn.bai-viet';
    $isEvent = ($type ?? 'post') === 'event';
    // Tin tức/sự kiện đã phê duyệt hoặc đang đăng: ô "hiển thị ở trang chủ" lưu ngay, không cần ngưng xuất bản (xem EditorialPostController::showHome).
    $instantHome = $post->exists && ($allowedActions['show_home'] ?? false) && in_array($post->status, [\App\Enums\PostStatus::APPROVED->value, \App\Enums\PostStatus::PUBLISHED->value], true);
    $recordNoun = $isEvent ? __('admin.ui.su_kien') : __('admin.ui.tin_tuc');
    $recordMenu = $isEvent ? __('admin.ui.quan_ly_su_kien') : __('admin.ui.quan_ly_bai_viet');
@endphp
<nav aria-label="{{ __('admin.ui.duong_dan_trang') }}" class="cms-breadcrumb"><i class="ri-home-4-line" aria-hidden="true"></i><a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard') }}">{{ __('admin.ui.quan_tri') }}</a><span>›</span><a href="{{ $listUrl }}">{{ $recordMenu }}</a><span>›</span><span>{{ ($post->exists ? __('admin.ui.sua_sp') : __('admin.ui.tao_sp')).$recordNoun }}</span></nav>
<div class="cms-page-heading cms-news-heading">
    <div><h1>{{ ($post->exists ? __('admin.ui.sua_sp') : __('admin.ui.tao_sp')).$recordNoun }}@if($post->exists)<span class="cms-status">{{ \Modules\CmsBackend\Services\EditorialPolicy::stateLabel($post->status) }}</span>@endif</h1><p class="cms-heading-note">{{ $post->exists ? __('admin.ui.cap_nhat_noi_dung_theo_trang_thai_xu_ly_hien_tai') : __('admin.ui.them_noi_dung_moi_de_dang_tai_len_he_thong') }} {{ __('admin.ui.cac_truong_co_dau') }} <span class="cms-required">*</span> {{ __('admin.ui.can_dien_truoc_khi_gui_duyet') }}</p></div>
    <div class="cms-form-actions">
        <a class="btn btn-outline-secondary" href="{{ $listUrl }}"><i class="ri-arrow-left-line" aria-hidden="true"></i>{{ __('admin.ui.tro_ve_danh_sach') }}</a>
        @foreach(['save_draft'=>[__('admin.ui.luu_nhap'),'ri-save-3-line'],'save_changes'=>[__('admin.ui.luu_chinh_sua'),'ri-save-3-line'],'submit_for_approval'=>[__('admin.ui.gui_phe_duyet'),'ri-send-plane-line'],'approved'=>[__('admin.ui.phe_duyet'),'ri-checkbox-circle-line'],'publish'=>[__('admin.ui.dang'),'ri-megaphone-line'],'stop_publishing'=>[__('admin.ui.ngung_hien_thi'),'ri-pause-circle-line']] as $action=>$button)
            @if($can($action))<button type="submit" form="cms-news-editorial-form" name="action" value="{{ $action }}" class="btn {{ $action === 'stop_publishing' ? 'btn-outline-danger' : ($action === 'save_draft' || $action === 'save_changes' ? 'cms-save' : 'btn-primary') }}"><i class="{{ $button[1] }}" aria-hidden="true"></i>{{ $button[0] }}</button>@endif
        @endforeach
        @if($post->exists && $editable)<a class="btn btn-outline-secondary cms-cancel" href="{{ $listUrl }}">{{ __('admin.ui.huy_bo') }}</a>@endif
    </div>
</div>
@if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if($history->first()?->action === 'reject')<div class="alert alert-warning"><strong>{{ __('admin.ui.yeu_cau_chinh_sua_2') }}</strong> {{ $history->first()->note }}</div>@endif
@if(!$editable)<div class="alert alert-info">{{ __('admin.ui.noi_dung_duoc_khoa_o_buoc_hien_tai') }}</div>@endif
<form id="cms-news-editorial-form" action="{{ $url($post->exists ? 'update' : 'store') }}" method="POST" class="cms-editorial-form cms-news-form">
    @csrf
    {{-- Production's outer proxy blocks Laravel's POST + _method=PUT request.
         The CMS article resource has an authenticated POST update route. --}}
    @if($post->exists && !$usesPostUpdate) @method('PUT') @endif
    <input type="hidden" name="revision" value="{{ $meta->revision }}">
    <div class="cms-form-grid">
        <div class="cms-form-column">
            <fieldset class="cms-form-column" @disabled(!$editable)>
                @include('cmsbackend::screens.components.editorial.language-tabs')
                @if(!$isEvent)<section class="cms-field-card"><label for="author_names">{{ __('admin.ui.tac_gia_noi_dung') }} <span class="cms-required">*</span></label><input id="author_names" class="form-control" name="author_names" maxlength="255" value="{{ old('author_names',$post->author_names) }}" placeholder="{{ __('admin.ui.nhap_ten_tac_gia') }}"></section>@endif
                <section class="cms-field-card"><label for="related_parties">{{ __('admin.ui.nguoi_don_vi_lien_quan') }}</label><textarea id="related_parties" name="related_parties" class="form-control" rows="1" maxlength="1000" placeholder="{{ __('admin.ui.nhap_thong_tin_nguoi_hoac_don_vi_lien_quan') }}">{{ old('related_parties',$meta->related_parties) }}</textarea></section>
            </fieldset>
            @include('cmsbackend::screens.components.editorial.history-timeline')
        </div>
        <div class="cms-form-column">
            <fieldset class="cms-form-column" @disabled(!$editable)>
                <section class="cms-field-card"><p class="cms-card-label" id="category-label">{{ __('admin.ui.chuyen_muc') }} <span class="cms-required">*</span></p><div class="cms-category-search"><i class="ri-search-line" aria-hidden="true"></i><input type="search" class="form-control" id="cms-category-search" placeholder="{{ __('admin.ui.tim_kiem_chuyen_muc_2') }}" aria-label="{{ __('admin.ui.tim_kiem_chuyen_muc') }}"></div>
                    <div id="category_ids" class="cms-category-list" role="group" aria-labelledby="category-label">
                        @forelse($categories as $category)<div class="cms-category-row"><input type="checkbox" id="editorial-category-{{ $category->term_taxonomy_id }}" name="category_ids[]" value="{{ $category->term_taxonomy_id }}" @checked($chosen->contains((int)$category->term_taxonomy_id))><label for="editorial-category-{{ $category->term_taxonomy_id }}">{{ $category->term?->name }}</label></div>@empty<p class="cms-help">{{ __('admin.ui.chua_duoc_phan_chuyen_muc_lien_he_quan_tri_de_du') }}</p>@endforelse
                        <p class="cms-help" id="cms-category-empty" hidden>{{ __('admin.ui.khong_tim_thay_chuyen_muc_phu_hop') }}</p>
                    </div>
                </section>
                @if($instantHome)
            </fieldset>
            <section class="cms-field-card cms-options"><p class="cms-card-label">{{ __('admin.ui.tuy_chon') }}</p>
                <label><input type="checkbox" id="cms-show-home-instant" data-url="{{ $url('show-home') }}" @checked($post->show_home_page)><span>{{ __('admin.ui.hien_thi_o_trang_chu') }}<small>{{ __('admin.ui.bai_viet_se_hien_tai_trang_chu_neu_duoc_chon') }}</small></span></label>
                {{-- Đã duyệt / đang đăng thì nội dung bị khóa: ô bình luận chỉ hiển thị trạng thái hiện tại, không lưu. --}}
                <label><input type="checkbox" disabled @checked($post->comment_status === 'published')><span>{{ __('admin.ui.cho_phep_binh_luan') }}<small>{{ __('admin.ui.cho_phep_nguoi_dung_binh_luan_ve_bai_viet_nay') }}</small></span></label>
                <script>
                document.getElementById('cms-show-home-instant')?.addEventListener('change', async function () {
                    const box = this, wanted = box.checked; box.disabled = true;
                    try {
                        const response = await fetch(box.dataset.url, {method: 'POST', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '', 'Accept': 'application/json', 'Content-Type': 'application/json'}, body: JSON.stringify({value: wanted ? 1 : 0})});
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) box.checked = !wanted;
                        if (window.toastr) (response.ok ? toastr.success : toastr.error)(data.message || '');
                    } catch { box.checked = !wanted; if (window.toastr) toastr.error(@json(__('admin.js.co_loi_xay_ra_vui_long_thu_lai'))); }
                    box.disabled = false;
                });
                </script>
            </section>
            <fieldset class="cms-form-column" @disabled(!$editable)>
                @else
                <section class="cms-field-card cms-options"><p class="cms-card-label">{{ __('admin.ui.tuy_chon') }}</p><label><input type="checkbox" name="show_home_page" value="1" @checked(old('show_home_page',$post->show_home_page))><span>{{ __('admin.ui.hien_thi_o_trang_chu') }}<small>{{ __('admin.ui.bai_viet_se_hien_tai_trang_chu_neu_duoc_chon') }}</small></span></label><label><input type="checkbox" name="allow_comments" value="1" @checked(old('allow_comments',$post->comment_status === 'published'))><span>{{ __('admin.ui.cho_phep_binh_luan') }}<small>{{ __('admin.ui.cho_phep_nguoi_dung_binh_luan_ve_bai_viet_nay') }}</small></span></label></section>
                @endif
                <section class="cms-field-card"><p class="cms-card-label">{{ __('admin.ui.anh_dai_dien') }}</p><input type="hidden" id="thumbnail" name="avatar" value="{{ $avatar }}"><div id="holder">@if($avatar)<img src="{{ $avatar }}" alt="{{ __('admin.ui.anh_dai_dien') }}">@endif</div><div class="cms-image-picker"><span class="cms-image-symbol"><i class="ri-image-line" aria-hidden="true"></i></span><span class="cms-image-text">{{ __('admin.ui.chon_anh_dai_dien') }}<small>{{ __('admin.ui.anh_png_jpg_jpeg_hoac_gif') }}</small></span>@if($editable)<button id="lfm" type="button" data-input="thumbnail" data-preview="holder" class="btn btn-outline-primary"><i class="ri-folder-open-line" aria-hidden="true"></i>{{ __('admin.ui.chon_anh') }}</button>@endif</div></section>
                @if($isEvent)
                    <section class="cms-field-card"><label for="event_starts_at">{{ __('admin.ui.thoi_gian_bat_dau_su_kien') }} <span class="cms-required">*</span></label><input type="datetime-local" class="form-control" id="event_starts_at" name="event_starts_at" value="{{ old('event_starts_at',$stamp($meta->event_starts_at)) }}"></section>
                    <section class="cms-field-card"><label for="event_ends_at">{{ __('admin.ui.thoi_gian_ket_thuc_su_kien') }} <span class="cms-required">*</span></label><input type="datetime-local" class="form-control" id="event_ends_at" name="event_ends_at" value="{{ old('event_ends_at',$stamp($meta->event_ends_at)) }}"></section>
                @endif
                <section class="cms-field-card"><p class="cms-card-label">{{ __('admin.ui.tap_tin_dinh_kem') }}</p>
                    @if($editable)<x-input-file-select id="editorial-files" name="fileDinhKem" :value="old('fileDinhKem', $post->getMedia('fileDinhKem')->map(fn($file) => $file->getUrl())->values()->toJson())" :multiple="true" />
                    @else<ul>@foreach($post->getMedia('fileDinhKem') as $file)<li><a href="{{ $file->getUrl() }}" target="_blank" rel="noopener">{{ $file->file_name }}</a></li>@endforeach</ul>@endif
                </section>
                <section class="cms-field-card"><label for="cms-tag-search">{{ __('admin.ui.the') }}</label><input type="search" id="cms-tag-search" class="form-control" placeholder="{{ __('admin.ui.tim_the_co_san_va_nhan_enter') }}" @disabled($tags->isEmpty())><div class="cms-tags" role="group" aria-label="{{ __('admin.ui.the_bai_viet') }}">
                    @foreach($tags as $tag)<span class="cms-tag"><input type="checkbox" id="editorial-tag-{{ $tag->term_taxonomy_id }}" name="tag_ids[]" value="{{ $tag->term_taxonomy_id }}" @checked($chosenTags->contains((int)$tag->term_taxonomy_id))><label for="editorial-tag-{{ $tag->term_taxonomy_id }}">{{ $tag->term?->name }}</label></span>@endforeach
                </div><p class="cms-help" id="cms-tag-empty" @if($tags->isNotEmpty()) hidden @endif>{{ $tags->isEmpty() ? __('admin.ui.chua_co_the_de_chon') : __('admin.ui.khong_tim_thay_the_phu_hop') }}</p><p class="cms-help">{{ __('admin.ui.dung_the_de_phan_loai_noi_dung_vi_du_nong_nghiep') }}</p></section>
            </fieldset>
            @if($can('publish'))<section class="cms-field-card"><p class="cms-card-label">{{ __('admin.ui.dang_len_website') }}</p><label for="publish_type">{{ __('admin.ui.thoi_diem_hien_thi') }}</label><select id="publish_type" name="publish_type" class="form-select mb-3"><option value="publish_now">{{ __('admin.ui.hien_thi_khi_bam_dang') }}</option><option value="schedule_publication" @selected(old('publish_type',$post->publish_type) === 'schedule_publication')>{{ __('admin.ui.len_lich_hien_thi') }}</option></select><label for="scheduled_at">{{ __('admin.ui.ngay_hen_hien_thi_khi_len_lich') }}</label><input type="datetime-local" id="scheduled_at" name="scheduled_at" class="form-control mb-3" value="{{ old('scheduled_at',$stamp($post->published_at)) }}"><label for="publication_date_expires">{{ __('admin.ui.ngay_het_han_hien_thi_tuy_chon') }}</label><input type="datetime-local" id="publication_date_expires" name="publication_date_expires" class="form-control" value="{{ old('publication_date_expires', $isEvent && !$meta->exists ? '' : $stamp($post->publication_date_expires)) }}"></section>@endif
        </div>
    </div>
</form>
@if($can('reject'))<form action="{{ $url('reject') }}" method="POST" class="cms-field-card cms-extra-panel cms-news-form">@csrf<input type="hidden" name="revision" value="{{ $meta->revision }}"><label for="feedback">{{ __('admin.ui.ly_do_yeu_cau_chinh_sua') }}</label><textarea id="feedback" name="feedback" class="form-control mb-3" rows="3" required maxlength="2000">{{ old('feedback') }}</textarea><button class="btn btn-outline-danger">{{ __('admin.ui.yeu_cau_chinh_sua') }}</button></form>@endif
@if($can('delete'))<form method="POST" action="{{ $url('destroy') }}" class="cms-extra-panel cms-news-form">@csrf @method('DELETE')<input type="hidden" name="revision" value="{{ $meta->revision }}"><button class="btn btn-outline-danger">{{ __('admin.ui.xoa_ban_name', ['name' => mb_strtolower($heading)]) }}</button></form>@endif

@if($post->exists && ($allowedActions['force_delete'] ?? false))
<div class="cms-extra-panel cms-news-form">@include('cmsbackend::screens.components.editorial.purge-form', ['purgeRevision' => (int) $meta->revision])</div>
@endif
