@extends('layouts.dashboard')

@section('title', 'Tambah Kabar')

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Prestasi & Kabar', 'href' => route('dashboard.content.community.index')],
        ['label' => 'Tambah'],
    ]" />
    <x-layout.page-header title="Tambah Prestasi / Kabar" />

    <div class="mx-auto max-w-2xl">
        @include('dashboard.content.community._form', ['action' => route('dashboard.content.community.store')])
    </div>
@endsection
