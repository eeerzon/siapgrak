@extends('layouts.site')

@section('title', $competition->title)
@section('description', $competition->excerpt ?? 'Info lomba dan kejuaraan di Siapgrak.')

@php
    $registrationUrl = $competition->registrationUrl();
    $contacts = $competition->contact_persons ?? [];
    $formatDate = fn ($date) => $date->locale('id')->translatedFormat('j F Y');
@endphp

@section('content')
    <article class="mx-auto max-w-5xl px-4 py-8 md:px-6">
        <a href="{{ route('site.competitions.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-600">← Info Lomba</a>

        <div class="mt-4 grid gap-6 md:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] md:gap-8">
            <div>
                <x-site.cover :url="$competition->coverUrl()" ratio="aspect-[4/5]" class="rounded-lg border border-slate-200" />
            </div>

            <div>
                <h1 class="text-2xl font-bold tracking-tight text-navy-900 md:text-3xl">{{ $competition->title }}</h1>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    @if ($competition->is_managed)
                        <span class="rounded-full bg-navy-50 px-2.5 py-0.5 text-xs font-medium text-navy-700">Dikelola Siapgrak</span>
                    @endif
                    @if ($competition->isRegistrationOpen())
                        <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Pendaftaran dibuka</span>
                    @else
                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500">Pendaftaran ditutup</span>
                    @endif
                </div>

                <dl class="mt-5 grid gap-4 rounded-lg border border-slate-200 bg-white p-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-slate-500">Tanggal Lomba</dt>
                        <dd class="mt-0.5 font-medium text-slate-800"><x-site.date-range :start="$competition->start_date" :end="$competition->end_date" /></dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Batas Pendaftaran</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $competition->registration_deadline ? $formatDate($competition->registration_deadline) : '—' }}</dd>
                    </div>
                    @if ($competition->technical_meeting_date)
                        <div class="sm:col-span-2">
                            <dt class="text-slate-500">Technical Meeting</dt>
                            <dd class="mt-0.5 font-medium text-slate-800">
                                {{ $formatDate($competition->technical_meeting_date) }}{{ $competition->technical_meeting_location ? ' · '.$competition->technical_meeting_location : '' }}
                            </dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-slate-500">Lokasi</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ collect([$competition->location, $competition->province])->filter()->unique()->implode(', ') ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Jenjang</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ implode(', ', $competition->levelLabels()) ?: '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-slate-500">Penyelenggara</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $competition->organizer_name ?: '—' }}</dd>
                    </div>
                </dl>

                @if ($competition->categories()->whereHas('rounds', fn ($query) => $query->whereNotNull('results_published_at'))->exists())
                    <a href="{{ route('site.competitions.results', $competition->slug) }}"
                       class="mt-5 flex items-center justify-between gap-3 rounded-lg border border-navy-100 bg-navy-50 px-4 py-3 text-sm font-semibold text-navy-900 hover:bg-navy-100">
                        Lihat hasil penilaian
                        <span aria-hidden="true">→</span>
                    </a>
                @endif

                <div class="mt-5 space-y-3">
                    @if (! $competition->isRegistrationOpen())
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            <p class="font-medium text-slate-800">Pendaftaran sudah ditutup</p>
                            <p class="mt-0.5 text-slate-500">
                                {{ $competition->registration_deadline ? 'Batas pendaftaran berakhir '.$formatDate($competition->registration_deadline).'.' : 'Lomba ini sudah dimulai.' }}
                                <a href="{{ route('site.competitions.index', ['buka' => 1]) }}" class="font-medium text-brand-700 hover:text-brand-600">Lihat lomba yang masih buka</a>
                            </p>
                        </div>
                    @elseif ($registrationUrl)
                        <a href="{{ $registrationUrl }}" target="_blank" rel="{{ $competition->is_managed ? 'noopener' : 'noopener nofollow' }}"
                           class="flex w-full items-center justify-center gap-2 rounded-md bg-brand-600 px-5 py-3 text-sm font-semibold text-white hover:bg-brand-700 sm:w-auto">
                            {{ $competition->is_managed ? 'Daftar via Kreenconnect' : 'Buka Link Pendaftaran' }}
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H18v4.5M18 6l-7.5 7.5M10.5 6H6v12h12v-4.5" /></svg>
                        </a>
                    @elseif ($competition->is_managed && ! $contacts)
                        <p class="rounded-md bg-navy-50 px-4 py-3 text-sm text-navy-800">Link pendaftaran belum tersedia.</p>
                    @endif

                    @if ($contacts && $competition->isRegistrationOpen())
                        {{-- Native <details> so it works without JavaScript on the public site. --}}
                        <details class="group rounded-lg border border-slate-200 bg-white" @if (! $registrationUrl) open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-slate-800 [&::-webkit-details-marker]:hidden">
                                {{ $registrationUrl ? 'Contact Person' : 'Daftar via Contact Person' }}
                                <svg class="h-4 w-4 text-slate-400 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" /></svg>
                            </summary>
                            <ul class="divide-y divide-slate-100 border-t border-slate-100">
                                @foreach ($contacts as $contact)
                                    <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm">
                                        <div>
                                            <p class="font-medium text-slate-800">{{ $contact['name'] }}</p>
                                            <p class="text-slate-500">{{ $contact['phone'] }}</p>
                                        </div>
                                        <a href="{{ App\Models\CompetitionListing::whatsappUrl($contact['phone']) }}" target="_blank" rel="noopener nofollow"
                                           class="rounded-md bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-700">Chat WhatsApp</a>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                </div>
            </div>
        </div>

        @if ($competition->excerpt || $competition->body)
            <div class="mx-auto mt-10 max-w-3xl border-t border-slate-200 pt-8">
                <h2 class="text-lg font-bold text-navy-900">Info lengkap dari panitia</h2>
                @if ($competition->excerpt)
                    <p class="mt-3 text-lg text-slate-600">{{ $competition->excerpt }}</p>
                @endif
                @if ($competition->body)
                    <x-site.article-body :text="$competition->body" />
                @endif
            </div>
        @endif
    </article>
@endsection
