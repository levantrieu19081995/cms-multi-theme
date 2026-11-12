@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', $publication ? __('admin.social.edit_title') : __('admin.social.create_title'))

@php
    $routeBase = 'cms.dashboard.truyen-thong.';
    $localeManager = app(\App\Services\LocaleManager::class);
@endphp

@section('content')
    {{ $publication ? Breadcrumbs::render('cms.dashboard.truyen-thong.publications.edit') : Breadcrumbs::render('cms.dashboard.truyen-thong.create') }}
    @include('cmsbackend::screens.pages.truyen-thong.partials.project-badge')

    <style>
        .cms-social-thumb { width: 72px; height: 48px; object-fit: cover; border-radius: 4px; background: #eee; }
        .cms-social-preview { max-width: 520px; border: 1px solid var(--bs-border-color, #dee2e6); border-radius: 8px; overflow: hidden; background: var(--bs-card-bg, #fff); }
        .cms-social-preview__image { aspect-ratio: 1.91 / 1; width: 100%; object-fit: cover; background: #e9ecef; display: flex; align-items: center; justify-content: center; color: #6c757d; }
        .cms-social-preview__message { display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden; white-space: pre-line; }
    </style>

    @if ($step === 'select')
        <div class="card">
            <h5 class="card-header bg-primary text-white text-uppercase">{{ __('admin.social.create_title') }}</h5>
            <div class="card-body mt-4">
                @include('cmsbackend::screens.pages.truyen-thong.partials.steps', ['current' => 1])

                <div class="alert alert-info" role="note">{{ __('admin.social.select_post_note') }}</div>

                {{-- Writing a post here instead of choosing one: opens the drawer on the right. --}}
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <span class="text-muted">{{ __('admin.social.direct_hint') }}</span>
                    <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#social-direct-drawer" aria-controls="social-direct-drawer">
                        <i class="ri-edit-box-line me-1" aria-hidden="true"></i>{{ __('admin.social.direct_button') }}
                    </button>
                </div>

                <form method="get" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'create') }}" class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <div class="input-group input-group-merge bg-white">
                            <span class="input-group-text"><i class="ri-search-line" aria-hidden="true"></i></span>
                            <input type="search" name="search" class="form-control" value="{{ $search }}" placeholder="{{ __('admin.social.search_post') }}" aria-label="{{ __('admin.social.search_post') }}">
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <select name="category" class="form-select" aria-label="{{ __('admin.social.filter_category') }}">
                            <option value="">{{ __('admin.social.all_categories') }}</option>
                            @foreach ($categories as $taxonomy)
                                <option value="{{ $taxonomy->term_taxonomy_id }}" @selected((int) $category === (int) $taxonomy->term_taxonomy_id)>{{ $taxonomy->term->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-grid">
                        <button type="submit" class="btn btn-outline-secondary bg-white"><i class="ri-filter-3-line me-1" aria-hidden="true"></i>{{ __('admin.ui.search') }}</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead>
                            <tr class="text-nowrap">
                                <th style="width: 1%">{{ __('admin.social.col_image') }}</th>
                                <th>{{ __('admin.social.col_post') }}</th>
                                <th>{{ __('admin.social.col_category') }}</th>
                                <th>{{ __('admin.social.col_published_at') }}</th>
                                <th>{{ __('admin.social.col_languages') }}</th>
                                <th style="width: 1%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($posts as $item)
                                @php
                                    $thumb = $item->getFirstMediaUrl('avatars');
                                    $categoryNames = $item->taxonomies->where('taxonomy', 'category')->map(fn ($taxonomy) => $taxonomy->term?->name)->filter()->implode(', ');
                                @endphp
                                <tr>
                                    <td>@if ($thumb)<img class="cms-social-thumb" src="{{ $thumb }}" alt="" loading="lazy">@else<span class="cms-social-thumb d-inline-block"></span>@endif</td>
                                    <td class="fw-bold">{{ $item->title }}</td>
                                    <td>{{ $categoryNames }}</td>
                                    <td class="text-nowrap">{{ $item->published_at ? \Illuminate\Support\Carbon::parse($item->published_at)->format('d/m/Y H:i') : '' }}</td>
                                    <td class="text-nowrap">
                                        @foreach ($localeManager->supported() as $code => $name)
                                            @php $has = $localeManager->isDefault($code) || $item->hasTranslation($code); @endphp
                                            <span class="badge {{ $has ? 'bg-label-success' : 'bg-label-secondary' }}" title="{{ $name }}: {{ $has ? __('admin.social.lang_ready') : __('admin.social.lang_missing') }}">{{ strtoupper($code) }}{{ $has ? '' : ' ×' }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        <a class="btn btn-sm btn-primary text-nowrap" href="{{ \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'create', ['post_id' => $item->id]) }}">{{ __('admin.social.choose_post') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">{{ __('admin.social.no_eligible_post') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="form-layout-footer mt-4">
                    {{ $posts->onEachSide(2)->links('cmsbackend::screens.components.shared.layouts.paginate') }}
                </div>
            </div>
        </div>

        @php
            $directSelected = (array) old('channels', []);
            $directOpen = old('direct') === '1';
        @endphp
        <div class="offcanvas offcanvas-end" tabindex="-1" id="social-direct-drawer" aria-labelledby="social-direct-title" style="--bs-offcanvas-width: 540px; width: min(540px, 100vw)">
            <form method="post" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'store-direct') }}" id="social-direct-form" class="d-flex flex-column h-100" novalidate>
                @csrf
                <input type="hidden" name="direct" value="1">
                <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

                <div class="offcanvas-header border-bottom">
                    <h5 class="offcanvas-title" id="social-direct-title">{{ __('admin.social.direct_title') }}</h5>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="{{ __('admin.social.cancel') }}"></button>
                </div>

                <div class="offcanvas-body">
                    @if ($directOpen && $errors->any())
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
                        </div>
                    @endif
                    <p class="text-muted small">{{ __('admin.social.direct_note') }}</p>

                    <div class="mb-3">
                        <label for="social-direct-post-title" class="form-label">{{ __('admin.social.field_title') }} <span class="text-danger">*</span></label>
                        <input type="text" id="social-direct-post-title" name="title" class="form-control" maxlength="255" required value="{{ $directOpen ? old('title') : '' }}">
                    </div>

                    <div class="mb-3">
                        <label for="social-direct-message" class="form-label">{{ __('admin.social.field_message') }}</label>
                        <textarea id="social-direct-message" name="message" rows="7" maxlength="2000" class="form-control">{{ $directOpen ? old('message') : '' }}</textarea>
                        <div class="form-text">{{ __('admin.social.message_plain_text') }} <span id="social-direct-count">0</span>/2000</div>
                    </div>

                    <div class="mb-3">
                        <span class="form-label d-block">{{ __('admin.social.field_media') }}</span>
                        @include('cmsbackend::screens.pages.truyen-thong.partials.media-picker', [
                            'pickerId' => 'social-direct-media',
                            'items' => $directOpen ? old('media', []) : [],
                        ])
                    </div>

                    <div class="mb-3">
                        <label for="social-direct-url" class="form-label">{{ __('admin.social.field_url') }}</label>
                        <input type="text" id="social-direct-url" name="public_url" class="form-control" maxlength="2000" placeholder="https://" value="{{ $directOpen ? old('public_url') : '' }}">
                        <div class="form-text">{{ __('admin.social.direct_url_note') }}</div>
                    </div>

                    <fieldset class="mb-2">
                        <legend class="form-label fs-6">{{ __('admin.social.field_channels') }}</legend>
                        @foreach (\Modules\CmsBackend\Enums\Social\SocialChannel::cases() as $channel)
                            @php $connected = $availability[$channel->value] ?? false; @endphp
                            <div class="form-check d-flex align-items-center gap-2 mb-2">
                                <input class="form-check-input mt-0" type="checkbox" name="channels[]" id="social-direct-channel-{{ $channel->value }}"
                                    value="{{ $channel->value }}" @checked($directOpen && in_array($channel->value, $directSelected, true))>
                                <label class="form-check-label d-flex align-items-center gap-2" for="social-direct-channel-{{ $channel->value }}">
                                    <i class="{{ $channel->icon() }}" aria-hidden="true"></i>{{ $channel->label() }}
                                    <span class="badge {{ $connected ? 'bg-label-success' : 'bg-label-warning' }}">{{ $connected ? __('admin.social.account_status_connected') : __('admin.social.not_connected') }}</span>
                                </label>
                            </div>
                        @endforeach
                        <div class="form-text">{{ __('admin.social.channels_phase1_note') }}</div>
                    </fieldset>
                </div>

                <div class="offcanvas-footer border-top p-3 d-flex flex-wrap justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">{{ __('admin.social.cancel') }}</button>
                    <button type="submit" name="intent" value="draft" class="btn btn-outline-primary">{{ __('admin.social.save_draft') }}</button>
                    <button type="submit" name="intent" value="ready" class="btn btn-primary">{{ __('admin.social.save_ready') }}</button>
                </div>
            </form>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var form = document.getElementById('social-direct-form'), drawer = document.getElementById('social-direct-drawer');
                if (!form || !drawer) { return; }
                // Under <body>, so no ancestor of the page content can clip or re-position the fixed drawer.
                document.body.appendChild(drawer);
                var message = document.getElementById('social-direct-message'), counter = document.getElementById('social-direct-count');
                function count() { counter.textContent = message.value.length; }
                message.addEventListener('input', count);
                count();

                // One click, one request: a second submit of the same form is ignored (the server also de-duplicates by key).
                form.addEventListener('submit', function (event) {
                    if (form.dataset.submitted === '1') { event.preventDefault(); return; }
                    form.dataset.submitted = '1';
                    [].forEach.call(form.querySelectorAll('button[type="submit"]'), function (button) {
                        button.classList.add('disabled'); button.setAttribute('aria-disabled', 'true');
                    });
                });

                // After a refused save the page reloads: open the drawer again with what was typed.
                @if ($directOpen)
                    if (window.bootstrap) { window.bootstrap.Offcanvas.getOrCreateInstance(drawer).show(); }
                @endif
            });
        </script>
    @else
        @php
            $selected = (array) old('channels', $publication ? $publication->channels->map(fn ($delivery) => $delivery->channel->value)->all() : []);
            $titleValue = old('title', $snapshot['title']);
            $messageValue = old('message', $snapshot['message']);
            // A publication written directly has no source post: its address is typed (optional) instead of fixed.
            $isDirect = $publication && $publication->post_id === null;
            $action = $publication
                ? \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'publications.update', $publication->id)
                : \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'store');
        @endphp

        <form method="post" action="{{ $action }}" id="cms-social-form" novalidate
            data-label-none="{{ __('admin.social.preview_no_channel') }}"
            data-label-no-image="{{ __('admin.social.preview_no_image') }}"
            data-label-not-connected="{{ __('admin.social.not_connected') }}">
            @csrf
            @unless ($publication)
                <input type="hidden" name="post_id" value="{{ $post->id }}">
                <input type="hidden" name="locale" value="{{ $locale }}">
                <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
            @endunless

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="card mb-4">
                <h5 class="card-header bg-primary text-white text-uppercase">{{ $publication ? __('admin.social.edit_title') : __('admin.social.create_title') }}</h5>
                <div class="card-body mt-4">
                    @include('cmsbackend::screens.pages.truyen-thong.partials.steps', ['current' => 2])

                    <div class="row g-4">
                        <div class="col-12 col-lg-7">
                            <h6 class="text-uppercase text-muted">{{ __('admin.social.step_content') }}</h6>

                            <div class="mb-3">
                                <label class="form-label">{{ __('admin.social.field_source_post') }}</label>
                                <div class="form-control-plaintext fw-medium">
                                    {{ $isDirect ? __('admin.social.source_direct') : ($post?->title ?? __('admin.social.source_post_deleted')) }}
                                </div>
                                @unless ($isDirect)
                                    <div class="form-text">{{ __('admin.social.source_not_modified') }}</div>
                                @endunless
                            </div>

                            <div class="mb-3">
                                <span class="form-label d-block">{{ __('admin.social.field_locale') }}</span>
                                @if ($publication)
                                    <span class="badge bg-label-primary">{{ strtoupper($locale) }}</span>
                                    <span class="form-text d-inline ms-2">{{ __('admin.social.locale_fixed') }}</span>
                                @else
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($localeManager->supported() as $code => $name)
                                            @if (in_array($code, $locales, true))
                                                <a class="btn btn-sm {{ $code === $locale ? 'btn-primary' : 'btn-outline-secondary' }}"
                                                    href="{{ \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'create', ['post_id' => $post->id, 'locale' => $code]) }}"
                                                    @if ($code === $locale) aria-current="true" @endif>{{ $name }}</a>
                                            @else
                                                <span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true" title="{{ __('admin.social.lang_missing') }}">{{ $name }}</span>
                                            @endif
                                        @endforeach
                                    </div>
                                    <div class="form-text">{{ __('admin.social.locale_reload_note') }}</div>
                                @endif
                            </div>

                            <div class="mb-3">
                                <label for="social-title" class="form-label">{{ __('admin.social.field_title') }} <span class="text-danger">*</span></label>
                                <input type="text" id="social-title" name="title" class="form-control @error('title') is-invalid @enderror" maxlength="255" required value="{{ $titleValue }}">
                            </div>

                            <div class="mb-3">
                                <label for="social-message" class="form-label">{{ __('admin.social.field_message') }}</label>
                                <textarea id="social-message" name="message" rows="6" maxlength="2000" class="form-control @error('message') is-invalid @enderror">{{ $messageValue }}</textarea>
                                <div class="form-text">{{ __('admin.social.message_plain_text') }} <span id="social-message-count">0</span>/2000</div>
                            </div>

                            <div class="mb-3">
                                <span class="form-label d-block">{{ __('admin.social.field_media') }}</span>
                                @include('cmsbackend::screens.pages.truyen-thong.partials.media-picker', [
                                    'pickerId' => 'social-media',
                                    'items' => old('media') !== null ? old('media') : ($snapshot['media'] ?? array_filter([$snapshot['image_url'] ?? null])),
                                ])
                                @error('media')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="social-url" class="form-label">{{ __('admin.social.field_url') }}</label>
                                @if ($isDirect)
                                    <input type="text" id="social-url" name="public_url" class="form-control @error('public_url') is-invalid @enderror" maxlength="2000" placeholder="https://" value="{{ old('public_url', $snapshot['public_url']) }}">
                                    <div class="form-text">{{ __('admin.social.direct_url_note') }}</div>
                                @else
                                    <input type="text" id="social-url" class="form-control" value="{{ $snapshot['public_url'] }}" readonly>
                                @endif
                            </div>
                        </div>

                        <div class="col-12 col-lg-5">
                            <h6 class="text-uppercase text-muted">{{ __('admin.social.step_channels') }}</h6>
                            <fieldset class="mb-4">
                                <legend class="visually-hidden">{{ __('admin.social.field_channels') }}</legend>
                                @foreach (\Modules\CmsBackend\Enums\Social\SocialChannel::cases() as $channel)
                                    @php $connected = $availability[$channel->value] ?? false; @endphp
                                    <div class="form-check d-flex align-items-center gap-2 mb-2">
                                        <input class="form-check-input mt-0" type="checkbox" name="channels[]" id="social-channel-{{ $channel->value }}"
                                            value="{{ $channel->value }}" data-label="{{ $channel->label() }}" data-connected="{{ $connected ? '1' : '0' }}"
                                            @checked(in_array($channel->value, $selected, true))>
                                        <label class="form-check-label d-flex align-items-center gap-2" for="social-channel-{{ $channel->value }}">
                                            <i class="{{ $channel->icon() }}" aria-hidden="true"></i>{{ $channel->label() }}
                                            @if ($connected)
                                                <span class="badge bg-label-success">{{ __('admin.social.account_status_connected') }}</span>
                                            @else
                                                <span class="badge bg-label-warning">{{ __('admin.social.not_connected') }}</span>
                                            @endif
                                        </label>
                                    </div>
                                @endforeach
                                <div class="form-text">{{ __('admin.social.channels_phase1_note') }}</div>
                            </fieldset>

                            <h6 class="text-uppercase text-muted">{{ __('admin.social.step_preview') }}</h6>
                            <div class="cms-social-preview mb-2" id="social-preview" aria-live="polite">
                                <div class="cms-social-preview__image" id="social-preview-image"></div>
                                <div class="p-3">
                                    <div class="small text-muted text-uppercase" id="social-preview-host"></div>
                                    <div class="fw-bold mb-1" id="social-preview-title"></div>
                                    <div class="small cms-social-preview__message" id="social-preview-message"></div>
                                </div>
                            </div>
                            <dl class="row small mb-0">
                                <dt class="col-4">{{ __('admin.social.field_url') }}</dt>
                                <dd class="col-8 text-break">{{ $snapshot['public_url'] }}</dd>
                                <dt class="col-4">{{ __('admin.social.field_project') }}</dt>
                                <dd class="col-8">{{ \Modules\CmsBackend\Support\ProjectContext::label($projectKey) }}</dd>
                                <dt class="col-4">{{ __('admin.social.field_locale') }}</dt>
                                <dd class="col-8">{{ strtoupper($locale) }}</dd>
                                <dt class="col-4">{{ __('admin.social.field_channels') }}</dt>
                                <dd class="col-8" id="social-preview-channels"></dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex flex-wrap justify-content-between gap-2">
                    <a href="{{ $publication ? \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'publications.show', $publication->id) : \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'create') }}" class="btn btn-outline-secondary">
                        <i class="ri-arrow-left-line me-1" aria-hidden="true"></i>{{ $publication ? __('admin.social.cancel') : __('admin.social.back_to_posts') }}
                    </a>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" name="intent" value="draft" class="btn btn-outline-primary">{{ __('admin.social.save_draft') }}</button>
                        <button type="submit" name="intent" value="ready" class="btn btn-primary">{{ __('admin.social.save_ready') }}</button>
                    </div>
                </div>
            </div>
        </form>

        <script>
            (function () {
                var form = document.getElementById('cms-social-form');
                if (!form) { return; }
                var title = document.getElementById('social-title'), message = document.getElementById('social-message'),
                    url = document.getElementById('social-url');
                var boxImage = document.getElementById('social-preview-image'), boxHost = document.getElementById('social-preview-host'),
                    boxTitle = document.getElementById('social-preview-title'), boxMessage = document.getElementById('social-preview-message'),
                    boxChannels = document.getElementById('social-preview-channels'), counter = document.getElementById('social-message-count');

                function render() {
                    boxTitle.textContent = title.value;
                    boxMessage.textContent = message.value;
                    counter.textContent = message.value.length;
                    try { boxHost.textContent = new URL(url.value).host; } catch (e) { boxHost.textContent = ''; }

                    boxImage.textContent = '';
                    // The preview shows the first picture; a video has no still here, so only its name is shown.
                    var chosen = [].slice.call(form.querySelectorAll('input[data-media-input]')).map(function (input) { return input.value; });
                    var src = chosen.length ? chosen[0] : '';
                    if (/\.(mp4|mpeg|mpg|mov|webm)(\?|#|$)/i.test(src)) {
                        boxImage.textContent = decodeURIComponent(src.split('?')[0].split('/').pop());
                    } else if (/^(https?:\/\/|\/(?!\/))/i.test(src)) {
                        var img = document.createElement('img');
                        img.alt = ''; img.src = src; img.style.cssText = 'width:100%;height:100%;object-fit:cover';
                        img.onerror = function () { boxImage.textContent = form.dataset.labelNoImage; };
                        boxImage.appendChild(img);
                    } else {
                        boxImage.textContent = form.dataset.labelNoImage;
                    }

                    var picked = [].slice.call(form.querySelectorAll('input[name="channels[]"]:checked')).map(function (box) {
                        return box.dataset.label + (box.dataset.connected === '1' ? '' : ' (' + form.dataset.labelNotConnected + ')');
                    });
                    boxChannels.textContent = picked.length ? picked.join(', ') : form.dataset.labelNone;
                }

                form.addEventListener('input', render);
                form.addEventListener('change', render);
                form.addEventListener('social-media-change', render);
                render();

                // One click, one request: a second submit of the same form is ignored (the server also de-duplicates by key).
                form.addEventListener('submit', function (event) {
                    if (form.dataset.submitted === '1') { event.preventDefault(); return; }
                    form.dataset.submitted = '1';
                    [].forEach.call(form.querySelectorAll('button[type="submit"]'), function (button) {
                        button.classList.add('disabled'); button.setAttribute('aria-disabled', 'true');
                    });
                });
            })();
        </script>
    @endif
@endsection
