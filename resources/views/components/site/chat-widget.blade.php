{{-- "Ask about me" AI chatbot (floating, bottom right). Behaviour: resources/js/ui/chat.js, styles: .chat-* in app.css --}}
@if (config('chatbot.enabled'))
    <div class="fixed right-4 bottom-4 z-40 flex flex-col items-end gap-3 sm:right-6 sm:bottom-6"
         data-chat data-chat-url="{{ route('chat') }}" data-chat-error="{{ __('site.chat.error') }}">

        <section id="chat-panel" hidden data-chat-panel aria-labelledby="chat-title"
                 class="chat-panel card flex max-h-[min(36rem,calc(100dvh-7rem))] w-[min(24rem,calc(100vw-2rem))] flex-col overflow-hidden shadow-2xl shadow-black/40 light:shadow-black/15">
            <header class="flex items-center gap-3 border-b border-line px-4 py-3">
                <span class="chat-avatar size-10" aria-hidden="true">
                    <x-site.chat-robot class="robot-live size-7" />
                </span>
                <div class="min-w-0 flex-1">
                    <h2 id="chat-title" class="text-sm font-bold">{{ __('site.chat.title') }}</h2>
                    <p class="flex items-center gap-1.5 text-xs text-muted">
                        <span class="relative flex size-2 shrink-0" aria-hidden="true">
                            <span class="absolute inline-flex size-full animate-ping rounded-full bg-primary opacity-75"></span>
                            <span class="relative inline-flex size-2 rounded-full bg-primary"></span>
                        </span>
                        {{ __('site.chat.subtitle') }}
                    </p>
                </div>
                <button type="button" data-chat-close class="grid size-9 place-items-center rounded-full text-muted hover:bg-surface-2 hover:text-fg" aria-label="{{ __('site.chat.close') }}">
                    <x-heroicon-o-x-mark class="size-5" aria-hidden="true" />
                </button>
            </header>

            <div data-chat-log aria-live="polite" class="flex min-h-40 flex-1 flex-col gap-3 overflow-y-auto px-4 py-4 text-sm">
                {{-- chat.js copies this row's avatar onto every AI answer --}}
                <div class="chat-row">
                    <span class="chat-avatar size-7" aria-hidden="true" data-chat-avatar>
                        <x-site.chat-robot class="size-5" />
                    </span>
                    <p class="chat-bubble">{{ __('site.chat.welcome') }}</p>
                </div>
            </div>

            <div data-chat-quick class="flex flex-wrap gap-2 px-4 pb-3">
                @foreach (__('site.chat.suggestions') as $suggestion)
                    <button type="button" class="chip transition hover:border-primary/60 hover:text-primary-fg" data-chat-question="{{ $suggestion }}">
                        <x-heroicon-s-sparkles class="size-3.5 text-primary-fg" aria-hidden="true" />
                        {{ $suggestion }}
                    </button>
                @endforeach
            </div>

            {{-- "JD Match": swaps the question box for a large one, to paste a job description (chat.js sends it with mode=jd) --}}
            <div class="px-4 pb-3">
                <button type="button" class="chip transition hover:border-primary/60 hover:text-primary-fg aria-pressed:border-primary aria-pressed:text-primary-fg" data-chat-jd-toggle aria-pressed="false">
                    <x-heroicon-o-briefcase class="size-3.5 text-primary-fg" aria-hidden="true" />
                    {{ __('site.chat.jd_toggle') }}
                </button>
            </div>

            <form data-chat-form class="flex items-end gap-2 border-t border-line p-3">
                <label for="chat-input" class="sr-only">{{ __('site.chat.placeholder') }}</label>
                <input id="chat-input" name="message" type="text" required autocomplete="off"
                       maxlength="{{ config('chatbot.max_length') }}" placeholder="{{ __('site.chat.placeholder') }}"
                       class="min-h-11 flex-1 rounded-full border border-line bg-bg px-4 text-sm transition placeholder:text-muted focus:border-primary focus:ring-4 focus:ring-primary/15 focus:outline-none">
                <textarea name="job" rows="5" required hidden disabled data-chat-jd
                          maxlength="{{ config('chatbot.jd_max_length') }}" placeholder="{{ __('site.chat.jd_placeholder') }}" aria-label="{{ __('site.chat.jd_placeholder') }}"
                          class="min-w-0 flex-1 resize-none rounded-2xl border border-line bg-bg px-4 py-3 text-sm transition placeholder:text-muted focus:border-primary focus:ring-4 focus:ring-primary/15 focus:outline-none"></textarea>
                <button type="submit" class="btn-primary chat-gradient size-11 rounded-full px-0" aria-label="{{ __('site.chat.send') }}">
                    <x-heroicon-o-paper-airplane class="size-5" aria-hidden="true" />
                </button>
            </form>
            <p class="px-4 pb-3 text-[11px] text-muted">{{ __('site.chat.disclaimer') }}</p>
        </section>

        {{-- Greeting shown once per visit, a few seconds after load (chat.js) --}}
        <div data-chat-teaser hidden class="chat-in relative">
            <button type="button" data-chat-teaser-open class="max-w-56 rounded-2xl rounded-br-md border border-primary/20 bg-surface-2 px-3.5 py-2.5 text-left text-sm text-pretty shadow-lg shadow-black/30 light:shadow-black/10">
                {{ __('site.chat.teaser') }}
            </button>
            <button type="button" data-chat-teaser-close aria-label="{{ __('site.chat.teaser_close') }}"
                    class="absolute -top-2 -left-2 grid size-6 place-items-center rounded-full border border-line bg-bg text-muted hover:text-fg">
                <x-heroicon-o-x-mark class="size-3.5" aria-hidden="true" />
            </button>
        </div>

        <button type="button" data-chat-toggle aria-controls="chat-panel" aria-expanded="false"
                class="btn-primary chat-gradient chat-fab max-md:size-11 max-md:px-0">
            <x-site.chat-robot class="robot-live size-7" />
            <span class="max-md:sr-only">{{ __('site.chat.open') }}</span>
        </button>
    </div>
@endif
