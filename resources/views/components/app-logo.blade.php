@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="TrainTrack" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-lg bg-blue-600">
            <x-app-logo-icon class="size-4 text-white" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="TrainTrack" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-lg bg-blue-600">
            <x-app-logo-icon class="size-4 text-white" />
        </x-slot>
    </flux:brand>
@endif
