@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <h2 class="mb-4 text-lg font-semibold text-slate-800">Masuk</h2>

    <form method="POST" action="{{ route('login') }}" class="space-y-4" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
            <input id="password" name="password" type="password" required
                   class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
        </div>

        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="remember" class="rounded border-slate-300">
                Ingat saya
            </label>
            <a href="{{ route('password.request') }}" class="text-slate-600 hover:underline">Lupa password?</a>
        </div>

        <button type="submit"
                class="w-full rounded-md bg-navy-900 px-4 py-2 text-sm font-medium text-white hover:bg-navy-800 disabled:opacity-50">
            Masuk
        </button>
    </form>

    <p class="mt-4 text-center text-sm text-slate-600">
        Belum punya akun? <a href="{{ route('register') }}" class="font-medium text-slate-800 hover:underline">Daftar</a>
    </p>
@endsection
