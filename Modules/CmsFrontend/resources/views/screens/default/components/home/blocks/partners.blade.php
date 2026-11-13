{{-- Đơn vị phối hợp (tách từ cụm Đối tác và hỗ trợ). --}}
<section class="cms-section cms-partners cms-home-section cms-home-section--white" aria-label="{{ __('frontend.ui.don_vi_phoi_hop') }}">
    @include('cmsfrontend::screens.components.home.section-heading', ['heading' => __('pages.don_vi_phoi_hop'), 'moreUrl' => null])
    <div class="cms-partners-grid">
        @forelse (collect($links ?? [])->take(6) as $partner)
            @php $partnerImage = trim((string) $partner->getFirstMediaUrl('avatars')); @endphp
            <a class="cms-partner" href="{{ $partner->url ?? \Modules\CmsBackend\Support\ModuleUrl::route('cms.doi-tac.show', ['slug' => $partner->slug]) }}">
                @if ($partnerImage !== '')<img src="{{ $partnerImage }}" alt="" loading="lazy" width="160" height="72">@else<i class="ri-{{ $partner->icon ?: 'community-line' }}" aria-hidden="true"></i>@endif
                <span>{{ $partner->title }}</span>
            </a>
        @empty
            <p class="cms-empty">{{ __('frontend.ui.chua_co_don_vi_phoi_hop_duoc_dang') }}</p>
        @endforelse
    </div>
</section>
