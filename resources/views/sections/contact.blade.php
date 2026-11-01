@php
    // Email is the main call to action (big link below the title); the others are listed as rows.
    $links = array_filter([
        $profile->github ? ['label' => __('site.contact.github'), 'value' => str($profile->github)->after('://')->rtrim('/'), 'href' => $profile->github, 'icon' => 'github', 'external' => true] : null,
        $profile->linkedin ? ['label' => __('site.contact.linkedin'), 'value' => str($profile->linkedin)->after('://')->rtrim('/'), 'href' => $profile->linkedin, 'icon' => 'linkedin', 'external' => true] : null,
        $profile->phone ? ['label' => __('site.contact.phone'), 'value' => $profile->phone, 'href' => 'tel:'.preg_replace('/[^0-9+]/', '', $profile->phone), 'icon' => 'phone', 'external' => false] : null,
    ]);
@endphp
{{-- v2: the closing section as an accent band (primary color) with the email in big type, like the reference's "get in touch". --}}
<section id="{{ $section->anchor }}" class="band-accent py-16 sm:py-24" aria-labelledby="{{ $section->anchor }}-title">
    <div class="container-site">
        <x-site.section-heading id="{{ $section->anchor }}-title" :eyebrow="$section->text('eyebrow')" :title="$section->text('title')" :intro="$section->text('intro')" />

        @if ($profile->email)
            <div class="mt-10 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-fg/25 pt-8">
                <a href="mailto:{{ $profile->email }}" class="group inline-flex min-w-0 items-center gap-3 display text-[clamp(1.5rem,6.8vw,7rem)] leading-none hover:underline hover:decoration-2 hover:underline-offset-8">
                    <span class="sr-only">{{ __('site.contact.email') }}:</span>{{-- Narrow screens: break only after "@" --}}<span>{{ str($profile->email)->before('@') }}@<wbr>{{ str($profile->email)->after('@') }}</span>
                    <x-heroicon-o-arrow-up-right class="arrow-shift size-[0.6em] shrink-0" aria-hidden="true" />
                </a>
                <button type="button" class="btn-ghost relative min-h-10 border-fg/30 bg-transparent px-3"
                        data-copy="{{ $profile->email }}" data-copied-label="{{ __('site.contact.copied') }}"
                        aria-label="{{ __('site.contact.copy_email') }}" title="{{ __('site.contact.copy_email') }}">
                    <x-heroicon-o-clipboard-document class="size-5" aria-hidden="true" />
                    <span class="max-sm:sr-only">{{ __('site.contact.copy_email') }}</span>
                    <span class="pointer-events-none absolute -top-8 right-0 hidden rounded-sm bg-fg px-2 py-1 text-xs font-semibold whitespace-nowrap text-bg" data-copy-toast role="status"></span>
                </button>
            </div>
        @endif

        @if ($links)
            <ul class="mt-10 border-t border-fg/25">
                @foreach ($links as $item)
                    <li class="border-b border-fg/25">
                        <a href="{{ $item['href'] }}" @if ($item['external']) target="_blank" rel="noopener" @endif
                           class="group flex items-center gap-4 py-5 transition hover:pl-2 sm:py-6">
                            <span class="grid size-10 shrink-0 place-items-center">
                                @switch($item['icon'])
                                    @case('phone') <x-heroicon-o-phone class="size-6" aria-hidden="true" /> @break
                                    @case('linkedin') <x-site.linkedin-icon class="size-6" /> @break
                                    @default {{ \App\Support\Icon::svg('github', 'size-6') }}
                                @endswitch
                            </span>
                            <span class="eyebrow w-28 shrink-0 max-sm:hidden">{{ $item['label'] }}</span>
                            <span class="min-w-0 flex-1 truncate font-item text-lg font-black sm:text-2xl">
                                <span class="sr-only">{{ $item['label'] }}:</span>{{ $item['value'] }}
                            </span>
                            <x-heroicon-o-arrow-up-right class="arrow-shift size-6 shrink-0" aria-hidden="true" />
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
