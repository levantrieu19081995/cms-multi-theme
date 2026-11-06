<div id="card-blankLayout">
    <div>
        <h1 class="text-white fw-bold text-uppercase text-center" style="vertical-align: middle;">
            {{ $title ?? ($breadcrumbs->last()->title ?? '') }}
        </h1>
        <h5 class="text-white text-center">
            @foreach ($breadcrumbs as $breadcrumb)
                @if (!empty($breadcrumb->url))
                    <a href="{{ $breadcrumb->url }}"
                        @if (url()->current() == $breadcrumb->url) class="active" @endif>{{ $breadcrumb->title }}</a>
                @else
                    <span>{{ $breadcrumb->title }}</span>
                @endif

                @if (!$loop->last)
                    <i class="ri-arrow-right-s-line"></i>
                @endif
            @endforeach
        </h5>
    </div>
</div>
