@props(['header' => null])

<x-layouts.admin :header="$header">
    {{ $slot }}
</x-layouts.admin>
