{{-- Cài đặt trang chủ động: field sinh từ settingsSchema() của các cụm ĐANG BẬT. Bố cục (bật/tắt, zone, variant, thứ tự) chỉ Developer đổi. --}}
<div class="cms-setting-heading">
    <h6>{{ __('homepage.settings.title') }}</h6>
    <p>{{ __('homepage.settings.intro') }}</p>
</div>
@forelse ($dynamicForms as $form)
    @php
        $instance = $form['instance'];
        $definition = $form['definition'];
        $hasInputs = collect($form['fields'])->contains(fn ($field) => ($field['type'] ?? '') !== 'management_link');
    @endphp
    <fieldset class="border rounded p-3 mb-4" data-homepage-block="{{ $instance }}">
        <legend class="float-none w-auto px-2 fs-6 mb-0">
            <i class="{{ $definition->icon() }} me-1 text-primary" aria-hidden="true"></i>{{ __($definition->nameKey()) }} <code class="ms-1 small">{{ $instance }}</code>
        </legend>
        <p class="small text-muted mb-3">{{ __($definition->descriptionKey()) }}</p>
        @foreach ($form['fields'] as $field => $schema)
            @php
                $type = $schema['type'] ?? 'text';
                $name = 'homepage_blocks['.$instance.']['.$field.']';
                $id = 'hb-'.$instance.'-'.$field;
                $errorKey = 'homepage_blocks.'.$instance.'.'.$field;
                $value = old($errorKey, $form['values'][$field] ?? null);
            @endphp
            @if ($type === 'management_link')
                @if (\Illuminate\Support\Facades\Route::has($schema['route'] ?? ''))
                    <div class="mb-3">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route($schema['route']) }}"><i class="ri-external-link-line me-1" aria-hidden="true"></i>{{ __($schema['label'] ?? 'homepage.settings.manage') }}</a>
                    </div>
                @endif
                @continue
            @endif
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-bold" for="{{ $id }}">{{ __($schema['label'] ?? $field) }}</label>
                <div class="col-sm-9">
                    @if ($type === 'category')
                        <select name="{{ $name }}" id="{{ $id }}" class="form-select">
                            <option value="">{{ __('homepage.settings.choose') }}</option>
                            @foreach ($categories as $cate)
                                <option value="{{ $cate->term_taxonomy_id }}" @selected((string) $value === (string) $cate->term_taxonomy_id)>{{ $cate->term?->name }}</option>
                            @endforeach
                        </select>
                    @elseif ($type === 'categories')
                        @php $selectedIds = array_values(array_map('intval', array_filter((array) $value, 'is_numeric'))); $byId = $categories->keyBy('term_taxonomy_id'); @endphp
                        <div data-hb-ordered data-name="{{ $name }}[]">
                            <ol class="list-group list-group-numbered mb-2" data-hb-list>
                                @foreach ($selectedIds as $categoryId)
                                    @if ($byId->has($categoryId))
                                        <li class="list-group-item d-flex align-items-center gap-2" data-hb-item>
                                            <input type="hidden" name="{{ $name }}[]" value="{{ $categoryId }}">
                                            <span class="me-auto">{{ $byId[$categoryId]->term?->name }}</span>
                                            <button type="button" class="btn btn-sm btn-icon" data-hb-up aria-label="{{ __('homepage.ui.move_up') }}"><i class="ri-arrow-up-s-line"></i></button>
                                            <button type="button" class="btn btn-sm btn-icon" data-hb-down aria-label="{{ __('homepage.ui.move_down') }}"><i class="ri-arrow-down-s-line"></i></button>
                                            <button type="button" class="btn btn-sm btn-icon text-danger" data-hb-remove aria-label="{{ __('admin.ui.xoa') }}"><i class="ri-close-line"></i></button>
                                        </li>
                                    @endif
                                @endforeach
                            </ol>
                            <select class="form-select" id="{{ $id }}" data-hb-add>
                                <option value="">{{ __('homepage.settings.choose') }}</option>
                                @foreach ($categories as $cate)<option value="{{ $cate->term_taxonomy_id }}" data-label="{{ $cate->term?->name }}">{{ $cate->term?->name }}</option>@endforeach
                            </select>
                            <div class="form-text">{{ __('homepage.settings.ordered_hint') }}</div>
                        </div>
                    @elseif ($type === 'number')
                        <input type="number" name="{{ $name }}" id="{{ $id }}" class="form-control" value="{{ $value }}" @if (isset($schema['min'])) min="{{ $schema['min'] }}" @endif @if (isset($schema['max'])) max="{{ $schema['max'] }}" @endif>
                    @elseif ($type === 'textarea')
                        <textarea name="{{ $name }}" id="{{ $id }}" class="form-control" rows="4" maxlength="2000">{{ $value }}</textarea>
                    @elseif ($type === 'boolean')
                        <input type="hidden" name="{{ $name }}" value="0">
                        <div class="form-check form-switch pt-2"><input class="form-check-input" type="checkbox" role="switch" name="{{ $name }}" id="{{ $id }}" value="1" @checked(filter_var($value, FILTER_VALIDATE_BOOLEAN))></div>
                    @elseif ($type === 'select')
                        <select name="{{ $name }}" id="{{ $id }}" class="form-select">
                            @foreach ($schema['options_resolved'] as $optionValue => $optionLabel)
                                <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="{{ ($schema['format'] ?? '') === 'url' ? 'url' : 'text' }}" name="{{ $name }}" id="{{ $id }}" class="form-control" maxlength="255" autocomplete="off" value="{{ $value }}">
                    @endif
                    @if (! empty($schema['help']))<div class="form-text">{{ __($schema['help']) }}</div>@endif
                    @if (! empty($form['missing'][$field]))<div class="text-danger small mt-1">{{ __('homepage.settings.missing', ['ids' => implode(', ', $form['missing'][$field])]) }}</div>@endif
                    <x-input-error :messages="$errors->get($errorKey)" class="mt-2" />
                </div>
            </div>
        @endforeach
        @unless ($hasInputs)<p class="small text-muted mb-0">{{ __('homepage.settings.managed_by') }}</p>@endunless
    </fieldset>
