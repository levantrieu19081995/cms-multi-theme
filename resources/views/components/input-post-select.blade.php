@props(['disabled' => false, 'value' => '', 'label' => '', 'callback' => 'postSelectCallback', 'displayMode' => 'input-group', 'type' => \App\Enums\PostType::POST->value])

@php
    $label = $label ?: 'Chọn ' . \App\Enums\PostType::tryFrom($type)->label();
    $ranNum = rand(10, 1000);
    $id = 'account-input-' . $ranNum;
    $attributes['label'] = $attributes['name'] . '_label';

    $routes = [
        'post' => 'bai-viet.index',
        'page' => 'trang.index',
        'image' => 'hinh-anh.index',
        'video' => 'video.index'
    ];
@endphp

@if ($displayMode == 'input-group')
<div class="input-group">
    <input {{ $disabled ? 'disabled' : '' }}
            name="{{ $attributes['label'] }}"
            class="form-control"
            data-display="{{ $id }}"
            readonly
            style="background: white; cursor: pointer;"
            value="{{ $label }}">
    <input type="hidden" id="hash" data-value="{{ $id }}" {!! $attributes !!} value="{{ $value }}">
    <button class="btn btn-outline-secondary waves-effect {{ $value ? '' : 'd-none' }}" type="button" data-clear="{{ $id }}">Bỏ chọn</button>
    <button class="btn btn-outline-secondary waves-effect {{ $value ? 'd-none' : '' }}" type="button" data-id="{{ $id }}">Chọn</button>
</div>
@else
<input type="hidden" name="{{ $attributes['name'] }}_type" value="{{ $type }}">
<input data-value="{{ $id }}" {!! $attributes !!} value="{{ $value }}" class="form-control" readonly data-display="{{ $id }}">
@endif

<script>
    if (document.querySelectorAll('button[data-clear={{ $id }}]').length > 0) {
        document.querySelectorAll('button[data-clear={{ $id }}]')[0].addEventListener('click', function (evt) {
            document.querySelectorAll('[data-display={{ $id }}]')[0].value = 'Chọn tài khoản';
            document.querySelectorAll('[data-value={{ $id }}]')[0].value = '';
            document.querySelectorAll('button[data-id={{ $id }}]')[0].classList.remove('d-none');
            document.querySelectorAll('button[data-clear={{ $id }}]')[0].classList.add('d-none');
        });
    }
    document.querySelectorAll('button[data-id={{ $id }}],[data-display={{ $id }}]').forEach((item) => {
        item.addEventListener('click', function (evt) {
            let w = 700;
            let h = screen.height < 800 ? screen.height : 800;

            // Fixes dual-screen position                             Most browsers      Firefox
            const dualScreenLeft = window.screenLeft !==  undefined ? window.screenLeft : window.screenX;
            const dualScreenTop = window.screenTop !==  undefined   ? window.screenTop  : window.screenY;

            const width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
            const height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

            const systemZoom = width / window.screen.availWidth;
            const left = (width - w) / 2 / systemZoom + dualScreenLeft
            const top = (height - h) / 2 / systemZoom + dualScreenTop

            window.open('{{ \Modules\CmsBackend\Support\ModuleUrl::route($routes[$type]) }}?mode=select&state={{ $ranNum }}&pageSize=10', 'targetWindow',
                                        `directories=no,
                                        titlebar=no
                                        location=no,
                                        status=no,
                                        menubar=no,
                                        scrollbars=yes,
                                        width=${w / systemZoom},
                                        height=${h / systemZoom},
                                        top=${top},
                                        left=${left}`);
        })
    });

    window._postSelectCallback_{{ $ranNum }} = function (state, res) {
        // Var state để đảm bảo đang hoạt động trên cùng 1 session
        if ({{ $ranNum }} == state) {
            try {
                @if ($displayMode == 'input-group')
                if (document.querySelectorAll('[data-display={{ $id }}]')[0]) {
                    document.querySelectorAll('[data-display={{ $id }}]')[0].value = res.title;
                }
                if (document.querySelectorAll('[data-value={{ $id }}]')[0]) {
                    document.querySelectorAll('[data-value={{ $id }}]')[0].value = res.id;
                }
                if (document.querySelectorAll('button[data-id={{ $id }}]')[0]) {
                    document.querySelectorAll('button[data-id={{ $id }}]')[0].classList.add('d-none');
                }
                if (document.querySelectorAll('button[data-clear={{ $id }}]')[0]) {
                    document.querySelectorAll('button[data-clear={{ $id }}]')[0].classList.remove('d-none');
                }
                @endif
                {{ $callback }}(res);
            } catch (error) {
                console.log(error);
            }
        }
    }
</script>
