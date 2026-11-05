import { Editor, Extension, mergeAttributes } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import Mention from '@tiptap/extension-mention'

const allowedProtocols = ['http', 'https', 'mailto', 'tel']
const maximumSuggestions = 8
const schemePattern = /^([a-z][a-z0-9+.-]*):/i
const invisiblePattern = /[\x00-\x20\x7f-\x9f\u{200b}-\u{200d}\u{2028}\u{2029}\u{feff}]/gu

const tools = {
    bold: { run: (chain) => chain.toggleBold(), isActive: (editor) => editor.isActive('bold') },
    italic: { run: (chain) => chain.toggleItalic(), isActive: (editor) => editor.isActive('italic') },
    link: { isActive: (editor) => editor.isActive('link') },
    'bullet-list': { run: (chain) => chain.toggleBulletList(), isActive: (editor) => editor.isActive('bulletList') },
    'ordered-list': { run: (chain) => chain.toggleOrderedList(), isActive: (editor) => editor.isActive('orderedList') },
    'heading-2': { run: (chain) => chain.toggleHeading({ level: 2 }), isActive: (editor) => editor.isActive('heading', { level: 2 }) },
    'heading-3': { run: (chain) => chain.toggleHeading({ level: 3 }), isActive: (editor) => editor.isActive('heading', { level: 3 }) },
}

export function isAllowedUri(uri) {
    if (typeof uri !== 'string') {
        return false
    }

    const match = schemePattern.exec(uri.replace(invisiblePattern, ''))

    return match !== null && allowedProtocols.includes(match[1].toLowerCase())
}

function normalizeAddress(address) {
    const trimmed = address.trim()

    if (trimmed === '' || schemePattern.test(trimmed.replace(invisiblePattern, ''))) {
        return trimmed
    }

    return /^[\w-]/.test(trimmed) ? `https://${trimmed}` : trimmed
}

function normalizeItems(list) {
    if (!Array.isArray(list)) {
        return []
    }

    return list
        .filter((item) => item !== null && typeof item === 'object' && (typeof item.id === 'string' || typeof item.id === 'number') && typeof item.label === 'string')
        .slice(0, maximumSuggestions)
        .map((item) => ({ id: String(item.id), label: item.label }))
}

function canonical(value) {
    const sorted = (node) => {
        if (Array.isArray(node)) {
            return node.length === 0 ? {} : node.map(sorted)
        }

        if (node !== null && typeof node === 'object') {
            return Object.fromEntries(Object.keys(node).sort().map((key) => [key, sorted(node[key])]))
        }

        return node
    }

    return JSON.stringify(sorted(value))
}

function createMention(char, suggestion) {
    return Mention.extend({
        addOptions() {
            return { ...this.parent?.(), deleteTriggerWithBackspace: true }
        },

        addAttributes() {
            return {
                id: {
                    default: null,
                    parseHTML: (element) => element.getAttribute('data-id'),
                    renderHTML: (attributes) => (attributes.id ? { 'data-id': attributes.id } : {}),
                },
                label: {
                    default: null,
                    keepOnSplit: false,
                    parseHTML: (element) => element.getAttribute('data-label'),
                    renderHTML: (attributes) => (attributes.label ? { 'data-label': attributes.label } : {}),
                },
                char: {
                    default: '@',
                    parseHTML: (element) => element.getAttribute('data-char') ?? '@',
                    renderHTML: (attributes) => (attributes.char ? { 'data-char': attributes.char } : {}),
                },
            }
        },

        renderHTML({ node, HTMLAttributes }) {
            return [
                'span',
                mergeAttributes({ 'data-type': this.name }, this.options.HTMLAttributes, HTMLAttributes),
                `${node.attrs.char ?? '@'}${node.attrs.label ?? ''}`,
            ]
        },

        renderText({ node }) {
            return `${node.attrs.char ?? '@'}${node.attrs.label ?? node.attrs.id ?? ''}`
        },
    }).configure({
        suggestion: {
            char,
            ...suggestion,
            command: ({ editor, range, props }) => {
                const nodeAfter = editor.view.state.selection.$to.nodeAfter

                if (nodeAfter?.text?.startsWith(' ')) {
                    range.to += 1
                }

                editor
                    .chain()
                    .focus()
                    .insertContentAt(range, [
                        { type: 'mention', attrs: { id: String(props.id), label: props.label, char } },
                        { type: 'text', text: ' ' },
                    ])
                    .run()
            },
        },
    })
}

