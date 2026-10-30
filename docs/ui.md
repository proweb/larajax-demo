---
subtitle: Render buttons, inputs and callouts with the Ui facade.
---
# UI Elements

Amber owns the **`Ui` facade** - a small factory for rendering common UI elements (buttons, search
inputs, dropdowns, callouts) from your views. Each call returns a renderable object, so you drop it
straight into Blade.

```blade
{{ Ui::button(label: 'Save', primary: true) }}
```

The objects returned by `Ui` implement Laravel's `Htmlable` contract, so Blade's escaped <span v-pre>`{{ }}`</span> output
renders them as raw HTML. You do **not** need `{!! !!}`.

## Buttons

### Basic button

`Ui::button()` renders a plain button. Use PHP named arguments for readability:

```blade
{{ Ui::button(label: 'Save', primary: true) }}
```

Common arguments:

Argument | Description
------------- | -------------
**label** | the button text.
**primary** | primary styling (`btn-solid theme-primary`).
**secondary** | secondary styling (`btn-solid theme-secondary`).
**danger** | danger styling (`btn-solid theme-danger`).
**icon** | an icon class rendered before the label.
**href** | render an `<a>` link instead of a `<button>`.
**hotkey** | a keyboard shortcut, e.g. `'ctrl+s'` or `['ctrl+s', 'cmd+s']`.

Without a styling flag the button renders Bootstrap's plain `btn` base class.

Pass a `href` to render a link styled as a button:

```blade
{{ Ui::button(label: 'Back', href: '/users', secondary: true) }}
```

Add a keyboard shortcut with `hotkey`. Amber renders the platform key symbols in the tooltip
automatically (for example `ctrl+s` shows as `⌃S`):

```blade
{{ Ui::button(label: 'Save', primary: true, hotkey: ['ctrl+s', 'cmd+s']) }}
```

### AJAX button

`Ui::ajaxButton()` wires the button to a [Larajax](https://larajax.org) AJAX handler. This is the button
you want when saving a form without a full page reload:

```blade
{{ Ui::ajaxButton(label: 'Save', handler: 'onSave', primary: true) }}
```

Argument | Description
------------- | -------------
**handler** | the AJAX handler to run, e.g. `'onSave'`.
**requestData** | extra data to send with the request, as an array.

```blade
{{ Ui::ajaxButton(
    label: 'Delete',
    handler: 'onDelete',
    danger: true,
    requestData: ['id' => $user->id]
) }}
```

### Popup button

`Ui::popupButton()` opens the handler's response in a popup - a native `<dialog>` that Amber manages
itself, styled with Bootstrap 6's dialog classes, so it needs no Bootstrap JavaScript. The handler
returns the dialog contents (header/body/footer markup), and elements inside carrying
`data-dismiss="popup"` close it:

```blade
{{ Ui::popupButton(label: 'Edit', handler: 'onEditForm') }}
```

Pass a size through the attribute bag: `dataSize: 'large'`. The default width is 598px, and the sizes
are `tiny` (300px), `small` (400px), `large` (750px), `huge` (900px), `giant` (982px), `fullscreen`,
`adaptive` (the window less 100px), `auto` (fits the content) or a number of pixels. The handler can
also set it from the response with a `data-popup-size` attribute on its first element:

```blade
<div data-popup-size="large">
    <div class="dialog-header">
        <h4 class="dialog-title">Edit User</h4>
        <button type="button" class="btn-close" data-dismiss="popup" aria-label="Close"></button>
    </div>
    <div class="dialog-body">...</div>
</div>
```

Clicking the backdrop makes the popup bounce rather than close; pass `dataBackdrop: true` to close it
instead. Escape closes the popup unless you pass `dataKeyboard: false`, which makes it bounce.

While the popup loads, a backdrop with a loading indicator is shown. A request button inside the popup
can do the same: add `data-popup-load-indicator` next to its `data-request` and the popup hides behind
the indicator while the request runs, closes when it succeeds, and shakes when it fails:

```html
<button data-request="onSave" data-popup-load-indicator class="btn-solid theme-primary">
    Save
</button>
```

Popups can also be opened from JavaScript, and announce themselves with `popup:show`, `popup:shown`,
`popup:hide`, `popup:hidden`, `popup:complete` and `popup:error` events on the element that opened
them:

```js
jax.popup.open({ handler: 'onEditForm', extraData: { id: 3 } });
```

### Icon button

`Ui::iconButton()` renders an icon-only link button with the `btn-icon-link` class (the label becomes
its accessible title). Bootstrap 6's own `btn-icon` is a different, square button:

```blade
{{ Ui::iconButton(label: 'Refresh', icon: 'icon-refresh', handler: 'onRefresh') }}
```

## Dropdowns

Dropdowns are slot-based: open the button with `->slot()`, echo the items, then close with `Ui::end()`.
Because the content is captured rather than returned, use `<?php ... ?>` tags around the open and close
calls. The button opens a Bootstrap 6 menu through its `data-bs-toggle="menu"` data API, so the
Bootstrap JavaScript module must be on the page:

```blade
<?php Ui::dropdownButton(label: 'Actions', secondary: true)->slot() ?>
    {{ Ui::dropdownItem(label: 'Edit', handler: 'onEdit') }}
    {{ Ui::dropdownItem(label: 'Duplicate', handler: 'onDuplicate') }}
    {{ Ui::dropdownDivider() }}
    {{ Ui::dropdownItem(label: 'Delete', handler: 'onDelete', class: 'fg-danger') }}
<?php Ui::end() ?>
```

The output is a `.btn-group` holding the toggle and a `.menu.control-dropdown` panel. Pass
`caret: false` to hide the caret icon, and any other argument becomes an attribute on the element, as
`class` does on the Delete item above. Items written by hand must be `<a class="menu-item">` elements
placed directly inside the menu, since Bootstrap's arrow-key navigation only finds direct children.

## Search input

`Ui::searchInput()` renders a search field. Give it a `handler` to fire an AJAX request as the user
types (debounced automatically):

```blade
{{ Ui::searchInput(name: 'q', placeholder: 'Search users...', handler: 'onSearch') }}
```

## Callouts

`Ui::callout()` renders a highlighted message block. It uses a fluent (chained) API rather than named
arguments:

```blade
{{ Ui::callout()->label('Heads up')->comment('This action cannot be undone')->warning() }}
```

The type methods set the callout style:

Method | Style
------------- | -------------
`->tip()` | informational (default)
`->success()` | success
`->warning()` | warning
`->danger()` | danger

## A note on syntax

Amber deliberately keeps the same call for every engine rather than wrapping these in component tags:

- **Blade** - call the facade directly: <span v-pre>`{{ Ui::button(label: 'Save', primary: true) }}`</span>.
- **Twig** *(when supported)* - the same element via a markup function taking an options hash:
  <span v-pre>`{{ ui_button({ label: 'Save', primary: true }) }}`</span>.

Both share one underlying `Ui` implementation, so the argument names and behavior are identical across
engines - only the surrounding call syntax differs, staying idiomatic to each.
