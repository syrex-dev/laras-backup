@props(['variant' => 'primary', 'size' => 'md', 'type' => 'button', 'href' => null])
@php
    $variantClass = match($variant) {
        'primary' => 'btn-primary',
        'outline' => 'btn-outline',
        'danger'  => 'btn-danger',
        'ghost'   => 'btn-ghost',
        default   => 'btn-primary',
    };
    $sizeClass = match($size) {
        'sm' => 'btn-sm',
        'lg' => 'btn-lg',
        default => '',
    };
    $classes = trim("btn {$variantClass} {$sizeClass}");
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
