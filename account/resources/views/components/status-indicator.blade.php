<!-- Google-Style Staff Status Indicator Component -->
@props(['status' => 'offline', 'size' => 'md'])

@php
    $colors = [
        'online' => 'bg-green-500',
        'away' => 'bg-yellow-500',
        'dnd' => 'bg-red-500',
        'offline' => 'bg-gray-400',
    ];

    $sizes = [
        'sm' => 'w-2 h-2 border-[1px]',
        'md' => 'w-3 h-3 border-2',
        'lg' => 'w-4 h-4 border-2',
    ];

    $colorClass = $colors[$status] ?? $colors['offline'];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

<span class="absolute bottom-0 right-0 {{ $colorClass }} {{ $sizeClass }} border-white rounded-full shadow-sm" title="{{ ucfirst($status) }}"></span>
