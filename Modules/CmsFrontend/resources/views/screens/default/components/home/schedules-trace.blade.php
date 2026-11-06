{{-- PLHĐ blocks: work schedules (Department leaders, Board of Directors) + traceability portals. --}}
@php
    $schedules = collect($homeSchedules ?? []);
    $traceLinks = collect($homeTraceLinks ?? []);
@endphp
@if ($schedules->isNotEmpty() || $traceLinks->isNotEmpty())
    <div class="cms-plan-grid cms-home-section cms-home-section--cream @if($traceLinks->isEmpty()) cms-plan-grid--no-trace @endif">
        @foreach ($schedules as $schedule)
            <section class="cms-section cms-schedule" aria-labelledby="cms-schedule-{{ $schedule['key'] }}">
                <div class="cms-section-heading">
                    <h2 id="cms-schedule-{{ $schedule['key'] }}">{{ $schedule['title'] }}</h2>
                    @if ($schedule['url'])<a class="cms-more" href="{{ $schedule['url'] }}">{{ __('frontend.ui.xem_tat_ca') }} <span aria-hidden="true">→</span></a>@endif
                </div>
                <ol class="cms-schedule-list">
                    @forelse ($schedule['items'] as $item)
                        <li>
                            <a href="{{ $item['url'] }}">
                                <span class="cms-schedule-icon" aria-hidden="true"><i class="ri-calendar-schedule-line"></i></span>
                                <span class="cms-schedule-text">
                                    <span class="cms-schedule-title">{{ $item['title'] }}</span>
                                    @if (filled($item['date']))<span class="cms-schedule-date">{{ $item['date'] }}</span>@endif
                                </span>
                            </a>
                        </li>
                    @empty
                        <li class="cms-empty">{{ __('frontend.ui.chua_co_lich_lam_viec_duoc_cong_bo') }}</li>
                    @endforelse
                </ol>
            </section>
        @endforeach
        @if ($traceLinks->isNotEmpty())
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
        @endif
    </div>
@endif
