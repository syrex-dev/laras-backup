@props([
    'label',
    'value',
    'change' => null,      // e.g. ['isUp' => true, 'percent' => '59.3%'] or ['isUp' => false, 'percent' => '12.5%']
    'icon' => null,
    'color' => 'primary',
    'desc' => null,        // e.g. "35 hari ini"
    'extra' => null,
])
<div {{ $attributes->merge(['class' => 'stat-card']) }}>
    <div class="stat-card-top">
        <span class="stat-card-label">{{ $label }}</span>
        @if($icon)
            <div class="stat-card-icon {{ $color }}">
                {{ $icon }}
            </div>
        @endif
    </div>

    <div class="stat-card-value">{{ $value }}</div>

    @if($change || $desc || (isset($slot) && !empty(trim($slot))))
        <div class="stat-card-trend">
            @if($change)
                @php
                    $isUp = $change['isUp'] ?? true;
                    $percent = $change['percent'] ?? ($change['text'] ?? '');
                @endphp
                <span class="trend-pill {{ $isUp ? 'up' : 'down' }}">
                    @if($isUp)
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="18 15 12 9 6 15"></polyline>
                        </svg>
                    @else
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    @endif
                    <span>{{ $percent }}</span>
                </span>
            @endif

            @if($desc)
                <span class="trend-desc">{{ $desc }}</span>
            @elseif(isset($slot) && !empty(trim($slot)))
                <span class="trend-desc">{{ $slot }}</span>
            @endif
        </div>
    @endif
</div>
