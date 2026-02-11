@props(['header' => null])

@include('layouts.admin', [
    'header' => $header,
    'slot' => $slot,
])
