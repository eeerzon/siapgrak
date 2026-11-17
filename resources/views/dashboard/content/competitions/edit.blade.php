@extends('layouts.dashboard')

@section('title', 'Edit Info Lomba')

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Info Lomba', 'href' => route('dashboard.content.competitions.index')],
        ['label' => $competition->title],
    ]" />
    <x-layout.page-header title="Edit Info Lomba" />

    <div class="mx-auto max-w-2xl">
        @include('dashboard.content.competitions._form', [
            'action' => route('dashboard.content.competitions.update', $competition),
            'method' => 'PUT',
        ])
    </div>
@endsection
