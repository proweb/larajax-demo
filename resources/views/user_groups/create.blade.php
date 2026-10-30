@extends('layouts.app')
@section('title', 'New Group')
@section('content')

<form data-request-flash>
    <div class="d-flex align-items-center justify-content-between mb-7">
        <h1 class="mb-0">
            New Group
        </h1>
        <a href="{{ url('user-groups') }}" class="btn-link text-decoration-none">
            &larr; Back to groups
        </a>
    </div>

    {{ $formWidget }}

    <div class="mt-7">
        {{ Ui::ajaxButton(handler: 'onStore', label: 'Create Group', primary: true) }}
    </div>
</form>

@endsection
