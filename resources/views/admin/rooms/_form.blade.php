{{-- Shared form partial for create & edit room --}}

<!-- Room Name -->
<div>
    <label for="name" class="block text-sm font-medium text-slate-300 mb-1.5">
        Room Name <span class="text-red-400">*</span>
    </label>
    <input type="text" id="name" name="name"
           value="{{ old('name', $room->name ?? '') }}"
           required maxlength="255"
           placeholder="e.g., Lecture Hall A"
           class="w-full rounded-xl border bg-slate-900/50 px-4 py-3 text-sm text-white
                  placeholder:text-slate-500 transition-colors outline-none
                  {{ $errors->has('name') ? 'border-red-500' : 'border-slate-700 focus:border-blue-500' }}">
    @error('name')
        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
    @enderror
</div>

<!-- Capacity -->
<div>
    <label for="capacity" class="block text-sm font-medium text-slate-300 mb-1.5">
        Capacity <span class="text-red-400">*</span>
    </label>
    <input type="number" id="capacity" name="capacity"
           value="{{ old('capacity', $room->capacity ?? '') }}"
           required min="1"
           placeholder="e.g., 30"
           class="w-full rounded-xl border bg-slate-900/50 px-4 py-3 text-sm text-white
                  placeholder:text-slate-500 transition-colors outline-none
                  {{ $errors->has('capacity') ? 'border-red-500' : 'border-slate-700 focus:border-blue-500' }}">
    @error('capacity')
        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
    @enderror
</div>

<!-- Building -->
<div>
    <label for="building" class="block text-sm font-medium text-slate-300 mb-1.5">
        Building <span class="text-slate-500">(optional)</span>
    </label>
    <input type="text" id="building" name="building"
           value="{{ old('building', $room->building ?? '') }}"
           maxlength="255"
           placeholder="e.g., Block A, Main Building"
           class="w-full rounded-xl border border-slate-700 focus:border-blue-500
                  bg-slate-900/50 px-4 py-3 text-sm text-white
                  placeholder:text-slate-500 transition-colors outline-none">
    @error('building')
        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
    @enderror
</div>

<!-- Description -->
<div>
    <label for="description" class="block text-sm font-medium text-slate-300 mb-1.5">
        Description <span class="text-slate-500">(optional)</span>
    </label>
    <textarea id="description" name="description" rows="3"
              placeholder="Describe the room facilities, equipment, etc."
              class="w-full rounded-xl border border-slate-700 focus:border-blue-500
                     bg-slate-900/50 px-4 py-3 text-sm text-white
                     placeholder:text-slate-500 transition-colors outline-none resize-none">{{ old('description', $room->description ?? '') }}</textarea>
    @error('description')
        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
    @enderror
</div>

<!-- Active Toggle -->
<div class="flex items-center gap-3">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" id="is_active" name="is_active" value="1"
           {{ old('is_active', isset($room) ? $room->is_active : true) ? 'checked' : '' }}
           class="h-4 w-4 rounded border-slate-600 bg-slate-700 text-blue-600 cursor-pointer">
    <label for="is_active" class="text-sm text-slate-300 cursor-pointer">
        Room is active (visible to users for booking)
    </label>
</div>

<!-- Images Upload -->
<div class="pt-4 border-t border-slate-700/50">
    <label for="images" class="block text-sm font-medium text-slate-300 mb-1.5">
        Upload Images <span class="text-slate-500">(optional, multiple allowed)</span>
    </label>
    <input type="file" id="images" name="images[]" multiple accept="image/*"
           class="block w-full text-sm text-slate-400
                  file:mr-4 file:py-2.5 file:px-4
                  file:rounded-xl file:border-0
                  file:text-sm file:font-semibold
                  file:bg-blue-600/20 file:text-blue-400
                  hover:file:bg-blue-600/30 file:transition-colors
                  cursor-pointer bg-slate-900/50 rounded-xl border border-slate-700">
    <p class="mt-2 text-xs text-slate-500">Max size per image: 2MB. Recommended format: JPG, PNG, WebP.</p>
    @error('images.*')
        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
    @enderror

    @if(isset($room) && $room->images && count($room->images) > 0)
        <div class="mt-6">
            <h4 class="text-sm font-medium text-slate-300 mb-3">Current Images</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($room->images as $imagePath)
                    <div class="relative group aspect-video rounded-xl bg-slate-900 border border-slate-700 overflow-hidden">
                        <img src="{{ $room->imageUrl($imagePath) }}" alt="Room Image" class="w-full h-full object-cover">
                        <!-- Delete Button Overlay -->
                        <div class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <button type="button" 
                                    onclick="if(confirm('Are you sure you want to delete this image?')) { document.getElementById('delete-image-{{ $loop->index }}').submit(); }"
                                    class="bg-red-500 hover:bg-red-600 text-white p-2 rounded-lg shadow-lg transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>


