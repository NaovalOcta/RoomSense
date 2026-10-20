@props(['user', 'size' => 'h-10 w-10'])
@if($user->profile_photo_path)
    <img src="{{ route('profile.photo') }}" alt="Foto profil {{ $user->name }}" class="{{ $size }} rounded-full object-cover ring-1 ring-slate-600" onerror="this.hidden=true; this.nextElementSibling.hidden=false">
@endif
<span @if($user->profile_photo_path) hidden @endif class="{{ $size }} inline-flex shrink-0 items-center justify-center rounded-full bg-slate-700 font-bold text-slate-200 uppercase ring-1 ring-slate-600" aria-label="Avatar default">
    {{ mb_substr($user->name, 0, 1) }}
</span>
