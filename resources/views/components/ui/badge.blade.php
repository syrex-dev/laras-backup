@props(['variant' => 'primary'])
@php
    $variantClass = match($variant) {
        'primary' => 'badge-primary',
        'success' => 'badge-success',
        'danger'  => 'badge-danger',
        'warning' => 'badge-warning',
        'info'    => 'badge-info',
        'muted'   => 'badge-muted',
        default   => 'badge-primary',
    };
@endphp
<span {{ $attributes->merge(['class' => "badge {$variantClass}"]) }}>
    {{ $slot }}
</span>
