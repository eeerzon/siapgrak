@extends('layouts.dashboard')

@section('title', 'Tambah Info Lomba')

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Info Lomba', 'href' => route('dashboard.content.competitions.index')],
        ['label' => 'Tambah'],
    ]" />
    <x-layout.page-header title="Tambah Info Lomba" />

    <div class="mx-auto max-w-2xl">
        @include('dashboard.content.competitions._form', ['action' => route('dashboard.content.competitions.store')])
    </div>
@endsection
