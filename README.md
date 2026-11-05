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

## Livewire traits

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
