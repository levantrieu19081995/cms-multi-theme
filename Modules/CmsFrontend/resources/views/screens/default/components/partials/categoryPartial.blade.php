@props(['items' => null, 'categoryName' => null])

<section class="listing-section" id="listing-section">
    <div class="container-fluid cms-page-container">
        @isset($categoryOptions)
            <form class="cms-listing-filter" role="search" method="GET" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.tin-tuc.index') }}">
                <input type="search" name="query" value="{{ $filter ?? '' }}" placeholder="{{ __('frontend.ui.tim_kiem_bai_viet_2') }}" maxlength="200" aria-label="{{ __('frontend.ui.tu_khoa') }}">
                <select name="category" aria-label="{{ __('frontend.ui.chuyen_muc') }}">
                    <option value="">{{ __('frontend.ui.tat_ca_chuyen_muc') }}</option>
                    @foreach ($categoryOptions as $option)
                        <option value="{{ $option->term->slug }}" @selected(($category ?? null) === $option->term->slug)>{{ $option->term->name }}</option>
                    @endforeach
                </select>
                <button type="submit">{{ __('frontend.ui.tim') }}</button>
            </form>
        @endisset
        <div class="parent2">
            {{-- contents --}}
            @foreach ($items as $item)
                <div class="content2" id="{{ $item->category }}">
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
                                    <span><b>{{ __('frontend.ui.nguoi_dang') }}</b> {{ $i->author }}</span>
                                    @if (filled($i->author_names)) <span><b>{{ __('frontend.ui.tac_gia') }}</b> {{ $i->author_names }}</span> @endif 
                                </div>
                                <div class="public"> 
                                    <span><b>{{ __('frontend.ui.ngay_dang') }}</b> {{ $i->public_date }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        @if ($posts->count() > 0)
            <div class="form-layout-footer" style="margin-top: 20px; margin-right: 20px;margin-left: 20px;">
                {{ $posts->onEachSide(2)->links('cmsbackend::screens.components.shared.layouts.paginate') }}
            </div>
        @endif

    </div>
</section>