@empty
    <div class="alert alert-secondary">{{ __('homepage.settings.none') }}</div>
@endforelse

<script>
    (function () {
        document.querySelectorAll('[data-hb-ordered]').forEach(function (box) {
            var list = box.querySelector('[data-hb-list]');
            var name = box.getAttribute('data-name');
            list.addEventListener('click', function (event) {
                var item = event.target.closest('[data-hb-item]');
                if (!item) { return; }
                if (event.target.closest('[data-hb-remove]')) { item.remove(); }
                else if (event.target.closest('[data-hb-up]') && item.previousElementSibling) { list.insertBefore(item, item.previousElementSibling); }
                else if (event.target.closest('[data-hb-down]') && item.nextElementSibling) { list.insertBefore(item.nextElementSibling, item); }
            });
            box.querySelector('[data-hb-add]').addEventListener('change', function (event) {
                var select = event.target, option = select.options[select.selectedIndex];
                if (!select.value || list.querySelector('input[value="' + select.value + '"]')) { select.value = ''; return; }
                var item = document.createElement('li');
                item.className = 'list-group-item d-flex align-items-center gap-2';
                item.setAttribute('data-hb-item', '');
                var input = document.createElement('input'); input.type = 'hidden'; input.name = name; input.value = select.value;
                var label = document.createElement('span'); label.className = 'me-auto'; label.textContent = option.getAttribute('data-label') || option.textContent;
                item.appendChild(input); item.appendChild(label);
                [['up', 'ri-arrow-up-s-line', ''], ['down', 'ri-arrow-down-s-line', ''], ['remove', 'ri-close-line', ' text-danger']].forEach(function (spec) {
                    var button = document.createElement('button'); button.type = 'button'; button.className = 'btn btn-sm btn-icon' + spec[2];
                    button.setAttribute('data-hb-' + spec[0], ''); var icon = document.createElement('i'); icon.className = spec[1]; button.appendChild(icon); item.appendChild(button);
                });
                list.appendChild(item); select.value = '';
            });
        });
    })();
</script>
