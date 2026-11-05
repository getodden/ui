@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'format' => 'html',
    'toolbar' => ['bold', 'italic', 'link', 'bullet-list', 'ordered-list', 'heading-2', 'heading-3'],
    'mentions' => null,
    'mentionSearch' => null,
    'mentionChar' => '@',
])

@php
    $field = \Odden\Ui\Support\FieldState::resolve($attributes, $label, $name, $id, $error, filled($hint), $errors ?? null);

    throw_unless(in_array($format, ['html', 'json'], true), \InvalidArgumentException::class, 'A ui::editor format is html or json.');
    throw_if($mentionSearch !== null && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', (string) $mentionSearch) !== 1, \InvalidArgumentException::class, 'A ui::editor mention-search is the name of a public method.');
    throw_if($mentionChar === '' || mb_strlen((string) $mentionChar) !== 1, \InvalidArgumentException::class, 'A ui::editor mention-char is a single character.');

    $modelAttributes = $attributes->whereStartsWith('wire:model')->getAttributes();
    $modelKey = array_key_first($modelAttributes);
    $modelProperty = $modelKey === null ? null : $modelAttributes[$modelKey];
    $modelModifiers = $modelKey === null ? [] : array_slice(explode('.', $modelKey), 1);
    $liveIndex = array_search('live', $modelModifiers, true);
    $lazyWithoutLive = $liveIndex === false && in_array('lazy', $modelModifiers, true);
    $live = $liveIndex !== false || $lazyWithoutLive;
    $syncModifiers = $liveIndex !== false ? array_slice($modelModifiers, 0, $liveIndex) : $modelModifiers;
    $networkModifiers = $liveIndex !== false ? array_slice($modelModifiers, $liveIndex + 1) : [];
    $syncOnBlur = ! $lazyWithoutLive && (bool) array_intersect(['blur', 'change', 'lazy'], $syncModifiers);
    $commitOnBlur = $lazyWithoutLive || ($live && (bool) array_intersect(['blur', 'change', 'lazy'], $networkModifiers));
    $durationOf = function (string $modifier) use ($networkModifiers): ?int {
        $index = array_search($modifier, $networkModifiers, true);

        if ($index === false) {
            return null;
        }

        $value = $networkModifiers[$index + 1] ?? null;

        if (is_string($value) && preg_match('/^(\d+(?:\.\d+)?)(ms|s)?$/', $value, $matches) === 1) {
            return (int) round((float) $matches[1] * (($matches[2] ?? 'ms') === 's' ? 1000 : 1));
        }

        return 150;
    };
    $commitDebounce = $durationOf('debounce');
    $commitThrottle = $durationOf('throttle');
    $commitDebounce ??= $commitThrottle === null ? 150 : null;

    $mentionItems = $mentions === null
        ? null
        : collect($mentions)->map(fn ($item): array => ['id' => data_get($item, 'id'), 'label' => data_get($item, 'label')])->values()->all();

    $rootAttributes = $attributes->whereDoesntStartWith('wire:model')->except(['aria-label', 'aria-labelledby', 'aria-describedby']);

    $catalog = [
        'bold' => ['fas-bold', __('ui::ui.editor.bold'), null],
        'italic' => ['fas-italic', __('ui::ui.editor.italic'), null],
        'link' => ['fas-link', __('ui::ui.editor.link'), null],
        'bullet-list' => ['fas-list-ul', __('ui::ui.editor.bullet_list'), null],
        'ordered-list' => ['fas-list-ol', __('ui::ui.editor.ordered_list'), null],
        'heading-2' => [null, __('ui::ui.editor.heading_2'), 'H2'],
        'heading-3' => [null, __('ui::ui.editor.heading_3'), 'H3'],
    ];
    $tools = array_values(array_filter(array_unique((array) $toolbar), fn ($tool): bool => isset($catalog[$tool])));

    $toolClasses = 'aria-pressed:bg-primary-100 aria-pressed:text-primary-900 aria-expanded:bg-primary-100 aria-expanded:text-primary-900 dark:aria-pressed:bg-primary-950 dark:aria-pressed:text-primary-200 dark:aria-expanded:bg-primary-950 dark:aria-expanded:text-primary-200';
    $labelId = $field->id.'-label';
    $explicitLabel = $attributes->get('aria-label');
    $explicitLabelledBy = $attributes->get('aria-labelledby');
    $labelledBy = filled($label) ? $labelId : $explicitLabelledBy;

    $borderClasses = $field->error === null ? 'border-gray-400 dark:border-gray-500' : 'border-rose-700 dark:border-rose-400';
    $contentClasses = 'ui-editor-content min-h-32 px-3 py-2 text-sm text-gray-900 focus:outline-none dark:text-gray-100';

    $config = [
        'id' => $field->id,
        'format' => $format,
        'toolbar' => $tools,
        'mentions' => $mentionItems,
        'mentionSearch' => $mentionSearch,
        'mentionChar' => $mentionChar,
        'commit' => ['live' => $live, 'blur' => $commitOnBlur, 'syncOnBlur' => $syncOnBlur, 'debounce' => $commitDebounce, 'throttle' => $commitThrottle],
        'unreadableId' => $field->id.'-unreadable',
        'linkErrorId' => $field->id.'-link-error',
        'listboxId' => $field->id.'-mentions',
        'optionPrefix' => $field->id.'-mention-',
        'labels' => [
            'linkInvalid' => __('ui::ui.editor.link_invalid'),
            'noMatches' => __('ui::ui.editor.no_matches'),
            'suggestions' => __('ui::ui.editor.suggestions_count'),
        ],
    ];

    $stateExpression = $modelProperty === null
        ? ''
        : 'state: $wire.$entangle('.\Illuminate\Support\Js::from($modelProperty).', false), ';
@endphp

<x-ui::field :id="$field->id" :hint="$hint" :error="$field->error">
    <div
        x-data="uiEditor({ {{ $stateExpression }}...{{ \Illuminate\Support\Js::from($config) }} })"
        x-effect="syncAttributes()"
        {{ $rootAttributes->class('space-y-1.5') }}
    >
        @if (filled($label))
            <div id="{{ $labelId }}" class="block text-sm font-medium text-gray-900 dark:text-gray-100" x-on:click="focusEditor()">{{ $label }}@if ($required) <span aria-hidden="true" class="text-rose-700 dark:text-rose-400">*</span>@endif</div>
        @endif

        <span
            hidden
            x-ref="meta"
            data-class="{{ $contentClasses }}"
            @if ($labelledBy) data-labelledby="{{ $labelledBy }}" @endif
            @if (filled($explicitLabel)) data-label="{{ $explicitLabel }}" @endif
            @if ($field->describedBy) data-describedby="{{ $field->describedBy }}" @endif
            @if ($field->error !== null) data-invalid @endif
            @if ($required) data-required @endif
        ></span>

        <div class="rounded-control border bg-white focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-primary-700 dark:bg-gray-900 dark:focus-within:outline-primary-400 {{ $borderClasses }}">
            <div wire:ignore>
                @if ($tools !== [])
                    <div
                        x-ref="toolbar"
                        id="{{ $field->id }}-toolbar"
                        role="toolbar"
                        aria-orientation="horizontal"
                        aria-controls="{{ $field->id }}"
                        aria-label="{{ filled($label) ? __('ui::ui.editor.toolbar', ['label' => $label]) : __('ui::ui.editor.toolbar_unnamed') }}"
                        class="flex flex-wrap items-center gap-1 border-b border-gray-200 p-1 dark:border-gray-700"
                        x-show="!unreadable"
                        x-on:keydown="toolbarKeydown($event)"
                    >
                        @foreach ($tools as $index => $tool)
                            @if ($tool === 'link')
                                <x-ui::button
                                variant="ghost"
                                size="sm"
                                :icon="$catalog[$tool][0]"
                                :label="$catalog[$tool][1]"
                                :aria-label="$catalog[$tool][1]"
                                data-tool="{{ $tool }}"
                                tabindex="{{ $index === 0 ? '0' : '-1' }}"
                                :class="$toolClasses"
                                x-on:mousedown.prevent=""
                                x-on:click="runTool('{{ $tool }}', $event)"
                                x-on:focus="toolbarIndex = {{ $index }}"
                                x-bind:tabindex="toolbarIndex === {{ $index }} ? 0 : -1"
                                    aria-expanded="false"
                                    aria-controls="{{ $field->id }}-link"
                                    x-bind:aria-expanded="link.open ? 'true' : 'false'"
                                >{{ $catalog[$tool][2] }}</x-ui::button>
                            @else
                                <x-ui::button
                                variant="ghost"
                                size="sm"
                                :icon="$catalog[$tool][0]"
                                :label="$catalog[$tool][1]"
                                :aria-label="$catalog[$tool][1]"
                                data-tool="{{ $tool }}"
                                tabindex="{{ $index === 0 ? '0' : '-1' }}"
                                :class="$toolClasses"
                                x-on:mousedown.prevent=""
                                x-on:click="runTool('{{ $tool }}', $event)"
                                x-on:focus="toolbarIndex = {{ $index }}"
                                x-bind:tabindex="toolbarIndex === {{ $index }} ? 0 : -1"
                                    aria-pressed="false"
                                    x-bind:aria-pressed="active['{{ $tool }}'] ? 'true' : 'false'"
                                >{{ $catalog[$tool][2] }}</x-ui::button>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if (in_array('link', $tools, true))
                    <div
                        id="{{ $field->id }}-link"
                        x-show="link.open && !unreadable"
                        style="display: none"
                        role="group"
                        aria-label="{{ __('ui::ui.editor.link') }}"
                        class="flex flex-wrap items-end gap-2 border-b border-gray-200 p-2 dark:border-gray-700"
                        x-on:keydown.escape.stop.prevent="closeLink(true)"
                    >
                        <div class="min-w-48 flex-1">
                            <x-ui::input
                                :id="$field->id.'-link-address'"
                                :label="__('ui::ui.editor.link_address')"
                                type="text"
                                inputmode="url"
                                autocomplete="off"
                                x-ref="linkInput"
                                x-model="link.url"
                                x-on:keydown.enter.prevent="applyLink()"
                                x-bind:aria-invalid="link.error ? 'true' : null"
                                x-bind:aria-describedby="link.error ? linkErrorId : null"
                            />
                            <p id="{{ $field->id }}-link-error" role="alert" x-show="link.error" style="display: none" x-text="link.error" class="mt-1.5 text-sm text-rose-700 dark:text-rose-400"></p>
                        </div>
                        <x-ui::button size="sm" data-link-apply x-on:click="applyLink()">{{ __('ui::ui.editor.link_apply') }}</x-ui::button>
                        <x-ui::button size="sm" variant="outline" data-link-remove x-show="link.existing" style="display: none" x-on:click="removeLink()">{{ __('ui::ui.editor.link_remove') }}</x-ui::button>
                        <x-ui::button size="sm" variant="ghost" data-link-cancel x-on:click="closeLink(true)">{{ __('ui::ui.editor.link_cancel') }}</x-ui::button>
                    </div>
                @endif

                <p id="{{ $field->id }}-unreadable" x-show="unreadable" style="display: none" class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300">{{ __('ui::ui.editor.unreadable') }}</p>

                <div x-ref="host"></div>

                @if ($mentions !== null || $mentionSearch !== null)
                    <div
                        id="{{ $field->id }}-mentions"
                        role="listbox"
                        aria-label="{{ __('ui::ui.editor.suggestions') }}"
                        x-show="suggest.open && suggest.items.length > 0"
                        style="display: none"
                        x-bind:style="{ position: 'fixed', top: suggest.top + 'px', left: suggest.left + 'px', zIndex: 60 }"
                        class="max-h-60 min-w-48 overflow-auto rounded-control border border-gray-400 bg-white py-1 text-sm shadow-lg dark:border-gray-500 dark:bg-gray-900"
                    >
                        <template x-for="(item, index) in suggest.items" x-bind:key="item.id">
                            <div
                                role="option"
                                x-bind:id="optionPrefix + index"
                                x-bind:aria-selected="index === suggest.index ? 'true' : 'false'"
                                x-text="item.label"
                                x-on:mousedown.prevent=""
                                x-on:click="chooseSuggestion(index)"
                                x-on:mousemove="suggest.index = index"
                                class="cursor-pointer px-3 py-1.5 text-gray-900 aria-selected:bg-primary-100 aria-selected:text-primary-900 dark:text-gray-100 dark:aria-selected:bg-primary-950 dark:aria-selected:text-primary-200"
                            ></div>
                        </template>
                    </div>
                    <div
                        aria-hidden="true"
                        x-show="suggest.open && suggest.items.length === 0"
                        style="display: none"
                        x-bind:style="{ position: 'fixed', top: suggest.top + 'px', left: suggest.left + 'px', zIndex: 60 }"
                        x-text="suggest.status"
                        class="rounded-control border border-gray-400 bg-white px-3 py-1.5 text-sm text-gray-700 shadow-lg dark:border-gray-500 dark:bg-gray-900 dark:text-gray-300"
                    ></div>
                    <div role="status" class="sr-only" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap" x-text="suggest.status"></div>
                @endif
            </div>
        </div>
    </div>
</x-ui::field>
