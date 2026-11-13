{{-- Truy xuất nguồn gốc: hai cổng (tách từ cụm Lịch làm việc; cùng dải màu kem). --}}
@php($traceLinks = collect($homeTraceLinks ?? []))
@if ($traceLinks->isNotEmpty())
    <div class="cms-home-section cms-home-section--cream cms-trace-band">
        <section class="cms-section cms-trace" aria-labelledby="cms-trace-title">
            <div class="cms-section-heading"><h2 id="cms-trace-title">{{ __('frontend.ui.truy_xuat_nguon_goc') }}</h2></div>
            <div class="cms-trace-list">
                @foreach ($traceLinks as $link)
                    @php($tag = $link['url'] ? 'a' : 'div')
                    <{{ $tag }} class="cms-trace-card @unless($link['url']) cms-trace-card--pending @endunless"
                        @if($link['url']) href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" @endif>
                        <span class="cms-trace-media" aria-hidden="true">
                            @if ($link['image'])<img src="{{ $link['image'] }}" alt="" loading="lazy">@else<i class="{{ $link['icon'] }}"></i>@endif
                        </span>
                        <span class="cms-trace-text">
                            <span class="cms-trace-title">{{ $link['title'] }}</span>
                            <span class="cms-trace-caption">{{ $link['url'] ? $link['caption'] : __('pages.chua_cau_hinh_lien_ket') }}</span>
                        </span>
                        @if ($link['url'])<span class="cms-trace-out" aria-hidden="true"><i class="ri-external-link-line"></i></span><span class="cms-sr-only">{{ __('frontend.ui.mo_tab_moi') }}</span>@endif
                    </{{ $tag }}>
                @endforeach
            </div>
        </section>
    </div>
@endif
