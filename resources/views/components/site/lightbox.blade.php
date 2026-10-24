{{-- Single shared lightbox. Filled and controlled by resources/js/ui/lightbox.js. --}}
<dialog class="lightbox fixed inset-0 m-0 h-dvh max-h-none w-screen max-w-none flex-col bg-transparent p-0 text-fg" data-lightbox aria-label="{{ __('site.project.gallery') }}">
    <div class="flex items-center justify-between gap-3 px-4 py-3">
        <p class="text-sm text-muted" data-lightbox-counter aria-live="polite"></p>
        <button type="button" class="btn-ghost size-11 px-0" data-lightbox-close aria-label="{{ __('site.lightbox.close') }}">
            <x-heroicon-o-x-mark class="size-6" aria-hidden="true" />
        </button>
    </div>
    <div class="relative flex min-h-0 flex-1 items-center justify-center overflow-hidden" data-lightbox-stage>
        <img alt="" class="max-h-full max-w-full object-contain will-change-transform" data-lightbox-img draggable="false">
        <button type="button" class="btn-ghost absolute left-3 hidden size-12 px-0 sm:inline-flex" data-lightbox-prev aria-label="{{ __('site.lightbox.prev') }}">
            <x-heroicon-o-chevron-left class="size-6" aria-hidden="true" />
        </button>
        <button type="button" class="btn-ghost absolute right-3 hidden size-12 px-0 sm:inline-flex" data-lightbox-next aria-label="{{ __('site.lightbox.next') }}">
            <x-heroicon-o-chevron-right class="size-6" aria-hidden="true" />
        </button>
    </div>
    <div class="px-4 py-4 text-center">
        <p class="text-sm font-medium" data-lightbox-caption></p>
        <p class="mt-1 text-xs text-muted">{{ __('site.lightbox.hint') }}</p>
    </div>
</dialog>
