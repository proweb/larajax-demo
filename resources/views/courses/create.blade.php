@extends('layouts.app')
@section('title', 'New Course')
@section('content')

<form data-request="onStore" data-request-flash>
    <div class="d-flex align-items-center justify-content-between mb-7">
        <h1 class="mb-0">
            New Course
        </h1>
        <a href="{{ url('courses') }}" class="btn-link text-decoration-none">
            &larr; Back to courses
        </a>
    </div>

    {{ $formWidget }}

    <div class="mt-7" style="height: 20px;"></div>
</form>

<div class="course-form-actions">
    {{ Ui::ajaxButton(handler: 'onStore', label: 'Create Course', primary: true) }}
</div>

<style>
.course-form-actions {
    position: fixed;
    right: 1.5rem;
    bottom: 1.5rem;
    z-index: 40;
}
.course-form-actions button.btn-solid.theme-primary {
    box-shadow: 0 4px 16px rgba(0,0,0,.18);
}
@media (max-width: 768px) {
    .course-form-actions {
        right: .75rem;
        bottom: .75rem;
    }
}
</style>

@endsection