export default function editor(config) {
    let instance = null
    let observer = null
    let removeListeners = null
    let commitSuggestion = null

    return {
        state: config.state ?? (config.format === 'json' ? null : ''),
        active: {},
        toolbarIndex: 0,
        link: { open: false, url: '', error: null, existing: false },
        suggest: { open: false, loading: false, items: [], index: 0, top: 0, left: 0, status: '' },

        init() {
            const self = this
            const extensions = [
                StarterKit.configure({
                    heading: { levels: [2, 3, 4] },
                    link: {
                        openOnClick: false,
                        autolink: true,
                        defaultProtocol: 'https',
                        isAllowedUri: (url) => isAllowedUri(url),
                    },
                }),
                Extension.create({
                    name: 'uiEditorShortcuts',
                    addKeyboardShortcuts() {
                        return {
                            'Mod-k': () => {
                                self.openLink()

                                return true
                            },
                        }
                    },
                }),
            ]

            if (config.mentions !== null || config.mentionSearch !== null) {
                extensions.push(createMention(config.mentionChar, this.suggestionOptions()))
            }

            instance = new Editor({
                element: this.$refs.host,
                extensions,
                content: this.contentFor(this.state),
                editorProps: { attributes: this.attributes() },
                onUpdate: () => {
                    this.state = this.output()
                    this.$dispatch('ui-editor-input', { value: this.state })
                },
                onTransaction: () => this.refreshActive(),
                onBlur: () => {
                    if (config.commitOnBlur) {
                        this.$wire.$commit()
                    }
                },
            })

            this.refreshActive()

            this.$watch('state', (value) => {
                if (!this.matchesEditor(value)) {
                    instance.commands.setContent(this.contentFor(value), { emitUpdate: false })
                }
            })

            observer = new MutationObserver(() => this.syncAttributes())
            observer.observe(this.$refs.meta, { attributes: true })
        },

        destroy() {
            observer?.disconnect()
            removeListeners?.()
            instance?.destroy()
            instance = null
        },

        output() {
            if (config.format === 'json') {
                return instance.getJSON()
            }

            return instance.isEmpty ? '' : instance.getHTML()
        },

        contentFor(value) {
            if (config.format === 'json') {
                return value !== null && typeof value === 'object' && Object.keys(value).length > 0 ? value : ''
            }

            return typeof value === 'string' ? value : ''
        },

        matchesEditor(value) {
            if (config.format === 'json') {
                return canonical(value ?? {}) === canonical(this.output())
                    || (Object.keys(value ?? {}).length === 0 && instance.isEmpty)
            }

            return (value ?? '') === this.output()
        },

        attributes() {
            const meta = this.$refs.meta
            const attributes = {
                id: config.id,
                role: 'textbox',
                'aria-multiline': 'true',
                class: meta.getAttribute('data-class') ?? '',
            }

            const labelledBy = meta.getAttribute('data-labelledby')
            const label = meta.getAttribute('data-label')
            const describedBy = meta.getAttribute('data-describedby')

            if (labelledBy) {
                attributes['aria-labelledby'] = labelledBy
            }

            if (label) {
                attributes['aria-label'] = label
            }

            if (describedBy) {
                attributes['aria-describedby'] = describedBy
            }

            if (meta.hasAttribute('data-invalid')) {
                attributes['aria-invalid'] = 'true'
            }

            if (meta.hasAttribute('data-required')) {
                attributes['aria-required'] = 'true'
            }

            if (config.mentions !== null || config.mentionSearch !== null) {
                attributes['aria-autocomplete'] = 'list'
            }

            if (this.suggest.open && this.suggest.items.length > 0) {
                attributes['aria-controls'] = config.listboxId
                attributes['aria-activedescendant'] = `${config.optionPrefix}${this.suggest.index}`
            }

            return attributes
        },

        syncAttributes() {
            const attributes = this.attributes()

            if (instance !== null) {
                instance.setOptions({ editorProps: { attributes } })
            }
        },

        refreshActive() {
            if (instance === null) {
                return
            }

            const next = {}

            for (const name of config.toolbar) {
                next[name] = tools[name]?.isActive(instance) ?? false
            }

            if (JSON.stringify(next) !== JSON.stringify(this.active)) {
                this.active = next
            }
        },

        focusEditor() {
            instance?.commands.focus()
        },

        runTool(name, event) {
            if (name === 'link') {
                this.openLink()

                return
            }

            const chain = instance.chain()

            if (event.detail > 0) {
                chain.focus()
            }

            tools[name].run(chain).run()
        },

        toolbarKeydown(event) {
            const buttons = [...this.$refs.toolbar.querySelectorAll('[data-tool]')]
            const current = buttons.indexOf(document.activeElement)

            if (current === -1) {
                return
            }

            const target = {
                ArrowRight: (current + 1) % buttons.length,
                ArrowLeft: (current - 1 + buttons.length) % buttons.length,
                Home: 0,
                End: buttons.length - 1,
            }[event.key]

            if (target === undefined) {
                return
            }

            event.preventDefault()
            this.toolbarIndex = target
            buttons[target].focus()
        },

        openLink() {
            const href = instance.getAttributes('link').href ?? ''

            this.link.url = href
            this.link.existing = instance.isActive('link')
            this.link.error = null
            this.link.open = true
            this.$nextTick(() => this.$refs.linkInput.focus())
        },

        closeLink(returnFocus) {
            this.link.open = false
            this.link.error = null

            if (returnFocus) {
                instance.commands.focus()
            }
        },

        applyLink() {
            const href = normalizeAddress(this.link.url)

            if (href === '') {
                this.removeLink()

                return
            }

            if (!isAllowedUri(href)) {
                this.link.error = config.labels.linkInvalid

                return
            }

            const { empty } = instance.state.selection
            const chain = instance.chain().focus()

            if (empty && !instance.isActive('link')) {
                chain.insertContent({ type: 'text', text: href, marks: [{ type: 'link', attrs: { href } }] })
            } else {
                chain.extendMarkRange('link').setLink({ href })
            }

            if (!chain.run()) {
                this.link.error = config.labels.linkInvalid

                return
            }

            this.closeLink(false)
        },

        removeLink() {
            instance.chain().focus().extendMarkRange('link').unsetLink().run()
            this.closeLink(false)
        },

        suggestionOptions() {
            const self = this
            const search = config.mentionSearch !== null

            return {
                debounce: search ? 250 : 0,
                items: async ({ query }) => {
                    if (search) {
                        return normalizeItems(await self.$wire[config.mentionSearch](query))
                    }

                    const needle = query.toLowerCase()

                    return normalizeItems(config.mentions).filter((item) => item.label.toLowerCase().includes(needle)).slice(0, maximumSuggestions)
                },
                render: () => ({
                    onStart: (props) => self.suggestionStart(props),
                    onUpdate: (props) => self.suggestionUpdate(props),
                    onKeyDown: ({ event }) => self.suggestionKeydown(event),
                    onExit: () => self.suggestionExit(),
                }),
            }
        },

        suggestionStart(props) {
            this.suggest.items = []
            this.suggest.index = 0
            this.suggest.open = false
            commitSuggestion = props.command

            const reposition = () => this.suggestionPosition(props)
            window.addEventListener('scroll', reposition, true)
            window.addEventListener('resize', reposition)
            removeListeners = () => {
                window.removeEventListener('scroll', reposition, true)
                window.removeEventListener('resize', reposition)
            }

            this.suggestionUpdate(props)
        },

        suggestionUpdate(props) {
            commitSuggestion = props.command
            this.suggest.loading = props.loading
            this.suggestionPosition(props)

            if (props.loading) {
                return
            }

            this.suggest.items = normalizeItems(props.items)
            this.suggest.index = 0
            this.suggest.open = true
            this.suggest.status = this.suggest.items.length === 0
                ? config.labels.noMatches
                : config.labels.suggestions.replace(':count', String(this.suggest.items.length))
        },

        suggestionPosition(props) {
            const rect = props.clientRect?.()

            if (rect) {
                this.suggest.top = rect.bottom + 4
                this.suggest.left = rect.left
            }
        },

        suggestionKeydown(event) {
            if (!this.suggest.open || this.suggest.items.length === 0) {
                return false
            }

            const count = this.suggest.items.length

            if (event.key === 'ArrowDown') {
                this.suggest.index = (this.suggest.index + 1) % count
            } else if (event.key === 'ArrowUp') {
                this.suggest.index = (this.suggest.index - 1 + count) % count
            } else if (event.key === 'Enter' || event.key === 'Tab') {
                this.chooseSuggestion(this.suggest.index)
            } else {
                return false
            }

            return true
        },

        chooseSuggestion(index) {
            const item = this.suggest.items[index]

            if (item && commitSuggestion) {
                commitSuggestion({ id: item.id, label: item.label })
            }
        },

        suggestionExit() {
            removeListeners?.()
            removeListeners = null
            commitSuggestion = null
            this.suggest.open = false
            this.suggest.loading = false
            this.suggest.items = []
            this.suggest.status = ''
        },
    }
}

export function registerEditor(Alpine) {
    Alpine.data('uiEditor', editor)
}
