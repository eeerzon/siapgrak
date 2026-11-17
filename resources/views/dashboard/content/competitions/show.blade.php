@extends('layouts.dashboard')

@section('title', $competition->title)

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Info Lomba', 'href' => route('dashboard.content.competitions.index')],
        ['label' => $competition->title],
    ]" />

    <x-layout.page-header :title="$competition->title">
        <x-slot:actions>
            @if ($competition->status === App\Enums\ContentStatus::Published)
                <x-ui.button :href="route('site.competitions.show', $competition->slug)" variant="secondary" target="_blank" rel="noopener">Halaman Publik ↗</x-ui.button>
            @endif
            <x-ui.button :href="route('dashboard.content.competitions.judging.show', $competition)" variant="secondary">Penjurian</x-ui.button>
            <x-ui.button :href="route('dashboard.content.competitions.edit', $competition)">Edit</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="max-w-5xl space-y-6">
        {{-- Same arrangement as the public detail page: poster left, details right. --}}
        <div @class(['grid gap-6 lg:gap-8', 'lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]' => $competition->coverUrl()])>
            @if ($competition->coverUrl())
                <x-site.cover :url="$competition->coverUrl()" ratio="aspect-[4/5]" class="w-full max-w-sm self-start rounded-lg border border-slate-200 lg:max-w-none" />
            @endif

            <dl class="grid content-start gap-4 self-start rounded-lg border border-slate-200 bg-white p-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-slate-500">Status</dt>
                    <dd class="mt-1"><x-ui.status-badge :status="$competition->status" /></dd>
                </div>
                <div>
                    <dt class="text-slate-500">Pengelolaan</dt>
                    <dd class="mt-1 font-medium text-slate-800">
                        @if ($competition->is_managed)
                            <x-ui.badge variant="info">Kreen</x-ui.badge>
                        @else
                            Eksternal
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">Tanggal</dt>
                    <dd class="mt-1 font-medium text-slate-800"><x-site.date-range :start="$competition->start_date" :end="$competition->end_date" /></dd>
                </div>
                <div>
                    <dt class="text-slate-500">Batas Pendaftaran</dt>
                    <dd class="mt-1 font-medium text-slate-800">{{ $competition->registration_deadline?->locale('id')->translatedFormat('j M Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Technical Meeting</dt>
                    <dd class="mt-1 font-medium text-slate-800">
                        @if ($competition->technical_meeting_date)
                            {{ $competition->technical_meeting_date->locale('id')->translatedFormat('j M Y') }}{{ $competition->technical_meeting_location ? ' · '.$competition->technical_meeting_location : '' }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">Lokasi</dt>
                    <dd class="mt-1 font-medium text-slate-800">{{ collect([$competition->location, $competition->province])->filter()->unique()->implode(', ') ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Jenjang</dt>
                    <dd class="mt-1 font-medium text-slate-800">{{ implode(', ', $competition->levelLabels()) ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Penyelenggara</dt>
                    <dd class="mt-1 font-medium text-slate-800">{{ $competition->organizer_name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Contact Person</dt>
                    <dd class="mt-1 font-medium text-slate-800">
                        @forelse ($competition->contact_persons ?? [] as $contact)
                            <span class="block">{{ $contact['name'] }} · {{ $contact['phone'] }}</span>
                        @empty
                            —
                        @endforelse
                    </dd>
                </div>
                @if ($competition->is_managed)
                    <div>
                        <dt class="text-slate-500">Slug Event Kreenconnect</dt>
                        <dd class="mt-1 font-medium break-all text-slate-800">
                            {{ $competition->kreen_slug }}
                            @if ($competition->kreenUrl())
                                <a href="{{ $competition->kreenUrl() }}" target="_blank" rel="noopener" class="mt-1 block text-sky-700 hover:underline">Buka di Kreenconnect ↗</a>
                            @endif
                        </dd>
                    </div>
                @else
                    <div>
                        <dt class="text-slate-500">Link Pendaftaran</dt>
                        <dd class="mt-1 font-medium break-all text-slate-800">
                            @if ($competition->external_url)
                                <a href="{{ $competition->external_url }}" target="_blank" rel="noopener nofollow" class="text-sky-700 hover:underline">{{ $competition->external_url }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                @endif
            </dl>
        </div>

        @if ($competition->excerpt)
            <p class="text-slate-600">{{ $competition->excerpt }}</p>
        @endif

        @if ($competition->body)
            <x-site.article-body :text="$competition->body" />
        @endif

        <div class="border-t border-slate-200 pt-4">
            @include('dashboard.content.partials.delete-form', ['action' => route('dashboard.content.competitions.destroy', $competition)])
        </div>
    </div>
@endsection
