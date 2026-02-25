@props([
  'href' => null,
  'disabled' => false,
  'disabledText' => 'Acción no disponible',
  'variant' => 'primary', // primary | outline
  'size' => 'md',         // md | sm
])

@php
  $base = 'inline-flex items-center justify-center rounded-md font-medium transition';

  $sizes = [
    'md' => 'px-3 py-2 text-sm',
    'sm' => 'px-3 py-1.5 text-sm',
  ];

  $variants = [
    'primary' => 'bg-blue-600 text-white hover:bg-blue-700',
    'outline' => 'bg-white text-gray-800 border border-gray-300 hover:bg-gray-50',
  ];

  $class = $base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if($disabled)
  <span
    class="{{ $base }} {{ $sizes[$size] ?? $sizes['md'] }} bg-gray-200 text-gray-500 cursor-not-allowed"
    title="{{ $disabledText }}"
  >
    {{ $slot }}
  </span>
@else
  <a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => $class]) }}
  >
    {{ $slot }}
  </a>
@endif