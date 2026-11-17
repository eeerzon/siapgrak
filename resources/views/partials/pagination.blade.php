{{--
    Indonesian, light-only pagination: Laravel's default view is English and follows
    the device's dark mode, which clashes with the light pages. Use via ->links('partials.pagination').
--}}
@if ($paginator->hasPages())
    @php
        $base = 'inline-flex h-9 min-w-9 items-center justify-center rounded-md border px-3 text-sm font-medium';
        $idle = $base.' border-slate-300 bg-white text-slate-700 hover:border-slate-400 hover:bg-slate-50';
        $disabled = $base.' border-slate-200 bg-white text-slate-300';
    @endphp

    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-col items-center justify-between gap-3 sm:flex-row">
        <p class="text-sm text-slate-500">
            Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }}
        </p>

        <div class="flex flex-wrap items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="{{ $disabled }}" aria-disabled="true">‹ Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $idle }}">‹ Sebelumnya</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="{{ $disabled }}">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="{{ $base }} border-navy-900 bg-navy-900 text-white">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="{{ $idle }} hidden sm:inline-flex" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $idle }}">Berikutnya ›</a>
            @else
                <span class="{{ $disabled }}" aria-disabled="true">Berikutnya ›</span>
            @endif
        </div>
    </nav>
@endif
