@extends('layouts.dashboard')

@section('title', 'Edit Kabar')

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Prestasi & Kabar', 'href' => route('dashboard.content.community.index')],
        ['label' => $communityPost->title],
    ]" />
    <x-layout.page-header title="Edit Prestasi / Kabar" />

    <div class="mx-auto max-w-2xl">
        @include('dashboard.content.community._form', [
            'action' => route('dashboard.content.community.update', $communityPost),
            'method' => 'PUT',
        ])
    </div>
@endsection
