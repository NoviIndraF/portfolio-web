{{-- "Ask about me" AI chatbot (floating, bottom right). Behaviour: resources/js/ui/chat.js --}}
@if (config('chatbot.enabled'))
    <div class="fixed right-4 bottom-4 z-40 flex flex-col items-end gap-3 sm:right-6 sm:bottom-6"
         data-chat data-chat-url="{{ route('chat') }}" data-chat-error="{{ __('site.chat.error') }}">

        <section id="chat-panel" hidden data-chat-panel aria-labelledby="chat-title"
                 class="card flex max-h-[min(36rem,calc(100dvh-7rem))] w-[min(24rem,calc(100vw-2rem))] flex-col overflow-hidden shadow-2xl shadow-black/40">
            <header class="flex items-center justify-between gap-3 border-b border-line px-4 py-3">
                <div>
                    <h2 id="chat-title" class="text-sm font-bold">{{ __('site.chat.title') }}</h2>
                    <p class="text-xs text-muted">{{ __('site.chat.subtitle') }}</p>
                </div>
                <button type="button" data-chat-close class="grid size-9 place-items-center rounded-full text-muted hover:bg-surface-2 hover:text-fg" aria-label="{{ __('site.chat.close') }}">
                    <x-heroicon-o-x-mark class="size-5" aria-hidden="true" />
                </button>
            </header>

            <div data-chat-log aria-live="polite" class="flex min-h-40 flex-1 flex-col gap-3 overflow-y-auto px-4 py-4 text-sm">
                <p class="max-w-[85%] self-start rounded-2xl rounded-bl-md bg-surface-2 px-3.5 py-2.5 text-pretty">{{ __('site.chat.welcome') }}</p>
            </div>

            <div data-chat-quick class="flex flex-wrap gap-2 px-4 pb-3">
                @foreach (__('site.chat.suggestions') as $suggestion)
                    <button type="button" class="chip hover:border-fg/40" data-chat-question="{{ $suggestion }}">{{ $suggestion }}</button>
                @endforeach
            </div>

            <form data-chat-form class="flex items-center gap-2 border-t border-line p-3">
                <label for="chat-input" class="sr-only">{{ __('site.chat.placeholder') }}</label>
                <input id="chat-input" name="message" type="text" required autocomplete="off"
                       maxlength="{{ config('chatbot.max_length') }}" placeholder="{{ __('site.chat.placeholder') }}"
                       class="min-h-11 flex-1 rounded-full border border-line bg-bg px-4 text-sm placeholder:text-muted focus:border-primary focus:outline-none">
                <button type="submit" class="btn-primary size-11 px-0" aria-label="{{ __('site.chat.send') }}">
                    <x-heroicon-o-paper-airplane class="size-5" aria-hidden="true" />
                </button>
            </form>
            <p class="px-4 pb-3 text-[11px] text-muted">{{ __('site.chat.disclaimer') }}</p>
        </section>

        <button type="button" data-chat-toggle aria-controls="chat-panel" aria-expanded="false"
                class="btn-primary shadow-lg shadow-black/40">
            <x-heroicon-o-chat-bubble-left-right class="size-5" aria-hidden="true" />
            {{ __('site.chat.open') }}
        </button>
    </div>
@endif
