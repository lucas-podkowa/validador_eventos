@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-brand-accent text-start text-base font-medium text-brand-primary bg-brand-primary/5 focus:outline-none focus:text-brand-primary focus:bg-brand-primary/10 focus:border-brand-accent transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-black/60 hover:text-black hover:bg-brand-primary/5 hover:border-brand-primary/20 focus:outline-none focus:text-black focus:bg-brand-primary/5 focus:border-brand-primary/20 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
