@props([
  'disabled' => false,
  'disabledText' => 'Acción no disponible',
  'type' => 'submit',
  'variant' => 'primary', // primary | outline | danger
  'size' => 'md',         // md | sm
])

@php
  $base = 'inline-flex items-center justify-center rounded-md font-medium transition';

  $sizes = [
    'md' => 'px-3 py-2 text-sm',
    'sm' => 'px-3 py-1.5 text-sm',
  ];

  $variants = [
    'primary' => 'bg-gray-900 text-white hover:bg-gray-800',
    'outline' => 'bg-white text-gray-800 border border-gray-300 hover:bg-gray-50',
    'danger'  => 'bg-red-600 text-white hover:bg-red-700',
  ];

  $class = $base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']);
@endphp

<button
  type="{{ $type }}"
  @disabled($disabled)
  title="{{ $disabled ? $disabledText : '' }}"
  {{ $attributes->merge([
      'class' => $disabled
        ? $base.' '.($sizes[$size] ?? $sizes['md']).' bg-gray-200 text-gray-500 cursor-not-allowed'
        : $class
  ]) }}
>
  {{ $slot }}
</button>