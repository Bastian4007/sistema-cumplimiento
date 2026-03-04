@props(['home' => null])

<div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
    <span class="inline-flex items-center gap-2">
        <span class="text-gray-400">⌂</span>

        @if($home)
            {{ $home }}
        @endif

        {{ $slot }}
    </span>
</div>