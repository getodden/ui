@props(['label' => null, 'columns' => []])

@php
    throw_if(
        blank($label) && ! $attributes->has('aria-label') && ! $attributes->has('aria-labelledby'),
        \InvalidArgumentException::class,
        'A ui::board needs a label, an aria-label or an aria-labelledby.'
    );

    $messages = [
        'moved' => __('ui::ui.board.moved'),
        'movedElsewhere' => __('ui::ui.board.moved_elsewhere'),
        'refused' => __('ui::ui.board.refused'),
        'failed' => __('ui::ui.board.failed'),
    ];
@endphp

<div
    role="group"
    @if (filled($label)) aria-label="{{ $label }}" @endif
    x-data="{
        pending: false,
        dragging: null,
        over: null,
        message: '',
        sayTimer: null,
        messages: @js($messages),
        say(key, values) {
            const text = this.messages[key].replace(/:(\w+)/g, (match, name) => values[name] ?? match)
            clearTimeout(this.sayTimer)
            this.message = ''
            this.sayTimer = setTimeout(() => (this.message = text), 50)
        },
        controlLike(card, like) {
            if (!like.hasAttribute('data-board-shift')) return card.querySelector('[data-board-move]')
            return [...card.querySelectorAll('[data-board-shift]')].find((button) => button.dataset.boardShift === like.dataset.boardShift) ?? null
        },
        cardElement(id) {
            return [...this.$el.querySelectorAll('[data-board-card]')].find((card) => card.dataset.boardCard === id) ?? null
        },
        listElement(key) {
            return [...this.$el.querySelectorAll('[data-board-list]')].find((list) => list.dataset.boardList === key) ?? null
        },
        cardsIn(list) {
            return [...list.children].filter((child) => child.hasAttribute('data-board-card'))
        },
        columnLabel(list) {
            return list.closest('[data-board-column]').dataset.boardLabel
        },
        positionAt(list, pointerY, moving) {
            const rest = this.cardsIn(list).filter((card) => card !== moving)
            const index = rest.findIndex((card) => {
                const box = card.getBoundingClientRect()
                return pointerY < box.top + box.height / 2
            })
            return index === -1 ? rest.length : index
        },
        adjustCount(list, change) {
            const count = list.closest('[data-board-column]').querySelector('[data-board-count]')
            if (count) count.textContent = Math.max(0, (parseInt(count.textContent, 10) || 0) + change)
        },
        dragStart(event) {
            const card = event.target.closest?.('[data-board-card]')
            if (!card || this.pending) {
                event.preventDefault()
                return
            }
            this.dragging = card.dataset.boardCard
            event.dataTransfer.effectAllowed = 'move'
            event.dataTransfer.setData('text/plain', this.dragging)
        },
        dragOver(event) {
            if (this.dragging === null) return
            const list = event.target.closest?.('[data-board-column]')?.querySelector('[data-board-list]')
            if (!list) return
            event.preventDefault()
            event.dataTransfer.dropEffect = 'move'
            this.over = list.dataset.boardList
        },
        drop(event) {
            const card = this.dragging === null ? null : this.cardElement(this.dragging)
            const list = event.target.closest?.('[data-board-column]')?.querySelector('[data-board-list]')
            this.dragEnd()
            if (!card || !list) return
            event.preventDefault()
            this.move(card, list.dataset.boardList, this.positionAt(list, event.clientY, card))
        },
        dragEnd() {
            this.dragging = null
            this.over = null
        },
        select(event) {
            const select = event.target
            const card = select.closest?.('[data-board-card]')
            if (!card || !select.hasAttribute('data-board-move')) return
            const key = select.value
            select.value = ''
            if (key === '') return
            const list = this.listElement(key)
            if (!list) return
            this.move(card, key, this.cardsIn(list).filter((other) => other !== card).length)
        },
        shift(event) {
            const button = event.target.closest?.('[data-board-shift]')
            const card = button?.closest('[data-board-card]')
            if (!card || !button) return
            const list = card.parentElement
            this.move(card, list.dataset.boardList, this.cardsIn(list).indexOf(card) + parseInt(button.dataset.boardShift, 10))
        },
        async move(card, key, position) {
            const target = this.listElement(key)
            const source = card.parentElement
            if (this.pending || !target) return
            const rest = this.cardsIn(target).filter((other) => other !== card)
            const toIndex = Math.min(Math.max(position, 0), rest.length)
            if (source === target && this.cardsIn(source).indexOf(card) === toIndex) return
            const next = card.nextElementSibling
            const focused = card.contains(document.activeElement) ? document.activeElement : null
            const values = { id: card.dataset.boardCard, card: card.dataset.boardLabel, column: this.columnLabel(target), position: toIndex + 1, total: rest.length + 1 }
            const origin = this.columnLabel(source)
            this.pending = true
            target.insertBefore(card, rest[toIndex] ?? null)
            if (source !== target) {
                this.adjustCount(source, -1)
                this.adjustCount(target, 1)
            }
            focused?.focus()
            let outcome = 'failed'
            try {
                outcome = (await this.$wire.moveCard(card.dataset.boardCard, key, toIndex)) === true ? 'moved' : 'refused'
            } catch (error) {
                outcome = 'failed'
            }
            if (outcome === 'moved') {
                await this.$nextTick()
                const placed = this.cardElement(values.id)
                if (placed) {
                    const shown = this.cardsIn(placed.parentElement)
                    this.say('moved', { card: values.card, column: this.columnLabel(placed.parentElement), position: shown.indexOf(placed) + 1, total: shown.length })
                } else {
                    this.say('movedElsewhere', { card: values.card, column: values.column })
                }
            } else {
                if (card.isConnected && card.parentElement === target && source !== target) {
                    this.adjustCount(source, 1)
                    this.adjustCount(target, -1)
                }
                if (card.isConnected && card.parentElement === target) {
                    source.insertBefore(card, next?.parentElement === source ? next : null)
                }
                this.say(outcome, { card: values.card, column: origin })
            }
            this.pending = false
            await this.$nextTick()
            if (focused && document.activeElement !== focused) {
                const placed = this.cardElement(values.id)
                const heading = this.listElement(key)?.closest('[data-board-column]').querySelector('[data-board-heading]')
                const landing = focused.isConnected ? focused : ((placed ? this.controlLike(placed, focused) : null) ?? heading)
                landing?.focus()
            }
        },
    }"
    x-on:dragstart="dragStart($event)"
    x-on:dragover="dragOver($event)"
    x-on:drop="drop($event)"
    x-on:dragend="dragEnd()"
    x-on:change="select($event)"
    x-on:click="shift($event)"
    x-bind:aria-busy="pending ? 'true' : null"
    {{ $attributes->class(['flex items-start gap-4 overflow-x-auto pb-2']) }}
>
    {{ $slot }}

    <p role="status" aria-live="polite" aria-atomic="true" class="sr-only" x-text="message"></p>
</div>
