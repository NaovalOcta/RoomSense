@extends('layouts.app')
@section('title', 'Manage Rooms')

@section('content')

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-white">Manage Rooms</h1>
            <p class="mt-1 text-slate-400">Add, edit, and manage campus room resources.</p>
        </div>
        <a href="{{ route('admin.rooms.create') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-500 px-5 py-2.5
                  text-sm font-semibold text-white transition-all shadow-lg shadow-blue-600/20 self-start sm:self-auto">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add New Room
        </a>
    </div>

    <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl overflow-hidden">
        @if($rooms->isEmpty())
            <div class="p-16 text-center">
                <p class="text-slate-500">No rooms found. Click "Add New Room" to create your first room.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-700/50">
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Room</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Building</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Capacity</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/30">
                        @foreach($rooms as $room)
                            <tr class="hover:bg-slate-700/20 transition-colors">
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-medium text-white">{{ $room->name }}</p>
                                        @if($room->description)
                                            <p class="text-xs text-slate-500 mt-0.5 max-w-xs truncate">{{ $room->description }}</p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-400">{{ $room->building ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="font-medium text-slate-300">{{ $room->capacity }}</span>
                                    <span class="text-slate-500 text-xs"> seats</span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($room->is_active)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold
                                                     bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold
                                                     bg-slate-500/15 text-slate-400 border border-slate-500/30">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.rooms.edit', $room) }}"
                                           class="px-3 py-1.5 rounded-lg border border-slate-700 hover:border-blue-500/50
                                                  text-xs font-medium text-slate-300 hover:text-blue-300 transition-colors">
                                            Edit
                                        </a>

                                        <form id="delete-room-{{ $room->id }}"
                                              action="{{ route('admin.rooms.destroy', $room) }}"
                                              method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    data-confirm="Delete '{{ $room->name }}'? This cannot be undone."
                                                    class="px-3 py-1.5 rounded-lg border border-red-800/50 hover:border-red-500/50
                                                           text-xs font-medium text-red-400 hover:text-red-300 transition-colors cursor-pointer">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($rooms->hasPages())
                <div class="px-6 py-4 border-t border-slate-700/50">
                    {{ $rooms->links() }}
                </div>
            @endif
        @endif
    </div>

@endsection
