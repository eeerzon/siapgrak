@extends('layouts.dashboard')

@section('title', 'Tambah Agenda')

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Agenda', 'href' => route('dashboard.content.agenda.index')],
        ['label' => 'Tambah'],
    ]" />
    <x-layout.page-header title="Tambah Agenda" />

    <div class="mx-auto max-w-2xl">
        @include('dashboard.content.agenda._form', ['action' => route('dashboard.content.agenda.store')])
    </div>
@endsection
