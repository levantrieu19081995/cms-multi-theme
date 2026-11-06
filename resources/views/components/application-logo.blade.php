@php
    use App\DataProviders\MenuDataProvider;
    use App\DataProviders\OptionDataProvider;
    use App\DataProviders\MenuItemDataProvider;
    use App\Enums\OptionEnum;

    $optionDataProvider = new OptionDataProvider();
    $title = $optionDataProvider->getOptionByKey(OptionEnum::WEB_TITLE->value);
    $description = $optionDataProvider->getOptionByKey(OptionEnum::WEB_DESCRIPTION->value);
    $logo = $optionDataProvider->getOptionByKey(OptionEnum::WEB_LOGO->value);

@endphp

<div class="app-brand justify-content-center mt-5">
    <a href="/" class="app-brand-link gap-2">
        <span class="app-brand-logo demo"><span>

                <img width="auto" height="70px" src="{{ $logo->value }}">
            </span>
        </span>
        {{-- <span class="app-brand-text demo text-heading fw-semibold">{{$title->value}}</span> --}}
    </a>
</div>
