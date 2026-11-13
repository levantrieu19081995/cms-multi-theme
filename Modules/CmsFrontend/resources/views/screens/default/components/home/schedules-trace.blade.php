{{-- Lịch làm việc (lãnh đạo Sở, Ban giám đốc). Truy xuất nguồn gốc là cụm riêng (blocks.trace). --}}
@php($schedules = collect($homeSchedules ?? []))
@if ($schedules->isNotEmpty())
    <div class="cms-plan-grid cms-plan-grid--no-trace cms-home-section cms-home-section--cream">
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
    </div>
@endif
