@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand href="/" {{ $attributes }}>
        <img src="/logo-site.png" alt="SaúdeSelect" class="h-8 object-contain">
    </flux:sidebar.brand>
@else
    <flux:brand href="/" {{ $attributes }}>
        <img src="/logo-site.png" alt="SaúdeSelect" class="h-8 object-contain">
    </flux:brand>
@endif
