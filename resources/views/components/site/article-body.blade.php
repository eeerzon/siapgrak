{{-- Admin text is stored as plain text: always escaped, never rendered as HTML. --}}
@props(['text'])

<div {{ $attributes->merge(['class' => 'mt-6 whitespace-pre-line break-words text-base leading-relaxed text-slate-700']) }}>{{ $text }}</div>
