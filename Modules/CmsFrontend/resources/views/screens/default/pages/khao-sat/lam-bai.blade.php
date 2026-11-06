@extends('cmsfrontend::screens.pages.khao-sat.layout')
@section('title', $survey->ks_ten)
@php
    $sections = $form['sections'];
    $total = count($sections);
    $action = $preview ? '#' : route('cms.frontend.khaosat.submit', $survey->ks_ma);
@endphp
@section('content')
<article class="sv-card">
    <h1>{{ $survey->ks_ten }}</h1>
    @if ($preview)<div class="sv-alert sv-alert-info" role="status">{{ __('survey.form.preview_banner') }}</div>@endif
</article>

@if ($errors->any())
    <div class="sv-alert sv-alert-error" role="alert" id="sv-error-summary" tabindex="-1">
        <div>{{ $errors->has('survey') ? $errors->first('survey') : __('survey.form.fix_errors') }}</div>
    </div>
@endif

@if ($total === 0)
    <div class="sv-card sv-muted">{{ __('survey.state.not_found.text') }}</div>
@else
<form method="post" action="{{ $action }}" id="sv-form" data-total="{{ $total }}" novalidate @if ($preview) onsubmit="return false" @endif>
    @csrf
    <div class="sv-hp" aria-hidden="true"><label>Website <input type="text" name="sv_website" tabindex="-1" autocomplete="off"></label></div>

    <div class="sv-progress" id="sv-progress" hidden aria-label="{{ __('survey.form.progress') }}">
        <div class="sv-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span></span></div>
        <div class="sv-dots"></div>
        <div class="sv-progress-text" aria-live="polite"></div>
    </div>

    @foreach ($sections as $si => $section)
        <section class="sv-card" data-step="{{ $si }}" aria-labelledby="sv-sec-{{ $section['key'] }}">
            @if ($section['title'])
                <h2 id="sv-sec-{{ $section['key'] }}">{{ $section['title'] }}</h2>
                @if ($section['description'])<p class="sv-muted">{{ $section['description'] }}</p>@endif
            @endif
            @foreach ($section['questions'] as $q)
                @php
                    $name = 'answers['.$q['key'].']';
                    $field = 'answers.'.$q['key'];
                    $old = old($field);
                    $err = $errors->first($field) ?: ($q['type'] === 'multiple' ? $errors->first($field.'.*') : '');
                    $qid = 'q-'.$q['key'];
                @endphp
                <fieldset class="sv-q" data-q data-type="{{ $q['type'] }}" @if ($q['required']) data-required @endif aria-describedby="{{ $qid }}-hint {{ $qid }}-err">
                    <legend>{{ $q['text'] }} @if ($q['required'])<span class="sv-req" title="{{ __('survey.form.required') }}">*</span>@endif</legend>
                    <div id="{{ $qid }}-hint" class="sv-muted">
                        {{ $q['description'] ?? '' }}
                        @if ($q['type'] === 'single') {{ __('survey.form.choose_one') }} @elseif ($q['type'] === 'multiple') {{ __('survey.form.choose_many') }} @endif
                    </div>
                    @switch($q['type'])
                        @case('single')
                            @foreach ($q['options'] as $o)
                                <label class="sv-opt"><input type="radio" name="{{ $name }}" value="{{ $o['key'] }}" @checked($old === $o['key'])><span>{{ $o['label'] }}</span></label>
                            @endforeach
                            @break
                        @case('multiple')
                            @foreach ($q['options'] as $o)
                                <label class="sv-opt"><input type="checkbox" name="{{ $name }}[]" value="{{ $o['key'] }}" @checked(is_array($old) && in_array($o['key'], $old, true))><span>{{ $o['label'] }}</span></label>
                            @endforeach
                            @break
                        @case('yesno')
                            @foreach (['yes' => __('survey.form.yes'), 'no' => __('survey.form.no')] as $value => $label)
                                <label class="sv-opt"><input type="radio" name="{{ $name }}" value="{{ $value }}" @checked($old === $value)><span>{{ $label }}</span></label>
                            @endforeach
                            @break
                        @case('scale')
                            @php $min = (int) ($q['config']['min'] ?? 1); $max = (int) ($q['config']['max'] ?? 5); @endphp
                            <div class="sv-scale">
                                @for ($n = $min; $n <= $max; $n++)
                                    <label><input type="radio" name="{{ $name }}" value="{{ $n }}" @checked((string) $old === (string) $n)>{{ $n }}</label>
                                @endfor
                            </div>
                            @if (! empty($q['config']['min_label']) || ! empty($q['config']['max_label']))
                                <div class="sv-scale-labels sv-muted"><span>{{ $q['config']['min_label'] ?? '' }}</span><span>{{ $q['config']['max_label'] ?? '' }}</span></div>
                            @endif
                            @break
                        @case('text')
                            <input type="text" name="{{ $name }}" value="{{ is_string($old) ? $old : '' }}" maxlength="255" placeholder="{{ __('survey.form.text_placeholder') }}" aria-label="{{ $q['text'] }}">
                            @break
                        @case('textarea')
                            <textarea name="{{ $name }}" maxlength="5000" placeholder="{{ __('survey.form.text_placeholder') }}" aria-label="{{ $q['text'] }}">{{ is_string($old) ? $old : '' }}</textarea>
                            @break
                    @endswitch
                    <div class="sv-error" id="{{ $qid }}-err" @if (! $err) hidden @endif>{{ $err }}</div>
                </fieldset>
            @endforeach
            <div class="sv-nav">
                <button type="button" class="sv-btn sv-btn-ghost" data-prev @if ($si === 0) hidden @endif>{{ __('survey.form.previous') }}</button>
                @if ($si < $total - 1)
                    <button type="button" class="sv-btn" data-next>{{ __('survey.form.next') }}</button>
                @else
                    <button type="submit" class="sv-btn" data-submit @disabled($preview)>{{ $preview ? __('survey.form.preview_submit') : __('survey.form.submit') }}</button>
                @endif
            </div>
        </section>
    @endforeach
