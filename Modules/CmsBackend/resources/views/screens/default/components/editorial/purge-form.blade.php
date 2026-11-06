<details class="mt-2">
    <summary class="text-danger">{{ __('admin.ui.xoa_vinh_vien_kem_tap_tin') }}</summary>
    <form method="POST" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route($resource.'.purge', $post->id) }}" class="d-flex flex-column gap-2 p-2">
        @csrf
        <input type="hidden" name="revision" value="{{ $purgeRevision }}">
        @if(request()->boolean('trash'))<input type="hidden" name="trash" value="1">@endif
        <p class="small mb-0">{{ __('admin.ui.khong_the_khoi_phuc_bai_va_tap_tin_rieng_sau_khi') }}</p>
        <label class="d-flex gap-2"><input type="checkbox" name="confirmation" value="1" required> {{ __('admin.ui.confirm_purge', ['title' => $post->title]) }}</label>
        <button type="submit" class="btn btn-sm btn-danger align-self-start">{{ __('admin.ui.xoa_vinh_vien') }}</button>
    </form>
</details>
