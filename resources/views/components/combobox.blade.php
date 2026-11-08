@props(['label' => null, 'name' => null, 'id' => null, 'hint' => null, 'error' => null, 'required' => false, 'search' => null, 'value' => null, 'valueLabel' => null, 'debounce' => 250, 'minimum' => 0])

@php
    throw_if(preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', (string) $search) !== 1, \InvalidArgumentException::class, 'A ui::combobox search is the name of a public method.');

    $field = \Odden\Ui\Support\FieldState::resolve($attributes, $label, $name, $id, $error, filled($hint), $errors ?? null);
    $initialValue = $value === null ? '' : (string) $value;
    $labelId = $field->id.'-label';
    $listboxId = $field->id.'-listbox';
    $borderClasses = $field->error === null ? 'border-gray-400 dark:border-gray-500' : 'border-rose-700 dark:border-rose-400';
    $inputAttributes = $field->controlAttributes($attributes->whereDoesntStartWith(['wire:model', 'wire:key']), $required)->except('name')->class(["block w-full rounded-control border bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-400 dark:focus-visible:outline-primary-400", $borderClasses]);
@endphp

<x-ui::field :id="$field->id" :label="$label" :hint="$hint" :error="$field->error" :required="$required" identified>
    <div
        class="relative"
        x-data="{
            id: @js($field->id),
            method: @js($search),
            debounce: @js((int) $debounce),
            minimum: @js((int) $minimum),
            text: @js([
                'searching' => __('ui::ui.combobox.searching'),
                'none' => __('ui::ui.combobox.no_matches'),
                'one' => __('ui::ui.combobox.one_result'),
                'many' => __('ui::ui.combobox.many_results'),
                'failed' => __('ui::ui.combobox.failed'),
            ]),
            value: '',
            ownValue: '',
            query: '',
            results: [],
            failed: false,
            open: false,
            active: -1,
            loading: false,
            searched: false,
            sequence: 0,
            timer: null,
            init() {
                this.value = this.$root.dataset.value
                this.ownValue = this.$root.dataset.value
                this.query = this.$root.dataset.label
                this.$watch('value', (value) => {
                    if (String(value ?? '') === this.ownValue) return
                    this.ownValue = String(value ?? '')
                    if (this.ownValue === '') this.query = ''
                })
            },
            expanded() {
                return this.open && this.results.length > 0
            },
            typed() {
                this.value = ''
                this.ownValue = ''
                this.schedule()
            },
            schedule() {
                clearTimeout(this.timer)
                this.sequence++
                this.results = []
                this.searched = false
                this.failed = false
                if (this.query.trim().length < this.minimum) {
                    this.loading = false
                    this.close()
                    return
                }
                this.open = true
                this.loading = true
                this.active = -1
                this.timer = setTimeout(() => this.run(), this.debounce)
            },
            async run() {
                const sequence = ++this.sequence
                this.loading = true
                this.failed = false
                try {
                    const items = await this.$wire.$call(this.method, this.query)
                    if (sequence !== this.sequence) return
                    this.results = (Array.isArray(items) ? items : [])
                        .filter((item) => item && item.id !== undefined && item.id !== null && typeof item.label === 'string')
                        .map((item) => ({ id: String(item.id), label: item.label }))
                    this.searched = true
                    this.active = -1
                } catch (error) {
                    if (sequence === this.sequence) {
                        this.results = []
                        this.searched = false
                        this.failed = true
                    }
                } finally {
                    if (sequence === this.sequence) this.loading = false
                }
            },
            reopen() {
                if (this.results.length > 0 && this.searched) {
                    this.open = true
                    return
                }
                this.schedule()
            },
            pick(index) {
                const item = this.results[index]
                if (!item) return
                clearTimeout(this.timer)
                this.sequence++
                this.loading = false
                this.value = item.id
                this.ownValue = item.id
                this.query = item.label
                this.close()
            },
            move(step) {
                if (!this.expanded()) {
                    this.reopen()
                    return
                }
                this.active = (this.active + step + this.results.length) % this.results.length
                this.reveal()
            },
            jump(index) {
                if (!this.expanded()) return
                this.active = index < 0 ? this.results.length - 1 : index
                this.reveal()
            },
            enter(event) {
                if (!this.expanded()) return
                event.preventDefault()
                if (this.active >= 0) this.pick(this.active)
            },
            escape(event) {
                if (!this.open) return
                event.stopPropagation()
                this.close()
            },
            close() {
                this.open = false
                this.active = -1
            },
            leave() {
                clearTimeout(this.timer)
                this.sequence++
                this.loading = false
                this.close()
                if (this.value === '') {
                    this.query = ''
                    this.results = []
                    this.searched = false
                }
                this.failed = false
            },
            reveal() {
                this.$nextTick(() => document.getElementById(this.id + '-option-' + this.active)?.scrollIntoView({ block: 'nearest' }))
            },
            status() {
                if (!this.open) return ''
                if (this.loading) return this.text.searching
                if (this.failed) return this.text.failed
                if (!this.searched) return ''
                if (this.results.length === 0) return this.text.none
                return this.results.length === 1 ? this.text.one : this.text.many.replace(':count', this.results.length)
            }
        }"
        x-modelable="value"
        data-value="{{ $initialValue }}"
        data-label="{{ $valueLabel }}"
        x-on:focusout="if (!$el.contains($event.relatedTarget)) leave()"
        {{ $attributes->whereStartsWith(['wire:model', 'wire:key']) }}
    >
        <input
            type="text"
            role="combobox"
            autocomplete="off"
            aria-autocomplete="list"
            aria-controls="{{ $listboxId }}"
            x-model="query"
            x-bind:aria-expanded="expanded() ? 'true' : 'false'"
            x-bind:aria-activedescendant="expanded() && active >= 0 ? id + '-option-' + active : null"
            x-on:input="typed()"
            x-on:keydown.arrow-down.prevent="move(1)"
            x-on:keydown.arrow-up.prevent="move(-1)"
            x-on:keydown.home="expanded() && ($event.preventDefault(), jump(0))"
            x-on:keydown.end="expanded() && ($event.preventDefault(), jump(-1))"
            x-on:keydown.enter="enter($event)"
            x-on:keydown.escape="escape($event)"
            {{ $inputAttributes }}
        >

        <ul
            id="{{ $listboxId }}"
            role="listbox"
            @if (filled($label)) aria-labelledby="{{ $labelId }}" @elseif ($attributes->has('aria-labelledby')) aria-labelledby="{{ $attributes->get('aria-labelledby') }}" @else aria-label="{{ $attributes->get('aria-label') }}" @endif
            x-show="expanded()"
            style="display: none"
            class="absolute z-40 mt-1 max-h-60 w-full overflow-y-auto rounded-control border border-gray-200 bg-white py-1 shadow-popover dark:border-gray-700 dark:bg-gray-900"
        >
            <template x-for="(item, index) in results" x-bind:key="index">
                <li
                    role="option"
                    x-bind:id="id + '-option-' + index"
                    x-bind:aria-selected="String(value) === item.id ? 'true' : 'false'"
                    x-bind:class="active === index ? 'bg-primary-100 text-primary-900 ring-2 ring-inset ring-primary-700 dark:bg-primary-950 dark:text-primary-200 dark:ring-primary-400' : 'text-gray-900 dark:text-gray-100'"
                    x-text="item.label"
                    x-on:mousedown.prevent
                    x-on:mousemove="active = index"
                    x-on:click="pick(index)"
                    class="cursor-pointer px-3 py-2 text-sm"
                ></li>
            </template>
        </ul>

        <p
            x-show="open && searched && !loading && results.length === 0"
            style="display: none"
            class="absolute z-40 mt-1 w-full rounded-control border border-gray-200 bg-white px-3 py-2 text-sm text-gray-500 shadow-popover dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400"
            x-text="text.none"
        ></p>

        <p
            x-show="open && failed"
            style="display: none"
            class="absolute z-40 mt-1 w-full rounded-control border border-rose-700 bg-white px-3 py-2 text-sm text-rose-700 shadow-popover dark:border-rose-400 dark:bg-gray-900 dark:text-rose-400"
            x-text="text.failed"
        ></p>

        <div role="status" class="sr-only" x-text="status()"></div>

        @if (filled($name))
            <input type="hidden" name="{{ $name }}" x-bind:value="value">
        @endif
    </div>
</x-ui::field>
