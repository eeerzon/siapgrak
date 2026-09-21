@extends('layouts.judge')

@section('title', 'Masuk')

@section('content')
    <div class="mt-10">
        <h1 class="text-lg font-semibold text-slate-800">Masuk sebagai Juri</h1>
        <p class="mt-1 text-sm text-slate-500">Masukkan kode akses yang diberikan panitia.</p>

        <form method="POST" action="{{ route('judge.access.store') }}" class="mt-6 space-y-4">
            @csrf
            <x-ui.input
                name="code"
                label="Kode Akses"
                placeholder="mis. AB23CD45"
                autocomplete="off"
                autofocus
                class="text-center text-lg tracking-widest uppercase"
                required
            />
            <x-ui.button type="submit" class="w-full">Masuk</x-ui.button>
        </form>
    </div>
@endsection
