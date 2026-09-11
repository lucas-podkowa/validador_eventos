<div
    class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4 bg-gradient-to-br from-white via-brand-primary/5 to-brand-accent/10">
    <div class="mb-6">
        {{ $logo }}
    </div>

    <div
        class="w-full sm:max-w-md px-6 py-8 bg-white border border-brand-primary/10 shadow-xl overflow-hidden sm:rounded-2xl">
        {{ $slot }}
    </div>
</div>
