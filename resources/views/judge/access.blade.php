@extends('layouts.judge')

@section('title', 'Masuk')

@section('content')
    <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <img src="{{ asset('images/siapgrak-logo.png') }}" alt="" class="mx-auto h-16 w-16">
        <h1 class="mt-4 text-center text-xl font-bold text-navy-900">Masuk sebagai Juri</h1>
        <p class="mt-1 text-center text-sm text-slate-500">Ketik kode akses 8 karakter dari panitia. Biasanya tertulis di kartu meja juri.</p>

        <form method="POST" action="{{ route('judge.access.store') }}" class="mt-6 space-y-4">
            @csrf
            <x-ui.input
                name="code"
                label="Kode Akses"
                placeholder="AB23CD45"
                autocomplete="off"
                autocapitalize="characters"
                spellcheck="false"
                maxlength="8"
                autofocus
                class="text-center font-mono text-2xl font-semibold tracking-[0.3em] uppercase"
                required
            />
            <button type="submit" class="w-full cursor-pointer rounded-md bg-brand-600 px-4 py-3 text-sm font-semibold text-white hover:bg-brand-700">Masuk</button>
        </form>
    </div>

    <p class="mt-4 text-center text-xs text-slate-400">Lupa kode? Minta ke panitia. Kode bisa dibuat ulang kapan saja.</p>
@endsection
