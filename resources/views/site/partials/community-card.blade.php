<a href="{{ route('site.community.show', $communityPost->slug) }}"
   class="group flex flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <x-site.cover :url="$communityPost->coverUrl()" />
    <div class="flex flex-1 flex-col p-4">
        <p class="text-xs font-semibold tracking-wide text-brand-700 uppercase">{{ $communityPost->type->label() }}</p>
        <h3 class="mt-1 font-semibold leading-snug text-navy-900 group-hover:text-brand-700">{{ $communityPost->title }}</h3>
        <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $communityPost->excerpt ?: $communityPost->body }}</p>
        <p class="mt-auto pt-3 text-xs text-slate-500">{{ $communityPost->published_at->copy()->locale('id')->translatedFormat('j M Y') }}</p>
    </div>
</a>
