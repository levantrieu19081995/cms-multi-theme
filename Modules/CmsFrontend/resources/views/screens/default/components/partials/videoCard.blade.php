 

@props(['cms.video','large'=>false,'class'=>''])

@php
  $src = $video['embed_url'] ?? $video->embed_url ?? $video['url'] ?? $video->url ?? '';
  if (preg_match('~youtu\.be/([^\?&]+)~',$src,$m))  $src = 'https://www.youtube.com/embed/'.$m[1];
  if (preg_match('~youtube\.com/watch\\?v=([^\\?&]+)~',$src,$m)) $src = 'https://www.youtube.com/embed/'.$m[1];
  $title = $video['title'] ?? $video->title ?? '';
@endphp

<div class="video-card {{ $class }} {{ $large ? 'video-card--lg' : '' }}">
  <div class="video-frame">
    <iframe
      src="{{ $src }}" title="{{ $title }}" loading="lazy"
      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
      allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>

    @if($title)
      <div class="video-title-overlay">{{ $title }}</div>
    @endif
  </div>
</div>

