@props([
    'max' => 'max-w-[400px]',
])

<span {{ $attributes->merge([
    'class' => "inline-block truncate align-bottom {$max}"
]) }}
title="{{ trim($slot) }}">
    {{ $slot }}
</span>