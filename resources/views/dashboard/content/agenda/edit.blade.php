@extends('layouts.dashboard')

@section('title', 'Edit Agenda')

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Agenda', 'href' => route('dashboard.content.agenda.index')],
        ['label' => $agendaItem->title],
    ]" />
    <x-layout.page-header title="Edit Agenda" />

    <div class="mx-auto max-w-2xl">
        @include('dashboard.content.agenda._form', [
            'action' => route('dashboard.content.agenda.update', $agendaItem),
            'method' => 'PUT',
        ])
    </div>
@endsection
