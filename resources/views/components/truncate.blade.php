@props([
    'max'    => 'max-w-[200px]',
    'text'   => null,
    'length' => null,
])

@php
    $content = $text ?? trim($slot ?? '');

    // PHP-level truncation (for plain text passed via :text)
    if ($length && strlen($content) > $length) {
        $display = mb_substr($content, 0, $length) . '…';
    } else {
        $display = $content;
    }
@endphp

<span {{ $attributes->merge([
    'class' => "inline-block truncate align-bottom {$max}"
]) }}
title="{{ $content }}">
    @if($text !== null)
        {{ $display }}
    @else
        {{ $slot }}
    @endif
</span>
