@extends('cmsbackend::screens.components.shared.layouts.layoutMaster')
@section('title', __('admin.ui.quan_ly_sp').mb_strtolower($heading))
@section('cms-list-title', __('admin.ui.quan_ly_sp').mb_strtolower($heading))
@section('cms-list-note', __('admin.ui.theo_doi_noi_dung_va_xu_ly_theo_quy_trinh_phe_du'))
@section('cms-list-create')
{{-- Page actions in the heading row: export (news only) next to "Tạo …". --}}
<div class="cms-page-actions">
@if(auth()->user()->hasRole('Quản trị'))
<a class="btn btn-outline-secondary" href="{{ \Modules\CmsBackend\Support\ModuleUrl::route($resource.'.index', request()->boolean('trash') ? [] : ['trash' => 1]) }}">{{ request()->boolean('trash') ? __('admin.ui.danh_sach_hien_co') : __('admin.ui.da_xoa') }}</a>
@endif
@if($type === 'post')
<a class="btn btn-outline-secondary" href="{{ \Modules\CmsBackend\Support\ModuleUrl::route($resource.'.export', request()->query()) }}"><i class="ri-file-excel-2-line" aria-hidden="true"></i>{{ __('admin.ui.xuat_danh_sach') }}</a>
<button id="cms-export-preview" type="button" class="btn btn-outline-secondary" data-url="{{ \Modules\CmsBackend\Support\ModuleUrl::route($resource.'.export.preview', request()->query()) }}"><i class="ri-eye-line" aria-hidden="true"></i>{{ __('admin.ui.xem_truoc_danh_sach_xuat') }}</button>
@endif
@if($canCreate)<a class="btn btn-primary" href="{{ \Modules\CmsBackend\Support\ModuleUrl::route($resource.'.create') }}"><i class="ri-add-line" aria-hidden="true"></i>{{ __('admin.ui.tao_sp') }}{{ mb_strtolower($heading) }}</a>@endif
</div>
@endsection
@section('page-script')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const csrf=document.querySelector('meta[name=csrf-token]')?.content||'';
    document.querySelectorAll('input[data-home-toggle]').forEach(box=>box.addEventListener('change',async()=>{
        const wanted=box.checked; box.disabled=true;
        try {
            const response=await fetch(box.dataset.url,{method:'POST',headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json','Content-Type':'application/json'},body:JSON.stringify({value:wanted?1:0})});
            const data=await response.json().catch(()=>({}));
            if(!response.ok) box.checked=!wanted;
            if(window.toastr) (response.ok?toastr.success:toastr.error)(data.message||'');
        } catch { box.checked=!wanted; if(window.toastr) toastr.error(@json(__('admin.js.co_loi_xay_ra_vui_long_thu_lai'))); }
        box.disabled=false;
    }));
});
</script>
@if($type === 'post')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const button=document.getElementById('cms-export-preview'), dialog=document.getElementById('cms-export-dialog');
    button.addEventListener('click',async()=>{
        const text=@json(__('admin.js.export'));
        dialog.showModal();const status=document.getElementById('cms-export-status'),body=document.getElementById('cms-export-rows');body.replaceChildren();status.textContent=text.loading;
        try {const response=await fetch(button.dataset.url,{headers:{Accept:'application/json'}});if(!response.ok)throw Error();const result=await response.json();status.textContent=text.summary.replace(':shown',result.data.length).replace(':total',result.total);
            for(const item of result.data){const row=document.createElement('tr');for(const key of ['tt','title','category','author_name','publisher_name','published_at']){const cell=document.createElement('td');cell.textContent=item[key]??'';row.append(cell);}body.append(row);}
        }catch {status.textContent=text.failed;}
    });
    document.getElementById('cms-export-close').addEventListener('click',()=>dialog.close());
});
</script>
@endif
@endsection
@section('content')
<div class="card">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="GET" class="cms-filter-panel">
            @if(request()->boolean('trash'))<input type="hidden" name="trash" value="1">@endif
            <label>{{ __('admin.ui.tim_tieu_de') }}<input class="form-control" name="filter" value="{{ request('filter') }}"></label>
            <label>{{ __('admin.ui.trang_thai') }}<select class="form-select" name="status"><option value="">{{ __('admin.ui.tat_ca') }}</option>@foreach(\App\Enums\PostStatus::cases() as $state)<option value="{{ $state->value }}" @selected(request('status') === $state->value)>{{ \Modules\CmsBackend\Services\EditorialPolicy::stateLabel($state->value) }}</option>@endforeach</select></label>
            <label>{{ __('admin.ui.chuyen_muc') }}<select class="form-select" name="category"><option value="">{{ __('admin.ui.tat_ca') }}</option>@foreach($categoryOptions as $option)<option value="{{ $option->term_taxonomy_id }}" @selected((string) request('category') === (string) $option->term_taxonomy_id)>{{ $option->term->name }}</option>@endforeach</select></label>
            <button class="btn btn-outline-primary align-self-end">{{ __('admin.ui.loc') }}</button>
        </form>
        <div class="table-responsive"><table class="table cms-list-table cms-list-table--home">
            <thead><tr><th>{{ __('admin.ui.tieu_de') }}</th><th>{{ __('admin.ui.tac_gia_soan') }}</th><th>{{ __('admin.ui.trang_thai') }}</th><th>{{ __('admin.ui.hien_thi_o_trang_chu') }}</th><th>{{ __('admin.ui.ngon_ngu') }}</th><th>{{ __('admin.ui.chuyen_muc') }}</th><th>{{ __('admin.ui.ngay_dang') }}</th><th>{{ __('admin.ui.thao_tac') }}</th></tr></thead>
            <tbody>@forelse($posts as $post)<tr>
                <td>@if($post->deleted_at){{ $post->title }}@else<a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route($resource.'.edit', $post->id) }}">{{ $post->title }}</a>@endif</td>
                <td>{{ $post->author?->name }}</td>
                <td><span class="cms-state cms-state-{{ $post->status }}">{{ \Modules\CmsBackend\Services\EditorialPolicy::stateLabel($post->status) }}</span></td>
                @php
                    // Chỉ bật được khi đã phê duyệt hoặc đang đăng; đang bật thì luôn tắt được. Bật/tắt không đổi trạng thái đăng.
                    $homeOn = (bool) $post->show_home_page;
                    $homeEligible = in_array($post->status, [\App\Enums\PostStatus::APPROVED->value, \App\Enums\PostStatus::PUBLISHED->value], true);
                    $homeCan = array_key_exists($post->id, $homeToggle ?? []) && ($homeEligible || $homeOn);
                @endphp
                <td class="text-center"><label class="switch switch-success mb-0" @unless($homeCan) title="{{ __('admin.message.su_kien_phai_duyet_moi_hien_trang_chu') }}" @endunless>
                    <input type="checkbox" class="switch-input" data-home-toggle data-url="{{ \Modules\CmsBackend\Support\ModuleUrl::route($resource.'.show-home', $post->id) }}" aria-label="{{ __('admin.ui.hien_thi_o_trang_chu') }}" @checked($homeOn) @disabled(! $homeCan)>
                    <span class="switch-toggle-slider"><span class="switch-on"><i class="ri-check-line"></i></span><span class="switch-off"><i class="ri-close-line"></i></span></span>
                </label></td>
                <td>@foreach(app(\App\Services\LocaleManager::class)->supported() as $localeCode => $localeLabel)<div class="small" title="{{ $localeLabel }}">{{ strtoupper($localeCode) }}: {{ $post->hasTranslation($localeCode) ? __('admin.ui.co_noi_dung') : __('admin.ui.chua_nhap') }}</div>@endforeach</td>
                <td>@foreach($post->taxonomies->where('taxonomy', 'category') as $category)<span class="badge bg-label-success">{{ $category->term?->name }}</span> @endforeach</td>
                <td>{{ $post->status === 'published' && $post->published_at ? \Carbon\Carbon::parse($post->published_at)->format('d/m/Y H:i') : __('admin.ui.chua_dang') }}</td>
                <td>
                    @unless($post->deleted_at)<a class="btn btn-sm btn-outline-primary" href="{{ \Modules\CmsBackend\Support\ModuleUrl::route($resource.'.edit', $post->id) }}">{{ __('admin.ui.xem_xu_ly') }}</a>@endunless
                    @if(isset($purgeAllowed[$post->id]))
                    @include('cmsbackend::screens.components.editorial.purge-form', ['purgeRevision' => (int) ($revisions[$post->id] ?? 0)])
                    @endif
                </td>
            </tr>@empty<tr><td colspan="8" class="text-center">{{ __('admin.ui.chua_co_noi_dung_trong_pham_vi_cua_ban') }}</td></tr>@endforelse</tbody>
        </table></div>
        <div class="cms-list-bottom">{{ $posts->onEachSide(2)->links('cmsbackend::screens.components.shared.layouts.paginate') }}</div>
        @if($type === 'post')
        <dialog id="cms-export-dialog" class="p-4 rounded border" style="width:min(960px,90vw)"><h2 class="h5">{{ __('admin.ui.danh_sach_xuat') }}</h2><p id="cms-export-status" role="status"></p><div class="table-responsive"><table class="table"><thead><tr><th>STT</th><th>{{ __('admin.ui.tieu_de') }}</th><th>{{ __('admin.ui.chuyen_muc') }}</th><th>{{ __('admin.ui.tac_gia') }}</th><th>{{ __('admin.ui.nguoi_dang') }}</th><th>{{ __('admin.ui.ngay_dang') }}</th></tr></thead><tbody id="cms-export-rows"></tbody></table></div><button id="cms-export-close" type="button" class="btn btn-outline-secondary">{{ __('admin.ui.dong') }}</button></dialog>
        @endif
</div>
@endsection
