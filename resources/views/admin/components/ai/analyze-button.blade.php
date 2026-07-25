@props([
    'scopeType',
    'scopeId',
    'question' => null,
    'label' => 'Analizar con IA',
    'iconOnly' => false,
    'size' => 'sm',
])

@php
    $allowedRole = in_array(
        auth()->user()?->role,
        ['superadmin', 'school_admin', 'director'],
        true
    );

    $url = route('admin.ai.index', array_filter([
        'scope_type' => $scopeType,
        'scope_id' => $scopeId,
        'question' => $question,
    ], fn ($value) => $value !== null && $value !== ''));
@endphp

@if($allowedRole && \Illuminate\Support\Facades\Route::has('admin.ai.index'))
    <a
        href="{{ $url }}"
        {{ $attributes->class([
            'btn',
            'btn-'.$size,
            'btn-primary',
            'btn-icon rounded-circle' => $iconOnly,
        ])->merge([
            'title' => $label,
            'aria-label' => $label,
        ]) }}
    >
        <i class="ti ti-sparkles"></i>

        @unless($iconOnly)
            <span class="ms-1">{{ $label }}</span>
        @endunless
    </a>
@endif
