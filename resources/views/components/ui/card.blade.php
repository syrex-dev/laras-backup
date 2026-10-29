@props(['title' => null, 'subtitle' => null, 'actions' => null, 'footer' => null])
<div {{ $attributes->merge(['class' => 'card']) }}>
    @if($title || $actions)
    <div class="card-header">
        <div>
            @if($title)<h3 class="card-title">{{ $title }}</h3>@endif
            @if($subtitle)<p class="card-subtitle">{{ $subtitle }}</p>@endif
        </div>
        @if($actions)
        <div class="card-actions" style="display:flex; align-items:center; gap:8px;">
            {{ $actions }}
        </div>
        @endif
    </div>
    @endif
    <div class="card-body">
        {{ $slot }}
    </div>
    @if($footer)
    <div class="card-footer">
        {{ $footer }}
    </div>
    @endif
</div>
