@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border-brand-primary/20 focus:border-brand-primary focus:ring-brand-accent rounded-md shadow-sm']) !!}>
