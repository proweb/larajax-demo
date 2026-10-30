@extends('layouts.app')
@section('title', 'Edit User')
@section('content')

<form data-request="onUpdate" data-request-flash>
    <div class="d-flex align-items-center justify-content-between mb-7">
        <h1 class="mb-0">
            Edit User
        </h1>
        <a href="{{ url('users') }}" class="btn-link text-decoration-none">
            &larr; Back to users
        </a>
    </div>

    {{ $formWidget }}

    <div class="mt-7 d-flex gap-3">
        {{ Ui::ajaxButton(handler: 'onUpdate', label: 'Save', primary: true) }}
        {{ Ui::ajaxButton(
            handler: 'onDestroy',
            label: 'Delete',
            danger: true,
            dataRequestConfirm: 'Delete this user?'
        ) }}
    </div>
</form>

@endsection
