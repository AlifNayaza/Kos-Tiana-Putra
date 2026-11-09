@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex items-center justify-between gap-3">
        <p class="hidden sm:block text-xs text-ink-soft">
            Menampilkan <span class="font-semibold text-ink tabular-nums">{{ $paginator->firstItem() }}</span>
            &ndash; <span class="font-semibold text-ink tabular-nums">{{ $paginator->lastItem() }}</span>
            dari <span class="font-semibold text-ink tabular-nums">{{ $paginator->total() }}</span>
        </p>

        <!-- Mobile: hanya Sebelumnya / Berikutnya agar tidak melebar -->
        <div class="flex w-full sm:hidden items-center justify-between gap-3">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center justify-center min-h-[44px] px-4 rounded-xl border border-line bg-canvas text-sm font-semibold text-ink-faint cursor-not-allowed" aria-disabled="true">Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center justify-center min-h-[44px] px-4 rounded-xl border border-line bg-surface text-sm font-semibold text-ink hover:bg-brand-wash">Sebelumnya</a>
            @endif

            <span class="text-xs text-ink-soft tabular-nums">Hal. {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center justify-center min-h-[44px] px-4 rounded-xl border border-line bg-surface text-sm font-semibold text-ink hover:bg-brand-wash">Berikutnya</a>
            @else
                <span class="inline-flex items-center justify-center min-h-[44px] px-4 rounded-xl border border-line bg-canvas text-sm font-semibold text-ink-faint cursor-not-allowed" aria-disabled="true">Berikutnya</span>
            @endif
        </div>

        <!-- Desktop: nomor halaman -->
        <div class="hidden sm:flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="px-3 py-2 rounded-lg text-sm text-ink-faint" aria-disabled="true" aria-label="Halaman sebelumnya">&lsaquo;</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya" class="px-3 py-2 rounded-lg text-sm font-semibold text-ink hover:bg-brand-wash">&lsaquo;</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 py-2 text-sm text-ink-faint">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="min-w-9 text-center px-3 py-2 rounded-lg bg-brand text-surface text-sm font-bold tabular-nums">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" aria-label="Ke halaman {{ $page }}" class="min-w-9 text-center px-3 py-2 rounded-lg text-sm font-semibold text-ink hover:bg-brand-wash tabular-nums">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya" class="px-3 py-2 rounded-lg text-sm font-semibold text-ink hover:bg-brand-wash">&rsaquo;</a>
            @else
                <span class="px-3 py-2 rounded-lg text-sm text-ink-faint" aria-disabled="true" aria-label="Halaman berikutnya">&rsaquo;</span>
            @endif
        </div>
    </nav>
@endif
