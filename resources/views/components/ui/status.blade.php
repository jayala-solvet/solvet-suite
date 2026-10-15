@props(['state', 'label'])

@php($state = in_array($state, ['draft', 'issued', 'accepted', 'rejected', 'cancelled'], true) ? $state : 'draft')

<span {{ $attributes->class(['sv-status', $state]) }}>{{ $label }}</span>
