@props(['label' => null, 'name' => null, 'id' => null, 'hint' => null, 'error' => null, 'rows' => [], 'itemLabel' => null, 'addLabel' => null])

@php
    throw_if(blank($name), \InvalidArgumentException::class, 'A ui::repeater needs the name of the array property it edits.');

    $field = \Odden\Ui\Support\FieldState::resolve($attributes, $label, $name, $id, $error, filled($hint), $errors ?? null);
    $rows = array_values($rows);
    $itemLabel = filled($itemLabel) ? $itemLabel : __('ui::ui.repeater.item');
    $spec = isset($this) && method_exists($this, 'repeaterSpec') ? $this->repeaterSpec((string) $name) : null;
    throw_if(isset($this) && method_exists($this, 'repeaterSpec') && $spec === null, \InvalidArgumentException::class, 'The repeater ['.$name.'] is not registered in repeaters() of its component, or is not an array property of it.');
    $atMaximum = $spec?->max !== null && count($rows) >= $spec->max;

    $config = [
        'name' => $name,
        'messages' => [
            'added' => __('ui::ui.repeater.added', ['row' => ':row']),
            'removed' => __('ui::ui.repeater.removed', ['row' => ':row']),
            'moved' => __('ui::ui.repeater.moved', ['row' => ':row', 'position' => ':position', 'total' => ':total']),
        ],
    ];

    $script = <<<'JS'
(config) => ({
    status: '',
    dragging: null,
    root: null,
    init() {
        this.root = this.$el
    },
    rowElements() {
        return [...this.root.querySelectorAll('[data-repeater-key]')]
    },
    rowKeys() {
        return this.rowElements().map((row) => row.dataset.repeaterKey)
    },
    rowElement(key) {
        return this.rowElements().find((row) => row.dataset.repeaterKey === key)
    },
    titleOf(row) {
        return row.querySelector('[data-repeater-title]').textContent.trim()
    },
    fill(template, values) {
        return template.replace(/:(row|position|total)/g, (match, name) => values[name])
    },
    announce(template, row, title = this.titleOf(row)) {
        const rows = this.rowElements()

        this.status = this.fill(template, { row: title, position: rows.indexOf(row) + 1, total: rows.length })
    },
    async announceFresh(write) {
        this.status = ''
        await this.$nextTick()
        write()
    },
    async settle(call) {
        await call
        await this.$nextTick()
    },
    async add() {
        const before = this.rowKeys()

        await this.settle(this.$wire.addRepeaterRow(config.name))

        const row = this.rowElements().find((candidate) => ! before.includes(candidate.dataset.repeaterKey))

        if (row) {
            await this.announceFresh(() => this.announce(config.messages.added, row))
            row.querySelector('input:not([type=hidden]), select, textarea, [contenteditable=true]')?.focus()
        }
    },
    async remove(key, position) {
        const title = this.titleOf(this.rowElement(key))

        await this.settle(this.$wire.removeRepeaterRow(config.name, key))

        if (this.rowElement(key)) {
            return
        }

        await this.announceFresh(() => { this.status = this.fill(config.messages.removed, { row: title }) })

        const rows = this.rowElements()
        const next = rows[Math.min(position, rows.length - 1)]

        if (next) {
            next.querySelector('[data-repeater-row]').focus()
        } else {
            this.root.querySelector('[data-repeater-add]').focus()
        }
    },
    async move(key, direction) {
        const before = this.rowKeys().join()
        const title = this.titleOf(this.rowElement(key))

        await this.settle(this.$wire.moveRepeaterRow(config.name, key, direction))

        if (this.rowKeys().join() === before) {
            return
        }

        const row = this.rowElement(key)

        await this.announceFresh(() => this.announce(config.messages.moved, row, title))

        const preferred = row.querySelector('[data-repeater-action=' + direction + ']:not([disabled])')
        const fallback = row.querySelector('[data-repeater-action]:not([disabled])')

        ;(preferred ?? fallback)?.focus()
    },
    startDrag(event, key) {
        this.dragging = key
        event.dataTransfer.effectAllowed = 'move'
        event.dataTransfer.setData('text/plain', key)
        event.dataTransfer.setDragImage(this.rowElement(key), 0, 0)
    },
    allowDrop(event) {
        if (this.dragging !== null) {
            event.preventDefault()
        }
    },
    async drop(event, targetKey) {
        const dragged = this.dragging

        if (dragged === null) {
            return
        }

        event.preventDefault()
        this.dragging = null

        const keys = this.rowKeys()
        const before = keys.join()
        const from = keys.indexOf(dragged)
        const to = keys.indexOf(targetKey)

        if (from < 0 || to < 0 || from === to) {
            return
        }

        const title = this.titleOf(this.rowElement(dragged))

        keys.splice(to, 0, keys.splice(from, 1)[0])

        await this.settle(this.$wire.reorderRepeaterRows(config.name, keys))

        if (this.rowKeys().join() === before) {
            return
        }

        await this.announceFresh(() => this.announce(config.messages.moved, this.rowElement(dragged), title))
    },
})
JS;
@endphp

<div {{ $attributes->except('aria-describedby')->merge(array_filter([
    'role' => 'group',
    'aria-labelledby' => $field->id.'-label',
    'aria-describedby' => $field->describedBy,
    'x-data' => '('.$script.')('.\Illuminate\Support\Js::from($config).')',
], fn ($value) => $value !== null))->class('space-y-3') }}>
    <p id="{{ $field->id }}-label" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ $label }}</p>

    @if (filled($hint))
        <p id="{{ $field->id }}-hint" class="text-sm text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif

    @if ($rows === [])
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('ui::ui.repeater.empty') }}</p>
    @else
        <ol class="space-y-3">
            {{ $slot }}
        </ol>
    @endif

    @if ($field->error !== null)
        <p id="{{ $field->id }}-error" class="text-sm text-rose-700 dark:text-rose-400">{{ $field->error }}</p>
    @endif

    <x-ui::button variant="outline" size="sm" icon="fas-plus" :disabled="$atMaximum" data-repeater-add x-on:click="add()">{{ filled($addLabel) ? $addLabel : __('ui::ui.repeater.add') }}</x-ui::button>

    <p class="sr-only" role="status" aria-live="polite" x-text="status"></p>
</div>
