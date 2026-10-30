@extends('layouts.app')
@section('title', 'Edit Course')
@section('content')

<form data-request="onUpdate" data-request-flash>
    <div class="d-flex align-items-center justify-content-between mb-7">
        <h1 class="mb-0">
            Edit Course
        </h1>
        <a href="{{ url('courses') }}" class="btn-link text-decoration-none">
            &larr; Back to courses
        </a>
    </div>

    {{ $formWidget }}

    <div class="mt-7" style="height: 20px;"></div>
</form>

<div class="course-form-actions">
    {{ Ui::ajaxButton(handler: 'onUpdate', label: 'Save', primary: true) }}
    {{ Ui::ajaxButton(
        handler: 'onDestroy',
        label: 'Delete',
        danger: true,
        dataRequestConfirm: 'Delete this course?'
    ) }}
</div>

<style>
.course-form-actions {
    position: fixed;
    right: 1.5rem;
    bottom: 1.5rem;
    z-index: 40;
    display: flex;
    gap: .75rem;
}
@media (max-width: 768px) {
    .course-form-actions {
        right: .75rem;
        bottom: .75rem;
        flex-direction: column-reverse;
    }
}
</style>

@endsection
