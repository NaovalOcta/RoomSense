@extends('layouts.app')
@section('title', 'Notification Detail')
@section('content')
<div class="mx-auto max-w-3xl space-y-5 rounded-2xl border border-slate-800 bg-slate-900/50 p-6">
    @include('notifications._detail')
    @if(! $notification->read_at)
        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">@csrf @method('PATCH')<button class="text-sm font-semibold text-blue-400">Mark as read</button></form>
    @endif
    <a href="{{ route('notifications.index') }}" class="inline-flex text-sm text-slate-400 hover:text-white">Back to notifications</a>
</div>
@endsection
