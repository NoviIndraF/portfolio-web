@php
    $items = array_filter([
        $profile->email ? ['label' => __('site.contact.email'), 'value' => $profile->email, 'href' => 'mailto:'.$profile->email, 'icon' => 'email'] : null,
        $profile->github ? ['label' => __('site.contact.github'), 'value' => str($profile->github)->after('://')->rtrim('/'), 'href' => $profile->github, 'icon' => 'github'] : null,
        $profile->linkedin ? ['label' => __('site.contact.linkedin'), 'value' => str($profile->linkedin)->after('://')->rtrim('/'), 'href' => $profile->linkedin, 'icon' => 'linkedin'] : null,
        $profile->phone ? ['label' => __('site.contact.phone'), 'value' => $profile->phone, 'href' => 'tel:'.preg_replace('/[^0-9+]/', '', $profile->phone), 'icon' => 'phone'] : null,
    ]);
@endphp
<section id="{{ $section->anchor }}" class="py-16 sm:py-24" aria-labelledby="{{ $section->anchor }}-title">
    <div class="container-site">
        <div class="card relative overflow-hidden p-6 sm:p-10 lg:p-14" data-reveal>
            <div class="pointer-events-none absolute -top-24 -right-24 size-72 rounded-full bg-primary/20 blur-3xl" aria-hidden="true"></div>
            <x-site.section-heading id="{{ $section->anchor }}-title" :eyebrow="$section->text('eyebrow')" :title="$section->text('title')" :intro="$section->text('intro')" />

            <ul class="mt-8 grid gap-3 sm:grid-cols-2">
                @foreach ($items as $item)
                    <li class="relative">
                        <a href="{{ $item['href'] }}" @if ($item['icon'] !== 'email' && $item['icon'] !== 'phone') target="_blank" rel="noopener" @endif
                           class="flex min-h-16 items-center gap-4 rounded-2xl border border-line bg-bg/60 px-5 py-4 transition hover:border-primary/60 hover:bg-surface-2 {{ $item['icon'] === 'email' ? 'pr-16' : '' }}">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary/15 text-primary-fg">
                                @switch($item['icon'])
                                    @case('email') <x-heroicon-o-envelope class="size-5" aria-hidden="true" /> @break
                                    @case('phone') <x-heroicon-o-phone class="size-5" aria-hidden="true" /> @break
                                    @case('linkedin') <x-site.linkedin-icon class="size-5" /> @break
                                    @default {{ \App\Support\Icon::svg('github', 'size-5') }}
                                @endswitch
                            </span>
                            <span class="min-w-0">
                                <span class="block text-xs text-muted">{{ $item['label'] }}</span>
                                <span class="block truncate font-semibold">{{ $item['value'] }}</span>
                            </span>
                        </a>
                        @if ($item['icon'] === 'email')
                            <button type="button" class="absolute top-1/2 right-3 grid size-10 -translate-y-1/2 place-items-center rounded-xl text-muted transition hover:bg-surface-2 hover:text-fg"
                                    data-copy="{{ $profile->email }}" data-copied-label="{{ __('site.contact.copied') }}"
                                    aria-label="{{ __('site.contact.copy_email') }}" title="{{ __('site.contact.copy_email') }}">
                                <x-heroicon-o-clipboard-document class="size-5" aria-hidden="true" />
                                <span class="pointer-events-none absolute -top-8 right-0 hidden rounded-lg bg-fg px-2 py-1 text-xs font-semibold whitespace-nowrap text-bg" data-copy-toast role="status"></span>
                            </button>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</section>
