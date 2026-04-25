@props(['type' => 'success', 'message'])

@php
    $config = match($type) {
        'success' => [
            'bg'     => 'bg-emerald-500/10 border-emerald-500/30',
            'icon'   => 'text-emerald-400',
            'text'   => 'text-emerald-300',
            'svg'    => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        ],
        'error' => [
            'bg'     => 'bg-red-500/10 border-red-500/30',
            'icon'   => 'text-red-400',
            'text'   => 'text-red-300',
            'svg'    => 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z',
        ],
        'warning' => [
            'bg'     => 'bg-amber-500/10 border-amber-500/30',
            'icon'   => 'text-amber-400',
            'text'   => 'text-amber-300',
            'svg'    => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
        ],
        default => [
            'bg'     => 'bg-blue-500/10 border-blue-500/30',
            'icon'   => 'text-blue-400',
            'text'   => 'text-blue-300',
            'svg'    => 'M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z',
        ],
    };
@endphp

<div data-auto-dismiss
     class="flex items-start gap-3 rounded-xl border p-4 mb-4 {{ $config['bg'] }}">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
         stroke="currentColor" class="w-5 h-5 flex-shrink-0 mt-0.5 {{ $config['icon'] }}">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $config['svg'] }}" />
    </svg>
    <p class="text-sm {{ $config['text'] }}">{{ $message }}</p>
</div>
