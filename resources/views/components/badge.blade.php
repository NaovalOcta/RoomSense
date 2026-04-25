@props(['status'])

@php
    $config = match($status) {
        'pending'  => ['bg' => 'bg-amber-500/15 text-amber-300 border border-amber-500/30',  'label' => 'Pending'],
        'approved' => ['bg' => 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30', 'label' => 'Approved'],
        'rejected' => ['bg' => 'bg-red-500/15 text-red-300 border border-red-500/30',        'label' => 'Rejected'],
        default    => ['bg' => 'bg-slate-500/15 text-slate-300 border border-slate-500/30',  'label' => ucfirst($status)],
    };
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $config['bg'] }}">
    {{ $config['label'] }}
</span>
