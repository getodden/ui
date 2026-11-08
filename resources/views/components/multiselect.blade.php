@props(['label' => null, 'name' => null, 'id' => null, 'hint' => null, 'error' => null, 'required' => false, 'options' => [], 'placeholder' => null, 'value' => []])

@php
    $field = \Odden\Ui\Support\FieldState::resolve($attributes, $label, $name, $id, $error, filled($hint), $errors ?? null);
    $items = collect($options)->map(fn ($optionLabel, $optionValue): array => ['value' => (string) $optionValue, 'label' => (string) $optionLabel])->values()->all();
    $initial = collect($value)->map(fn ($selected): string => (string) $selected)->values()->all();
    $labelId = $field->id.'-label';
    $summaryId = $field->id.'-summary';
    $listboxId = $field->id.'-listbox';
    $borderClasses = $field->error === null ? 'border-gray-400 dark:border-gray-500' : 'border-rose-700 dark:border-rose-400';
    $triggerAttributes = $field->controlAttributes($attributes->whereDoesntStartWith(['wire:model', 'wire:key']), $required)->except(['name', 'required'])->class(["flex w-full items-center justify-between gap-2 rounded-control border bg-white px-3 py-2 text-left text-sm text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-gray-100 dark:focus-visible:outline-primary-400", $borderClasses]);
    $triggerAttributes = $required ? $triggerAttributes->merge(['aria-required' => 'true']) : $triggerAttributes;
    $triggerAttributes = filled($label) ? $triggerAttributes->merge(['aria-labelledby' => $labelId.' '.$summaryId]) : $triggerAttributes;
@endphp

<x-ui::field :id="$field->id" :label="$label" :hint="$hint" :error="$field->error" :required="$required" identified>
    <div
        class="relative"
        x-data="{
            id: @js($field->id),
            selected: [],
            revision: 0,
            placeholder: @js(filled($placeholder) ? $placeholder : __('ui::ui.multiselect.placeholder')),
            countText: @js(__('ui::ui.multiselect.count')),
            open: false,
            active: -1,
            init() {
                this.selected = JSON.parse(this.$root.dataset.initial)
                this.$nextTick(() => {
                    this.revision++
                    new MutationObserver(() => this.revision++).observe(this.$refs.list, { childList: true, subtree: true, characterData: true, attributes: true, attributeFilter: ['data-label', 'data-value'] })
                })
            },
            get picked() {
                return Array.isArray(this.selected) ? this.selected : []
            },
            get options() {
                this.revision
                return [...(this.$refs.list?.querySelectorAll('[role=option]') ?? [])].map((option) => ({ value: option.dataset.value, label: option.dataset.label }))
            },
            get values() {
                return this.options.map((option) => option.value)
            },
            has(value) {
                return this.picked.map(String).includes(value)
            },
            toggle(index) {
                const value = this.values[index]
                if (value === undefined) return
                this.selected = this.has(value) ? this.picked.filter((item) => String(item) !== value) : [...this.picked, value]
            },
            summary() {
                if (this.picked.length === 0) return this.placeholder
                if (this.picked.length > 2) return this.countText.replace(':count', this.picked.length)
                return this.picked.map((value) => this.options.find((option) => option.value === String(value))?.label ?? value).join(', ')
            },
            show(position) {
                if (this.values.length === 0) return
                this.open = true
                const firstSelected = this.values.findIndex((value) => this.has(value))
                this.active = position === 'last' ? this.values.length - 1 : (firstSelected >= 0 ? firstSelected : 0)
                this.reveal()
            },
            close() {
                this.open = false
                this.active = -1
            },
            move(step) {
                this.active = Math.min(this.values.length - 1, Math.max(0, this.active + step))
                this.reveal()
            },
            jump(index) {
                this.active = index
                this.reveal()
            },
            reveal() {
                this.$nextTick(() => document.getElementById(this.id + '-option-' + this.active)?.scrollIntoView({ block: 'nearest' }))
            }
        }"
        x-modelable="selected"
        data-initial="{{ json_encode($initial, JSON_THROW_ON_ERROR) }}"
        x-on:click.outside="close()"
        {{ $attributes->whereStartsWith(['wire:model', 'wire:key']) }}
    >
        <button
            type="button"
            role="combobox"
            aria-haspopup="listbox"
            aria-controls="{{ $listboxId }}"
            x-bind:aria-expanded="open ? 'true' : 'false'"
            x-bind:aria-activedescendant="open && active >= 0 ? id + '-option-' + active : null"
            x-on:click="open ? close() : show('first')"
            x-on:keydown.arrow-down.prevent="open ? move(1) : show('first')"
            x-on:keydown.arrow-up.prevent="open ? move(-1) : show('last')"
            x-on:keydown.home.prevent="open && jump(0)"
            x-on:keydown.end.prevent="open && jump(values.length - 1)"
            x-on:keydown.enter.prevent="open ? toggle(active) : show('first')"
            x-on:keydown.space.prevent="open ? toggle(active) : show('first')"
            x-on:keyup.space.prevent
            x-on:keydown.escape="if (open) { $event.stopPropagation(); close() }"
            x-on:keydown.tab="close()"
            {{ $triggerAttributes }}
        >
            <span id="{{ $summaryId }}" class="truncate" x-text="summary()" x-bind:class="picked.length === 0 ? 'text-gray-500 dark:text-gray-400' : ''"></span>
            <x-ui::icon name="fas-chevron-down" size="sm" class="text-gray-500 dark:text-gray-400" />
        </button>

        <ul
            id="{{ $listboxId }}"
            x-ref="list"
            role="listbox"
            aria-multiselectable="true"
            tabindex="-1"
            @if (filled($label)) aria-labelledby="{{ $labelId }}" @elseif ($attributes->has('aria-labelledby')) aria-labelledby="{{ $attributes->get('aria-labelledby') }}" @else aria-label="{{ $attributes->get('aria-label') }}" @endif
            x-show="open"
            style="display: none"
            class="absolute z-40 mt-1 max-h-60 w-full overflow-y-auto rounded-control border border-gray-200 bg-white py-1 shadow-popover dark:border-gray-700 dark:bg-gray-900"
        >
            @foreach ($items as $index => $item)
                <li
                    id="{{ $field->id }}-option-{{ $index }}"
                    role="option"
                    data-value="{{ $item['value'] }}"
                    data-label="{{ $item['label'] }}"
                    x-bind:aria-selected="has(@js($item['value'])) ? 'true' : 'false'"
                    x-bind:class="active === {{ $index }} ? 'bg-primary-100 text-primary-900 ring-2 ring-inset ring-primary-700 dark:bg-primary-950 dark:text-primary-200 dark:ring-primary-400' : 'text-gray-900 dark:text-gray-100'"
                    x-on:mousedown.prevent
                    x-on:mousemove="active = {{ $index }}"
                    x-on:click="toggle({{ $index }})"
                    class="flex cursor-pointer items-center justify-between gap-2 px-3 py-2 text-sm"
                >
                    <span>{{ $item['label'] }}</span>
                    <x-ui::icon name="fas-check" size="sm" class="text-primary-700 dark:text-primary-400" x-show="has($el.closest('[role=option]').dataset.value)" />
                </li>
            @endforeach
        </ul>

        @if (filled($name))
            <template x-for="item in picked" x-bind:key="item">
                <input type="hidden" name="{{ $name }}[]" x-bind:value="item">
            </template>
        @endif
    </div>
</x-ui::field>
