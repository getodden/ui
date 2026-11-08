# Odden UI

Accessible Blade components, Livewire traits and Tailwind 4 theme tokens for Laravel Livewire screens.

Status: **0.1.** Pre-1.0, so a minor version may still change the API. It is the UI layer behind the Odden Livewire screens, and it knows nothing about CRMs or any host application.

## Install

```bash
composer require getodden/ui
```

Requires PHP 8.3, Laravel 13, Livewire 4 (which bundles Alpine with its Focus plugin) and Blade FontAwesome (free set). The service provider is discovered automatically.

Import the tokens in your Tailwind 4 entry file. The import also tells Tailwind to scan the component views, and it adds the `dark` variant, which follows a `.dark` class on a parent element.

```css
@import 'tailwindcss';
@import '../../vendor/getodden/ui/resources/css/theme.css';
```

The theme defines `primary-50` to `primary-950`, a `gray` scale, the `rounded-control` and `rounded-surface` radii and the `Inter Variable` font stack. Override any `--color-primary-*` in your own `@theme` to rebrand.

Put `<x-ui::toasts />` once in your layout, outside any element that is hidden or re-rendered.

## Components

All components are anonymous Blade components under the `ui` prefix. Extra attributes land on the main element.

| Component | Props |
| --- | --- |
| `<x-ui::button>` | `variant` primary, secondary, outline, ghost, danger; `size` sm, md, lg; `href` (renders a link); `type` (default `button`); `disabled`; `icon`; `label` (the accessible name of an icon-only button, required when the slot is empty) |
| `<x-ui::badge>` | `variant` gray, primary, success, warning, danger, info; `icon` |
| `<x-ui::card>` | `heading` (prop or slot); `level` (default 2); `footer` slot |
| `<x-ui::alert>` | `variant` info, success, warning, danger; `heading`; `icon`; `dismissible` |
| `<x-ui::modal>` | `name`; `heading` (required, it names the dialog); `description`; `size` sm, md, lg, xl; `footer` slot; `wire:model` |
| `<x-ui::confirm>` | `confirmation` (the `$confirmation` property of `WithConfirmation`) |
| `<x-ui::toasts>` | `duration` in milliseconds before a non-danger toast leaves (default 5000) |
| `<x-ui::input>` | `label` (required unless you pass `aria-label` or `aria-labelledby`), `name`, `id`, `hint`, `error`, `required`, `type` |
| `<x-ui::textarea>` | the field props, `rows` |
| `<x-ui::select>` | the field props, `options` (value => label), `placeholder`, `value`, and a slot |
| `<x-ui::toggle>` | the field props; renders a switch |
| `<x-ui::icon>` | `name` (for example `fas-check`), `size` sm, md, lg |
| `<x-ui::editor>` | the field props, `format` html or json, `toolbar` (list of tools, `[]` for none), `mentions`, `mention-search`, `mention-char` |
| `<x-ui::board>` | `label` (required unless you pass `aria-label` or `aria-labelledby`), `columns` (key => label, offered in each card's Move to select) |
| `<x-ui::board.column>` | `column` (key), `label`, `count`, `total` (text, optional), `has-more` |
| `<x-ui::board.card>` | `id`, `label` |
| `<x-ui::table>` | `caption` (required unless you pass `aria-label` or `aria-labelledby`), `caption-visible`, `columns`, `state`, `records`; `toolbar` and `empty` slots |

Components that take an `icon` accept a Blade FontAwesome name as a string, or a named `icon` slot rendered as given.

### Fields

`name` is taken from `wire:model*` when you do not pass it, and `id` from `name` (`address[city]` becomes `ui-address-city`). The error is read from the `$errors` bag by the same key when you do not pass `error`.

Two fields bound to the same key on one page get the same id, so give one of them an explicit `id`. An `aria-describedby` you pass is appended to the hint and error ids, not substituted for them. A field without a `label` throws unless it has an `aria-label` or `aria-labelledby`.

```blade
<x-ui::input label="Email" wire:model.blur="form.email" hint="We never share it" required />
```

### Modal

```blade
<x-ui::modal wire:model="showEdit" heading="Edit account">...</x-ui::modal>

<x-ui::modal name="invite" heading="Invite someone">...</x-ui::modal>
<x-ui::button x-on:click="$dispatch('ui-modal-open', { name: 'invite' })">Invite</x-ui::button>
```

The `ui-modal-open` and `ui-modal-close` window events take the modal's `name` in `detail.name`. Put `data-autofocus` on an element inside the modal to start focus there instead of on the first focusable element. The dialog is teleported to `<body>`, and the most recently opened one is always on top, so a `ui::confirm` can sit anywhere in the page. If the modal is removed from the page while open, focus returns to its opener. Escape closes only the modal that holds focus, so a modal opened from inside another closes first. A modal without a `heading` throws.

### Editor

A TipTap rich-text field. It reads and writes the same HTML and JSON as Filament's RichEditor for paragraphs, headings 1 to 4, bold, italic, underline, strike, inline code, code blocks, links, lists, blockquotes, rules and mentions. Text alignment, sub- and superscript, colours, tables, images and custom blocks are not supported: stored HTML loses them when saved, and a stored JSON document holding them opens read-only with a notice, so it is never overwritten. Install TipTap 3 in your app and register the Alpine data before Alpine starts:

```bash
npm install @tiptap/core@^3 @tiptap/pm@^3 @tiptap/starter-kit@^3 @tiptap/extension-mention@^3 @tiptap/suggestion@^3
```

```js
import { registerEditor } from '../../vendor/getodden/ui/resources/js/editor.js'

document.addEventListener('alpine:init', () => registerEditor(window.Alpine))
```

```blade
<x-ui::editor label="Reply" wire:model="body" mention-search="searchPeople" />
```

Tools are `bold`, `italic`, `link`, `bullet-list`, `ordered-list`, `heading-2` and `heading-3`. Links accept http, https, mailto, tel and relative addresses. `wire:model` follows Livewire 4: plain and `.blur` update the component without a request, `.live` sends after a 150 ms pause (`.live.debounce.500ms` and `.live.throttle.500ms` change it), and `.live.blur` sends on blur when the text changed. As in Livewire, `.live.throttle` alone can hold the last keystrokes until the next change or request.

Mentions come from a static `:mentions="[['id' => '7', 'label' => 'Ada']]"` list, which is read once and is visible in the page source, or from `mention-search`, a method on your component that takes the query and returns that same shape. Anyone can call that method with any string, so check who may see the results, cap the query and the number of results, return only `id` and `label`, and mark it `#[Renderless]`.

The bound value is whatever the browser sends, so treat it as untrusted. Before you save it or print it with `{!! !!}`, pass HTML through `Odden\Ui\Support\RichHtml::clean()` and a `format="json"` document through `RichJson::clean()`. Both keep only what the editor can produce. Both throw a `LengthException` above their size limits (`RichHtml::MAXIMUM_BYTES`, `MAXIMUM_TAGS`, `MAXIMUM_TAG_BYTES`, `RichJson::MAXIMUM_NODES`, `MAXIMUM_DEPTH`), so validate the length first. A single tag over 8 KiB, such as a very long link or a large pasted comment, counts as too long.

### Board

```blade
<x-ui::board label="Deals" :columns="$labels">
    @foreach ($columns as $key => $column)
        <x-ui::board.column :column="$key" :label="$column['label']" :count="$column['count']" :total="$column['total']" :has-more="$column['slice']->hasMore">
            @foreach ($column['slice']->records as $deal)
                <x-ui::board.card wire:key="deal-{{ $deal->id }}" :id="$deal->id" :label="$deal->name">{{ $deal->name }}</x-ui::board.card>
            @endforeach
        </x-ui::board.column>
    @endforeach
</x-ui::board>
```

```php
use Odden\Ui\Livewire\Concerns\WithBoard;

class Deals extends Component
{
    use WithBoard;

    protected function boardColumnKeys(): array
    {
        return ['new', 'won'];
    }

    protected function moveBoardCard(string $cardId, string $toColumn, int $position, ?string $afterCardId, bool $toEnd): bool
    {
        $deal = Deal::query()->find($cardId);

        if ($deal === null) {
            return false;
        }

        $this->authorize('update', $deal);

        return $deal->update(['stage' => $toColumn]);
    }

    protected function boardPageSize(): int
    {
        return 20;
    }
}
```

A card moves by dragging it, or with the controls on the card, which work with the keyboard and on touch screens: pick a column in the Move to select and press Move to send the card to the end of that column, or use the up and down buttons to move it one place within its column. Choosing in the select does not move anything by itself. The page moves the card at once and calls `moveBoardCard()`. When the server answers, the status region announces the position the card has in the list you rendered, and focus goes to the card's control, or to the column heading if your query no longer shows the card. Return `false`, throw an `AuthorizationException` or a `ValidationException`, or `abort(403)`, to refuse the move: the card goes back and the status region says so. A request that fails does the same.

The card id arrives from the browser as a string and is not trusted: look the card up through your tenant or policy scope. The column is checked against `boardColumnKeys()` and the position is a non-negative integer. The write method is never called for anything that fails those checks.

`$position` is an index among the cards the browser is showing in that column, and `$afterCardId` is the id of the card the moved card now sits directly below, or `null` when it is first. Prefer `$afterCardId` when you store a manual order: it stays right when other people change the column in the meantime. When the Move to select sends a card to a column that has more cards than are shown, `$position` is `BoardSlice::END_POSITION` and `$toEnd` is `true`; `$afterCardId` is `null`, as it is for a card dropped first in its column, so read `$toEnd` first and place the card at the end of the whole column. `$afterCardId` is as untrusted as the card id: look it up through the same scope.

Set `:reorderable="false"` on the board when the query decides the order: the up and down buttons go away and a drop inside the same column does nothing. Set `:movable="false"` on the board, or `locked` on a card, when the user may not move cards: those cards have no drag handle and no move controls. That only changes the markup: `moveCard` can still be called from the browser, so `moveBoardCard()` must refuse on its own. Give a column `:heading-level="2"` (2 to 6, default 3) to fit the heading outline of your page. Cards get a `wire:key` of `ui-board-card-{id}` unless you pass your own, which the rollback after a refused move relies on.

`boardSlice($query, $column)` returns the first `boardPageSize()` cards of a column and whether more exist. The column's Show more button calls `loadMoreCards()`, a read. If your component runs behind a write guard that classifies public methods as reads or writes, the guard must be told that `loadMoreCards` from `WithBoard` is a read; a trait method cannot carry your own attribute, so the guard has to list it itself, otherwise a read-only user can never page past the first cards. It raises that column's limit by one page, to at most 1000 cards. Your query decides the order, so a card that was moved shows up where the query puts it once the server answers. The count and total are yours to compute.

### Table

```blade
<x-ui::table caption="Contacts" :columns="['name' => 'Name', 'email' => 'Email']" :state="$this->recordTableState()" :records="$contacts">
    <x-slot:toolbar>
        <x-ui::input type="search" label="Search" wire:model.live.debounce.250ms="search" />
    </x-slot:toolbar>

    @foreach ($contacts as $contact)
        <tr wire:key="{{ $contact->id }}">...</tr>
    @endforeach
</x-ui::table>
```

Sortable headers are buttons, and the sorted column carries `aria-sort`. When there are no records, the table shows the `empty` slot. While a request runs, it is marked `aria-busy`. Pagination links appear when there is more than one page.

## Livewire traits

`WithRecordTable` holds search, sort, filters, per-page and pagination state in the URL. Every key comes from your allow-lists and is checked each time it is used, so a value from the URL or `$wire.$set` that is not on a list falls back to the default.

```php
use Odden\Ui\Livewire\Concerns\WithRecordTable;

class Contacts extends Component
{
    use WithRecordTable;

    protected function sortableColumns(): array
    {
        return ['name' => 'name', 'created' => 'created_at'];
    }

    protected function searchableColumns(): array
    {
        return ['name', 'email'];
    }

    protected function recordFilters(): array
    {
        return ['status' => fn (Builder $query, string $value) => $query->where('status', Status::tryFrom($value) ?? Status::Active)];
    }

    public function render(): View
    {
        return view('contacts', ['contacts' => $this->paginateRecords(Contact::query())]);
    }
}
```

A filter closure receives the raw string from the request, so validate it there. Override `defaultSort()`, `defaultDirection()`, `perPageOptions()` or `applySearch()` when you need to.

A chosen sort replaces any order on your base query, and the model key always breaks ties, so pages stay stable. Search compares `lower(column) like lower(term)`. On PostgreSQL that fails for columns that are not text, such as integers, uuids and json, so search those in your own `applySearch()`. On SQLite only ASCII letters match regardless of case. A page past the end shows the last page.

`WithToasts` adds `toast($message, ToastVariant::Success)`, which shows a toast now, and `toastAfterRedirect(...)`, which flashes it to the session for the next page. Danger toasts stay until dismissed; the others leave after five seconds, and hovering or focusing the toasts holds them.

`WithConfirmation` asks before a destructive action and keeps what was asked in a `#[Locked]` property, so the browser cannot change which method runs or with what arguments. The confirmed method may be `protected`, so confirming is the only way to reach it.

```php
use Odden\Ui\Livewire\Concerns\WithConfirmation;

class Contacts extends Component
{
    use WithConfirmation;

    public function askToDelete(int $id): void
    {
        $this->confirm('delete', [$id], 'Delete this contact?', 'This cannot be undone.', 'Delete');
    }

    protected function delete(int $id): void
    {
    }
}
```

```blade
<x-ui::confirm :confirmation="$confirmation" />
```

`confirm()` is protected, so only your own methods can call it. It refuses a name that is not a method on the component, the framework's own methods and lifecycle names such as `mount*`, `boot*`, `updated*` and `render`. Closing the dialog cancels the confirmation. Focus starts on Cancel for a danger confirmation and on the confirm button otherwise.

## Accessibility

- Text contrast follows the rules in the components, not overrides in your app: secondary text is `gray-500` or darker on light and `gray-400` on dark; the primary fill is `primary-700` with white text on light and `primary-400` with `gray-950` text on dark; danger, success and warning text use the 700, 700 and 800 shades on light.
- Fields render a `label` for the control, a hint and an error with ids, `aria-describedby` listing only what is rendered, `aria-invalid` only with an error, and an `aria-hidden` required mark beside the `required` attribute.
- Modals are `role="dialog"` with `aria-modal`, `aria-labelledby` and optional `aria-describedby`; focus is trapped, Escape and the backdrop close them, body scroll is locked and focus returns to the opener.
- The toast region is `aria-live="polite"` and is in the page from the start. Danger and warning alerts use `role="alert"`, the others `role="status"`.
- Control borders (inputs, selects, textareas, outline buttons) meet the 3:1 non-text contrast of WCAG 1.4.11. A disabled link button has no `href` and no click handlers.
- Icon-only buttons need a `label`. Icons are `aria-hidden`.
- The editor's toolbar is one tab stop with arrow-key movement and `aria-pressed` on toggles; the editable area is a labelled `textbox`, and mention suggestions are a `listbox` it points at with `aria-activedescendant`.
- Table headers are column headers, the sorted one carries `aria-sort`, the table is `aria-busy` while its own requests run, and a status region announces loading and the result count. The current page stays focusable.
- The board is a group of labelled column sections. Each card has a labelled Move to select, so a move never needs a pointer, and a polite status region announces each move or refusal. The board is `aria-busy` while a move is saved.

## Development

```bash
composer install
npm install && npx playwright install chromium
vendor/bin/pest --testsuite=Feature
vendor/bin/pest --testsuite=Browser
vendor/bin/phpstan analyse
vendor/bin/pint
```

The browser suite drives the demo page in `workbench/` and runs axe on it. The demo is not compiled with Tailwind, so axe cannot judge colour contrast there.

MIT licensed.
