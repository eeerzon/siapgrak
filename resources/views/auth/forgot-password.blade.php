@extends('layouts.guest')

@section('title', 'Lupa Password')

@section('content')
    <h2 class="mb-4 text-lg font-semibold text-slate-800">Lupa Password</h2>
    <p class="mb-4 text-sm text-slate-600">Masukkan email Anda, kami akan kirim link reset password.</p>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="w-full rounded-md bg-navy-900 px-4 py-2 text-sm font-medium text-white hover:bg-navy-800 disabled:opacity-50">
            Kirim Link Reset
        </button>
    </form>

    <p class="mt-4 text-center text-sm text-slate-600">
        <a href="{{ route('login') }}" class="font-medium text-slate-800 hover:underline">Kembali ke Masuk</a>
    </p>
@endsection
