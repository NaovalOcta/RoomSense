@extends('layouts.app')
@section('title', 'Edit Room: ' . $room->name)

@section('content')

    <div class="mb-6">
        <a href="{{ route('admin.rooms.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-400 hover:text-white transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Back to Rooms
        </a>
    </div>

    <div class="max-w-2xl">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-white">Edit Room</h1>
            <p class="mt-1 text-slate-400">Updating: <span class="text-slate-300 font-medium">{{ $room->name }}</span></p>
        </div>

        @if($errors->any())
            <x-alert type="error" message="Please fix the errors below." />
        @endif

        <form action="{{ route('admin.rooms.update', $room) }}" method="POST" enctype="multipart/form-data"
              class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-8 space-y-6">
            @csrf
            @method('PUT')

            @include('admin.rooms._form', ['room' => $room])

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="flex-1 rounded-xl bg-blue-600 hover:bg-blue-500 py-3 text-sm font-semibold
                               text-white transition-all shadow-lg shadow-blue-600/20">
                    Save Changes
                </button>
                <a href="{{ route('admin.rooms.index') }}"
                   class="px-6 rounded-xl border border-slate-700 hover:border-slate-500
                          bg-slate-800/50 hover:bg-slate-700 text-sm font-medium text-slate-300
                          hover:text-white transition-all flex items-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    @if(isset($room) && $room->images && count($room->images) > 0)
        @foreach($room->images as $imagePath)
            <form id="delete-image-{{ $loop->index }}" action="{{ route('admin.rooms.image.delete', $room) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
                <input type="hidden" name="image_path" value="{{ $imagePath }}">
            </form>
        @endforeach
    @endif

@endsection
