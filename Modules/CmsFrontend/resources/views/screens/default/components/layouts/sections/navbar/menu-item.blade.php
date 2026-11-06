@foreach ($items as $item)
    <li>
        @if (!empty($item['children']))
            <details><summary>@if(!empty($item['icon']))<i class="{{ $item['icon'] }} me-1" aria-hidden="true"></i>@endif{{ $item['title'] }}</summary><ul>
                @if($item['url'])<li><a href="{{ $item['url'] }}" @if(($item['target'] ?? null) === '_blank') target="_blank" rel="noopener noreferrer" @endif>Xem {{ $item['title'] }}</a></li>@endif
                @include('cmsfrontend::screens.components.layouts.sections.navbar.menu-item', ['items' => $item['children']])
            </ul></details>
        @elseif ($item['url'])
            <a href="{{ $item['url'] }}" @if(($item['target'] ?? null) === '_blank') target="_blank" rel="noopener noreferrer" @endif>@if(!empty($item['icon']))<i class="{{ $item['icon'] }} me-1" aria-hidden="true"></i>@endif{{ $item['title'] }}</a>
        @else
            <span>@if(!empty($item['icon']))<i class="{{ $item['icon'] }} me-1" aria-hidden="true"></i>@endif{{ $item['title'] }}</span>
        @endif
    </li>
@endforeach
