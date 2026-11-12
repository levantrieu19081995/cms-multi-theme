{{--
    Pictures and video of a publication, chosen in the media library (the same laravel-filemanager popup as the picture of a post).
    Props: $pickerId (unique on the page), $items (list of addresses already chosen).
    Sends media[]: one hidden input per chosen file, in order, plus one empty entry so that "nothing chosen" clears the list.
    The kind is read from the extension. A post has up to 10 pictures or one video: choosing a video replaces the pictures and
    choosing pictures removes the video (the server checks the same rules). Fires "social-media-change" (bubbles) after each change.
--}}
@php
    $pickerItems = array_values(array_filter((array) ($items ?? []), fn ($url) => is_string($url) && $url !== ''));
@endphp
<div class="cms-media-picker" id="{{ $pickerId }}" data-items="{{ json_encode($pickerItems) }}"
    data-max="{{ \Modules\CmsBackend\Services\Social\SocialMedia::MAX_IMAGES }}"
    data-label-remove="{{ __('admin.social.media_remove') }}" data-label-left="{{ __('admin.social.media_move_left') }}"
    data-label-right="{{ __('admin.social.media_move_right') }}" data-label-cover="{{ __('admin.social.media_cover') }}"
    data-label-video="{{ __('admin.social.media_video') }}">
    <input type="hidden" name="media[]" value="">
    <div class="d-flex flex-wrap gap-2 mb-2">
        <button type="button" class="btn btn-outline-primary btn-sm" data-media-pick="image">
            <i class="ri-image-add-line me-1" aria-hidden="true"></i>{{ __('admin.social.media_pick_images') }}
        </button>
        <button type="button" class="btn btn-outline-primary btn-sm" data-media-pick="video">
            <i class="ri-movie-line me-1" aria-hidden="true"></i>{{ __('admin.social.media_pick_video') }}
        </button>
    </div>
    <div class="cms-media-picker__list" data-media-list></div>
    <div class="form-text">{{ __('admin.social.media_note', ['max' => \Modules\CmsBackend\Services\Social\SocialMedia::MAX_IMAGES]) }}</div>
</div>

@once
    <style>
        .cms-media-picker__list { display: flex; flex-wrap: wrap; gap: .5rem; }
        .cms-media-picker__list:empty { display: none; }
        .cms-media-picker__item { position: relative; width: 104px; height: 78px; border: 1px solid var(--bs-border-color, #dee2e6); border-radius: 6px; overflow: hidden; background: #f1f3f5; }
        .cms-media-picker__item img, .cms-media-picker__item video { width: 100%; height: 100%; object-fit: cover; display: block; }
        .cms-media-picker__item .badge { position: absolute; left: 4px; top: 4px; }
        .cms-media-picker__item button { position: absolute; border: 0; border-radius: 4px; padding: 0 4px; line-height: 1.3; background: rgba(0, 0, 0, .6); color: #fff; font-size: 14px; cursor: pointer; }
        .cms-media-picker__item button:hover, .cms-media-picker__item button:focus-visible { background: rgba(0, 0, 0, .85); }
        .cms-media-picker__item [data-media-remove] { right: 4px; top: 4px; }
        .cms-media-picker__item [data-media-left] { left: 4px; bottom: 4px; }
        .cms-media-picker__item [data-media-right] { right: 4px; bottom: 4px; }
    </style>
    <script>
        (function () {
            var VIDEO = /\.(mp4|mpeg|mpg|mov|webm)(\?|#|$)/i, IMAGE = /\.(jpe?g|png|gif|bmp)(\?|#|$)/i;

            function init(root) {
                var list = root.querySelector('[data-media-list]'), items = [], max = parseInt(root.dataset.max, 10) || 10;
                try { items = JSON.parse(root.dataset.items || '[]'); } catch (e) { items = []; }

                function button(icon, label, attribute) {
                    var el = document.createElement('button');
                    el.type = 'button'; el.setAttribute(attribute, ''); el.title = label; el.setAttribute('aria-label', label);
                    var i = document.createElement('i'); i.className = icon; i.setAttribute('aria-hidden', 'true'); el.appendChild(i);
                    return el;
                }

                function render() {
                    [].forEach.call(root.querySelectorAll('input[data-media-input]'), function (node) { node.remove(); });
                    list.textContent = '';
                    items.forEach(function (url, index) {
                        var input = document.createElement('input');
                        input.type = 'hidden'; input.name = 'media[]'; input.value = url; input.setAttribute('data-media-input', '');
                        root.appendChild(input);

                        var cell = document.createElement('div');
                        cell.className = 'cms-media-picker__item';
                        if (VIDEO.test(url)) {
                            var video = document.createElement('video');
                            video.src = url; video.muted = true; video.preload = 'metadata'; video.setAttribute('playsinline', '');
                            cell.appendChild(video);
                            var tag = document.createElement('span');
                            tag.className = 'badge bg-dark'; tag.textContent = root.dataset.labelVideo;
                            cell.appendChild(tag);
                        } else {
                            var img = document.createElement('img');
                            img.alt = ''; img.loading = 'lazy'; img.src = url;
                            cell.appendChild(img);
                            if (index === 0) {
                                var cover = document.createElement('span');
                                cover.className = 'badge bg-primary'; cover.textContent = root.dataset.labelCover;
                                cell.appendChild(cover);
                            }
                        }
                        var remove = button('ri-close-line', root.dataset.labelRemove, 'data-media-remove');
                        remove.addEventListener('click', function () { items.splice(index, 1); render(); });
                        cell.appendChild(remove);
                        if (items.length > 1 && index > 0) {
                            var left = button('ri-arrow-left-s-line', root.dataset.labelLeft, 'data-media-left');
                            left.addEventListener('click', function () { items.splice(index - 1, 0, items.splice(index, 1)[0]); render(); });
                            cell.appendChild(left);
                        }
                        if (items.length > 1 && index < items.length - 1) {
                            var right = button('ri-arrow-right-s-line', root.dataset.labelRight, 'data-media-right');
                            right.addEventListener('click', function () { items.splice(index + 1, 0, items.splice(index, 1)[0]); render(); });
                            cell.appendChild(right);
                        }
                        list.appendChild(cell);
                    });
                    root.dispatchEvent(new CustomEvent('social-media-change', { bubbles: true, detail: { items: items.slice() } }));
                }

                function add(urls, kind) {
                    var wanted = kind === 'video' ? VIDEO : IMAGE;
                    urls = urls.filter(function (url) { return typeof url === 'string' && wanted.test(url) && items.indexOf(url) === -1; });
                    if (!urls.length) { return; }
                    // A video replaces everything (one video, no pictures); pictures replace a video.
                    items = kind === 'video' ? urls.slice(0, 1) : items.filter(function (url) { return !VIDEO.test(url); }).concat(urls).slice(0, max);
                    render();
                }

                [].forEach.call(root.querySelectorAll('[data-media-pick]'), function (trigger) {
                    trigger.addEventListener('click', function () {
                        var kind = trigger.dataset.mediaPick;
                        window.open('/laravel-filemanager?type=' + kind, 'FileManager', 'width=900,height=600');
                        window.SetUrl = function (selected) { add((selected || []).map(function (item) { return item.url; }), kind); };
                    });
                });

                render();
            }

            document.addEventListener('DOMContentLoaded', function () {
                [].forEach.call(document.querySelectorAll('.cms-media-picker'), init);
            });
        })();
    </script>
@endonce
