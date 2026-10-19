@props([
    'show' => 'open',
    'title' => null,
    'maxWidth' => 'max-w-md',
])

<div x-show="{{ $show }}" 
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     role="dialog" 
     aria-modal="true">
    
    <!-- Backdrop -->
    <div x-show="{{ $show }}" 
         x-transition:enter="ease-out duration-200 motion-reduce:duration-0"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150 motion-reduce:duration-0"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="{{ $show }} = false" 
         class="fixed inset-0 bg-ink/40 backdrop-blur-xs"></div>

    <div class="min-h-full flex items-end sm:items-center justify-center p-0 sm:p-4 text-left">
        <!-- Floating Surface -->
        <div x-show="{{ $show }}" 
             x-transition:enter="ease-out duration-200 motion-reduce:duration-0 transform"
             x-transition:enter-start="translate-y-full sm:translate-y-0 sm:scale-95 sm:opacity-0"
             x-transition:enter-end="translate-y-0 sm:scale-100 sm:opacity-100"
             x-transition:leave="ease-in duration-150 motion-reduce:duration-0 transform"
             x-transition:leave-start="translate-y-0 sm:scale-100 sm:opacity-100"
             x-transition:leave-end="translate-y-full sm:scale-95 sm:opacity-0"
             class="relative bg-surface w-full {{ $maxWidth }} rounded-t-2xl sm:rounded-xl shadow-xl border border-line overflow-hidden flex flex-col max-h-[90vh]">
            
            @if($title || isset($header))
                <div class="px-5 py-4 border-b border-line flex items-center justify-between flex-shrink-0">
                    @if(isset($header))
                        {{ $header }}
                    @else
                        <h3 class="text-sm font-bold text-ink">{{ $title }}</h3>
                    @endif
                    <button type="button" 
                            @click="{{ $show }} = false" 
                            class="min-w-[44px] min-h-[44px] -mr-2 flex items-center justify-center text-ink-faint hover:text-ink rounded-lg transition-colors"
                            aria-label="Tutup">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            @endif

            <div class="p-5 overflow-y-auto">
                {{ $slot }}
            </div>

            @if(isset($footer))
                <div class="p-4 bg-canvas border-t border-line flex-shrink-0">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
