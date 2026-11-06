{{--
    Other-language label (and, for plain links, address) of a menu item, shown inside every menu-item modal.
    The item stays one record with one position in the tree; these values are posted inside the same `structure` JSON as
    title_{locale} / url_{locale} and saved to menu_item_translations. Links to a post, page, category... never get a hand-typed
    address: it is resolved from the linked object for the language shown.
    Expects: $withUrl (bool, true only for the "Liên kết" modal).
--}}
@foreach (app(\App\Services\LocaleManager::class)->alternates() as $menuLocale)
    @php $menuLocaleLabel = app(\App\Services\LocaleManager::class)->supported()[$menuLocale]; @endphp
    <hr class="my-4">
    <p class="fw-semibold mb-3">{{ $menuLocaleLabel }}</p>
    <div class="row">
        <div class="col mb-4">
            <div class="form-floating form-floating-outline">
                <input type="text" class="form-control" placeholder="Display name" name="title_{{ $menuLocale }}" maxlength="255" autocomplete="off">
                <label>Display name ({{ $menuLocaleLabel }})</label>
            </div>
            <small class="text-muted">Để trống thì mục này không hiện trong menu {{ $menuLocaleLabel }}; hệ thống không tự dịch.</small>
        </div>
    </div>
    @if ($withUrl ?? false)
        <div class="row">
            <div class="col mb-2">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" placeholder="https://xxx.xxx hoặc /example" name="url_{{ $menuLocale }}" maxlength="255" autocomplete="off">
                    <label>Custom URL ({{ $menuLocaleLabel }})</label>
                </div>
            </div>
        </div>
    @else
        <input type="hidden" name="url_{{ $menuLocale }}" value="">
    @endif
@endforeach
