@extends('cmsfrontend::screens.pages.khao-sat.layout')
@section('title', $survey->ks_ten)
@section('content')
<article class="sv-card">
    <h1>{{ $survey->ks_ten }}</h1>
    @if ($survey->ks_mo_ta_ngan)<p class="sv-muted">{{ $survey->ks_mo_ta_ngan }}</p>@endif
    <p class="sv-muted">
        {{ __('survey.intro.questions', ['count' => $questionCount]) }}
        @if ($survey->ks_ket_thuc) · {{ __('survey.intro.closes', ['time' => $survey->ks_ket_thuc->format('H:i d/m/Y')]) }} @endif
    </p>
    @if ($introHtml)<div class="sv-intro">{!! $introHtml !!}</div>@endif
</article>

@if ($errors->any())
    <div class="sv-alert sv-alert-error" role="alert">
        @foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach
    </div>
@endif

<form method="post" action="{{ \Modules\CmsBackend\Support\SurveyCluster::route('cms.frontend.khaosat.start', $survey->ks_ma) }}" class="sv-card" novalidate>
    @csrf
    @if ($identity['collect'])
        <h2>{{ __('survey.intro.identity_title') }}</h2>
        @if ($identity['anonymous_allowed'])<p class="sv-muted">{{ __('survey.intro.identity_optional') }}</p>@endif
        @if ($identity['anonymous_allowed'])
            <div class="sv-field">
                <label><input type="checkbox" name="an_danh" value="1" id="sv-anon" @checked(old('an_danh'))> {{ __('survey.intro.anonymous') }}</label>
            </div>
        @endif
        <div id="sv-identity">
            <div class="sv-field">
                <label class="sv-label" for="ho_ten">{{ __('survey.field.full_name') }} <span class="sv-req" aria-hidden="true">*</span></label>
                <input type="text" id="ho_ten" name="ho_ten" value="{{ old('ho_ten') }}" maxlength="255" autocomplete="name" @error('ho_ten') aria-invalid="true" @enderror>
                @error('ho_ten')<div class="sv-error">{{ $message }}</div>@enderror
            </div>
            <p class="sv-muted" style="margin:0 0 8px">{{ __('survey.intro.contact_hint') }}</p>
            <div class="sv-field">
                <label class="sv-label" for="so_dien_thoai">{{ __('survey.field.phone') }}</label>
                <input type="tel" id="so_dien_thoai" name="so_dien_thoai" value="{{ old('so_dien_thoai') }}" maxlength="20" autocomplete="tel">
                @error('so_dien_thoai')<div class="sv-error">{{ $message }}</div>@enderror
            </div>
            <div class="sv-field">
                <label class="sv-label" for="email">{{ __('survey.field.email') }}</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email">
                @error('email')<div class="sv-error">{{ $message }}</div>@enderror
            </div>
            <div class="sv-field">
                <label><input type="checkbox" name="dong_y" value="1" @checked(old('dong_y'))> {{ __('survey.intro.consent') }}</label>
                @error('dong_y')<div class="sv-error">{{ $message }}</div>@enderror
            </div>
        </div>
    @else
        <p class="sv-muted">{{ __('survey.intro.privacy') }}</p>
    @endif
    @if (! empty($schools))
        <div class="sv-field">
            <label class="sv-label" for="truong">{{ __('survey.intro.school') }}</label>
            <select id="truong" name="truong" style="width:100%;padding:10px 12px;border:1px solid var(--sv-border);border-radius:var(--sv-radius);font:inherit;background:#fff">
                <option value="">{{ __('survey.intro.school_none') }}</option>
                @foreach ($schools as $school)
                    <option value="{{ $school['key'] }}" @selected(old('truong') === $school['key'])>{{ $school['name'] }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <button type="submit" class="sv-btn">{{ $resuming ? __('survey.intro.resume') : __('survey.intro.start') }}</button>
</form>
@endsection
@section('scripts')
@if ($identity['collect'] && $identity['anonymous_allowed'])
<script>
    (function () {
        var anon = document.getElementById('sv-anon'), box = document.getElementById('sv-identity');
        if (!anon || !box) return;
        function sync() { box.hidden = anon.checked; }
        anon.addEventListener('change', sync); sync();
    })();
</script>
@endif
@endsection
