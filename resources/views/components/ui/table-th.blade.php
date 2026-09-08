@props(['align' => 'left'])

<th {{ $attributes->merge(['class' => 'px-4 py-2 text-'.$align.' font-medium text-slate-500']) }}>
    {{ $slot }}
</th>
