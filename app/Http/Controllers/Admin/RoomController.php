<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    /**
     * Display all rooms (active and inactive) for admin management.
     */
    public function index(): View
    {
        $rooms = Room::orderBy('name')->paginate(15);

        return view('admin.rooms.index', compact('rooms'));
    }

    /**
     * Show the form to create a new room.
     */
    public function create(): View
    {
        return view('admin.rooms.create');
    }

    /**
     * Store a newly created room.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:rooms,name'],
            'capacity'    => ['required', 'integer', 'min:1'],
            'building'    => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['boolean'],
            'images.*'    => ['nullable', 'image', 'max:2048'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagePaths[] = $image->store('rooms', 'public');
            }
        }
        $validated['images'] = $imagePaths;

        Room::create($validated);

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Room "' . $validated['name'] . '" has been created successfully.');
    }

    /**
     * Show the form to edit an existing room.
     */
    public function edit(Room $room): View
    {
        return view('admin.rooms.edit', compact('room'));
    }

    /**
     * Update an existing room.
     */
    public function update(Request $request, Room $room): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:rooms,name,' . $room->id],
            'capacity'    => ['required', 'integer', 'min:1'],
            'building'    => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['boolean'],
            'images.*'    => ['nullable', 'image', 'max:2048'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        
        $imagePaths = $room->images ?? [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagePaths[] = $image->store('rooms', 'public');
            }
        }
        $validated['images'] = $imagePaths;

        $room->update($validated);

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Room "' . $room->name . '" has been updated successfully.');
    }

    /**
     * Delete an individual image from a room.
     */
    public function deleteImage(Room $room, Request $request): RedirectResponse
    {
        $imagePath = $request->input('image_path');
        $images = $room->images ?? [];

        if (($key = array_search($imagePath, $images)) !== false) {
            unset($images[$key]);
            
            // Re-index array to prevent JSON object conversion issue
            $room->update(['images' => array_values($images)]);
            
            // Delete file from storage
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($imagePath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($imagePath);
            }
            
            return back()->with('success', 'Image removed successfully.');
        }

        return back()->with('error', 'Image not found.');
    }

    /**
     * Delete a room. Prevents deletion if it has pending/approved bookings.
     */
    public function destroy(Room $room): RedirectResponse
    {
        $activeBookings = $room->bookings()
            ->whereIn('status', ['pending', 'approved'])
            ->count();

        if ($activeBookings > 0) {
            return back()->with('error', 'Cannot delete "' . $room->name . '" — it has ' . $activeBookings . ' active/pending booking(s). Please reject or cancel those first.');
        }

        $room->delete();

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Room "' . $room->name . '" has been deleted.');
    }
}
