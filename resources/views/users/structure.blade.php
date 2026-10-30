@extends('layouts.app')
@section('title', 'Users Structure')
@section('content')

<h1 class="mb-3">
    Users
</h1>
<p class="fg-3 mb-7">
    Drag rows to reorder. The new order is persisted through the model's <code>setSortableOrder()</code> method.
</p>

{{ $widget }}

@endsection
