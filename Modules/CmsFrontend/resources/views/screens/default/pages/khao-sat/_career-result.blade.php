{{-- Kết quả định hướng nghề nghiệp (RIASEC). $result lấy từ CareerService::resultFor(). Biểu đồ bằng HTML/CSS, không cần bản build Vite. --}}
<section class="sv-card" aria-labelledby="sv-career-title">
    <h2 id="sv-career-title">{{ __('career.result.title') }}</h2>
    <p class="sv-muted" style="margin:0">{{ __('career.result.code') }}</p>
    <div class="sv-career-code" aria-label="{{ $result['code'] }}">
        @foreach (str_split($result['code']) as $letter)<span>{{ $letter }}</span>@endforeach
    </div>
    <p class="sv-muted" style="margin:0 0 18px">{{ __('career.result.code_hint') }}</p>

    <h2 style="margin-top:8px">{{ __('career.result.top') }}</h2>
    @foreach ($result['top'] as $row)
        <div class="sv-career-top">
            <h3>{{ $row['rank'] }}. {{ $row['code'] }} — {{ $row['name'] }}</h3>
            <p style="margin:0">{{ $row['description'] }}</p>
        </div>
    @endforeach
</section>

<section class="sv-card" aria-labelledby="sv-career-scores">
    <h2 id="sv-career-scores">{{ __('career.result.scores') }}</h2>
    @foreach ($result['scores'] as $row)
        <div class="sv-career-row {{ $row['rank'] !== null ? 'is-top' : '' }}">
            <div class="sv-label"><strong>{{ $row['code'] }}</strong> {{ $row['name'] }}</div>
            <div class="sv-bar" role="img" aria-label="{{ $row['code'] }}: {{ $row['pct'] ?? 0 }}%"><span style="width: {{ $row['pct'] ?? 0 }}%"></span></div>
            <div class="sv-pct">{{ $row['pct'] === null ? '—' : $row['pct'].'%' }}</div>
        </div>
    @endforeach
</section>

<section class="sv-card" aria-labelledby="sv-career-majors">
    <h2 id="sv-career-majors">{{ __('career.result.majors') }}</h2>
    @if ($result['majors']->isEmpty())
        <p class="sv-muted" style="margin:0">{{ __('career.result.majors_empty') }}</p>
    @else
        <ul class="sv-career-majors">
            @foreach ($result['majors'] as $major)
                <li>
                    <strong>{{ $major->cm_ten }}</strong><span class="sv-tag">{{ $major->cm_ma_riasec }}</span>
                    @if ($major->cm_mo_ta)<div class="sv-muted">{{ $major->cm_mo_ta }}</div>@endif
                    @if ($major->cm_url && preg_match('~^https?://~i', $major->cm_url))
                        <a href="{{ $major->cm_url }}" target="_blank" rel="noopener noreferrer">{{ __('career.result.learn_more') }}</a>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
    <p class="sv-muted" style="margin:14px 0 0;font-size:.9rem">{{ __('career.result.note') }}</p>
</section>
