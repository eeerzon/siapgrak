@props(['title', 'description' => null])

<div class="border-b border-slate-200 bg-white">
    <div class="mx-auto max-w-6xl px-4 py-8 md:px-6 md:py-10">
        <h1 class="border-l-4 border-brand-600 pl-3 text-2xl font-bold tracking-tight text-navy-900 md:text-3xl">{{ $title }}</h1>
        @if ($description)
            <p class="mt-2 max-w-2xl text-sm text-slate-500 md:text-base">{{ $description }}</p>
        @endif
        {{ $slot }}
    </div>
</div>
