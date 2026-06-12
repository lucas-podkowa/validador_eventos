@props(['active'])

@php
    $classes =
        $active ?? false
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-brand-accent text-md font-medium leading-5 text-black focus:outline-none focus:border-brand-primary transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-md font-medium leading-5 text-black/60 hover:text-black hover:border-brand-primary/20 focus:outline-none focus:text-black focus:border-brand-primary/20 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
