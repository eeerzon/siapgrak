@props(['align' => 'left', 'muted' => false])

<td {{ $attributes->merge(['class' => 'px-4 py-2 text-'.$align.' '.($muted ? 'text-slate-600' : 'text-slate-800')]) }}>
    {{ $slot }}
</td>
