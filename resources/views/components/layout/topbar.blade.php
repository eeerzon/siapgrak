{{--
    Mobile-only (hidden md:+, sidebar takes over there). No hamburger — nav on
    mobile is the bottom nav (mobile-nav.blade.php), not a slide-out drawer.
    Account/Logout lives in mobile-nav's "Lainnya" tab, not here, so there's a
    single account entry point per breakpoint instead of two.
--}}
@props(['organizations'])

@php
    $primaryOrganization = $organizations->first();
@endphp

<header class="sticky top-0 z-30 flex items-center border-b border-navy-900 bg-navy-950 px-4 py-3 md:hidden">
    <img src="{{ asset('images/siapgrak-logo.png') }}" alt="" class="mr-2.5 h-8 w-8 shrink-0">
    <div class="min-w-0">
        <div class="text-base font-semibold text-white">Siapgrak</div>
        <!-- <div class="truncate text-xs text-slate-400">
            {{ $primaryOrganization?->name ?? 'Belum ada organisasi' }}
        </div> -->
    </div>
</header>
