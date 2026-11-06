@props(['items' => null, 'categories' => null])
@php
    use Illuminate\Support\Str;
@endphp

<section class="listing-section" id="listing-section">
    <div class="container-fluid">
        <div class="parent">
            {{-- contents --}}
            @foreach ($items as $item)
                <div class="content" id="{{ $item->category }}">
                    <div class="content-title">
                        <h4>{{ $item->category }}</h4>
                    </div>
                    <div class="content-items">
                        @foreach ($item->list as $index => $i)
                            <a class="grid-item {{ $index == 0 ? 'first-content-item' : '' }}"
                                style="height: 100%; padding: 18px"
                                href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.tin-tuc.show', ['slug' => $i->slug]) }}">
                                @if ($i->image)
                                    <img src="{{ $i->image }}" alt="{{ $i->title }}" loading="lazy" decoding="async">
                                @endif
                                <h5>{{ $i->title }}</h5>
                                <p class="description">{{ $i->description }}</p>
                                <div class="public">
                                    <span><b>{{ __('frontend.ui.tac_gia') }}</b> {{ $i->author }}</span>
                                    <span><b>{{ __('frontend.ui.ngay_dang') }}</b> {{ $i->public_date }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <a class="read-more"
                        href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.tin-tuc.index', ['category' => Str::slug($item->category), 'name' => $item->category]) }}">
                        <span>{{ __('frontend.ui.xem_them') }}</span>
                        <svg class="icon" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm4.28 10.28a.75.75 0 000-1.06l-3-3a.75.75 0 10-1.06 1.06l1.72 1.72H8.25a.75.75 0 000 1.5h5.69l-1.72 1.72a.75.75 0 101.06 1.06l3-3z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </a>

                </div>
            @endforeach
            {{-- Categories box --}}
            <div class="categories grid-item">
                <h4 class="categories-title">{{ __('frontend.ui.chuyen_muc') }}</h4>
                <ul class="categories-list">
                    @foreach ($categories as $cat)
                        <li class="description categories-list-item">
                            <a href="#{{ $cat }}"><i class="ri-book-2-fill"
                                    style="color: #0C57A3"></i>{{ $cat }}</a>
                        </li>
                    @endforeach
            </div>
            </ul>
        </div>
    </div>
</section>