</form>
@endif
@endsection
@section('scripts')
<script>
(function () {
    'use strict';
    var form = document.getElementById('sv-form');
    if (!form) return;
    var steps = Array.prototype.slice.call(form.querySelectorAll('[data-step]'));
    var total = steps.length;
    var style = (document.querySelector('meta[name="sv-progress"]') || {}).content || 'bar';
    var progress = document.getElementById('sv-progress');
    var bar = progress.querySelector('.sv-bar'), dots = progress.querySelector('.sv-dots'), text = progress.querySelector('.sv-progress-text');
    var labelTpl = @json(__('survey.form.step', ['current' => ':current', 'total' => ':total']));
    var needAnswer = @json(__('survey.form.answer_needed'));
    var current = 0;

    form.classList.add('sv-js');
    progress.hidden = false;
    if (style === 'steps') { bar.hidden = true; steps.forEach(function () { dots.appendChild(document.createElement('span')); }); } else { dots.hidden = true; }

    function answered(q) {
        var type = q.getAttribute('data-type');
        if (type === 'text' || type === 'textarea') { var f = q.querySelector('input,textarea'); return f && f.value.trim() !== ''; }
        return !!q.querySelector('input:checked');
    }
    function showError(q, on) {
        var out = q.querySelector('.sv-error');
        out.textContent = on ? needAnswer : ''; out.hidden = !on;
        q.setAttribute('aria-invalid', on ? 'true' : 'false');
    }
    function validateStep(i) {
        var firstBad = null;
        steps[i].querySelectorAll('[data-q][data-required]').forEach(function (q) {
            var ok = answered(q); showError(q, !ok);
            if (!ok && !firstBad) firstBad = q;
        });
        if (firstBad) { var focusable = firstBad.querySelector('input,textarea'); if (focusable) focusable.focus(); return false; }
        return true;
    }
    function render() {
        steps.forEach(function (s, i) { s.classList.toggle('is-current', i === current); });
        var pct = Math.round(((current + 1) / total) * 100);
        bar.setAttribute('aria-valuenow', String(pct)); bar.firstElementChild.style.width = pct + '%';
        Array.prototype.forEach.call(dots.children, function (d, i) { d.className = i < current ? 'is-done' : (i === current ? 'is-current' : ''); });
        text.textContent = labelTpl.replace(':current', current + 1).replace(':total', total);
        var heading = steps[current].querySelector('h2'); if (heading) { heading.setAttribute('tabindex', '-1'); }
    }
    function go(i, focus) { current = i; render(); window.scrollTo({ top: 0, behavior: 'smooth' }); if (focus) { var h = steps[i].querySelector('h2') || steps[i]; h.focus && h.focus({ preventScroll: true }); } }

    form.addEventListener('click', function (e) {
        var next = e.target.closest('[data-next]'), prev = e.target.closest('[data-prev]');
        if (next && validateStep(current)) go(current + 1, true);
        if (prev) go(current - 1, true);
    });
    form.addEventListener('submit', function (e) {
        // Kiểm tra lần cuối ở phía người dùng; máy chủ vẫn kiểm tra đầy đủ.
        for (var i = 0; i < total; i++) { if (!validateStep(i)) { e.preventDefault(); go(i, false); return; } }
        var button = form.querySelector('[data-submit]');
        if (button) { button.disabled = true; button.textContent = @json(__('survey.form.submitting')); }
    });
    // Nếu máy chủ trả lỗi, mở đúng phần chứa câu hỏi lỗi đầu tiên.
    var firstServerError = form.querySelector('.sv-error:not([hidden])');
    if (firstServerError) { var host = firstServerError.closest('[data-step]'); current = steps.indexOf(host); }
    render();
})();
</script>
@endsection
