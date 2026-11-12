{{-- Value of one interaction metric. Needs $metric (SocialStatistics::metricCatalog() row). --}}
@if ($metric['available'])
    <div class="h5 mb-0" title="{{ $metric['value'] === null ? __('admin.social.metric_not_synced') : '' }}">{{ $metric['value'] === null ? '—' : number_format($metric['value']) }}</div>
@elseif ($metric['key'] === 'reach')
    <div class="small text-muted">{{ __('admin.social.metric_reach_unavailable') }}</div>
@else
    <div class="small text-muted">{{ __('admin.social.metric_after_connect', ['channel' => $metric['channel']->label()]) }}</div>
@endif
