@props(['variant' => 'info', 'dismissible' => false])
@php
    $variantClass = match($variant) {
        'success' => 'alert-success',
        'danger'  => 'alert-danger',
        'warning' => 'alert-warning',
        'info'    => 'alert-info',
        default   => 'alert-info',
    };
@endphp
<div {{ $attributes->merge(['class' => "alert {$variantClass}"]) }} role="alert">
    {{ $slot }}
</div>
