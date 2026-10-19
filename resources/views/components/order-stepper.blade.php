@props([
    'status' => null,
    'step' => 1,
    'compact' => false,
])

@php
    $currentStep = (int) $step;
    if ($status) {
        $currentStep = match (strtolower(trim((string)$status))) {
            'menunggu' => 1,
            'diproses' => 2,
            'diantar'  => 3,
            'selesai'  => 4,
            default    => 1,
        };
    }

    $steps = [
        1 => 'Menunggu',
        2 => 'Diproses',
        3 => 'Diantar',
        4 => 'Selesai',
    ];
@endphp

@php
    $activeCircleClass = match ($currentStep) {
        1 => 'bg-wait text-surface ring-4 ring-wait-wash',
        2 => 'bg-process text-surface ring-4 ring-process-wash',
        3 => 'bg-brand text-surface ring-4 ring-brand-wash',
        4 => 'bg-done text-surface ring-4 ring-done-wash',
        default => 'bg-brand text-surface ring-4 ring-brand-wash',
    };
    $activeBarClass = match ($currentStep) {
        1 => 'bg-wait',
        2 => 'bg-process',
        3 => 'bg-brand',
        4 => 'bg-done',
        default => 'bg-brand',
    };
@endphp

@if($compact)
    <div {{ $attributes->merge(['class' => 'flex items-center gap-1.5 w-full']) }}>
        @foreach($steps as $sIndex => $sLabel)
            <div class="flex-1 flex flex-col gap-1">
                <div class="h-1.5 rounded-full transition-all duration-300 motion-reduce:transition-none {{ $sIndex < $currentStep ? 'bg-brand' : ($sIndex === $currentStep ? $activeBarClass : 'bg-line') }}"></div>
                <span class="text-[10px] {{ $sIndex === $currentStep ? 'font-bold text-ink' : ($sIndex < $currentStep ? 'font-medium text-ink-soft' : 'font-normal text-ink-faint') }}">
                    {{ $sLabel }}
                </span>
            </div>
        @endforeach
    </div>
@else
    <div {{ $attributes->merge(['class' => 'w-full py-2']) }}>
        <div class="relative grid grid-cols-4 items-center">
            <!-- Progress Line Behind Stepper -->
            <div class="absolute top-4 left-6 right-6 h-0.5 bg-line -z-0"></div>
            <div class="absolute top-4 left-6 h-0.5 bg-brand transition-all duration-500 motion-reduce:transition-none -z-0"
                 style="width: {{ $currentStep <= 1 ? '0%' : ($currentStep == 2 ? '33.3%' : ($currentStep == 3 ? '66.6%' : '100%')) }};"></div>

            @foreach($steps as $sIndex => $sLabel)
                <div class="flex flex-col items-center text-center relative z-10">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300 motion-reduce:transition-none {{ $sIndex < $currentStep ? 'bg-brand text-surface' : ($sIndex === $currentStep ? $activeCircleClass : 'bg-surface border-2 border-line text-ink-faint') }}">
                        @if($sIndex < $currentStep)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            <span>{{ $sIndex }}</span>
                        @endif
                    </div>
                    <span class="text-xs mt-2 {{ $sIndex === $currentStep ? 'font-bold text-ink' : ($sIndex < $currentStep ? 'font-medium text-ink-soft' : 'font-normal text-ink-faint') }}">
                        {{ $sLabel }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
@endif
