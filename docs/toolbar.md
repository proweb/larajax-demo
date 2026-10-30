---
subtitle: Action buttons and a search bar for list views.
---
# Toolbar

The Toolbar widget renders a horizontal control bar, typically above a [List widget](lists.md). It has
three optional parts: a buttons area for actions, a search box, and a list setup icon.

## Rendering a toolbar

Build the widget with `Toolbar::make(...)` and render it above the list:

```php
use Larajax\Ui\Widgets\Toolbar;

$toolbar = Toolbar::make(
    alias: 'toolbar',
    buttons: '~/resources/ui/user/buttons.yaml',
    search: ['prompt' => 'Search users...'],
);
```

```blade
{{ $toolbar }}

{{ $widget }}
```

## Widget configuration

Option | Description
------------- | -------------
**buttons** | the toolbar buttons, as a YAML file path or array of [button definitions](#button-definitions), or a [partial name](#button-partials) resolved through the controller.
**search** | search widget configuration array, or a partial name for a custom search area. Omit to hide the search box.
**setupHandler** | an AJAX handler name that opens the list setup popup, rendering a setup icon in the toolbar.

The `listWidgetId` property links the toolbar to a list element for button integrations:

```php
$toolbar->listWidgetId = $widget->getId();
```

With the linkage in place, toolbar buttons can react to the list's row selection - see
[checked-state buttons](lists.md#checked-state-buttons).

## Button definitions

Define the toolbar buttons declaratively in a YAML file. Each button renders through the
[`Ui` facade](ui.md) button components:

```yaml
# resources/ui/user/buttons.yaml
buttons:
    create:
        label: New User
        icon: ph ph-plus
        type: primary
        url: users/create

    delete:
        label: Delete
        type: danger
        handler: onDelete
        confirm: Delete the selected users?
        checked: true
```

The definitions can also be passed inline as an array on the `buttons` config. Each button
supports the following options:

Option | Description
------------- | -------------
**label** | the button label, passed through the translator.
**icon** | an icon key or CSS classes, resolved by the icon manager.
**type** | the button styling: `default`, `primary`, `secondary` or `danger`.
**url** | renders the button as a link to this address.
**handler** | renders the button as an AJAX button posting to this handler (`data-request`).
**popup** | set to `true` to load the handler response in a popup instead.
**confirm** | a confirmation message displayed before the AJAX request runs.
**checked** | set to `true` to link the button to the checked rows of the bound list - the button stays disabled until rows are checked, and the checked ids are included with its request (see [checked-state buttons](lists.md#checked-state-buttons)).
**visible** | a boolean, or the name of a controller method resolved when the toolbar renders.
**permissions** | one or more gate abilities the user must have for the button to display.
**hotkey** | a hotkey binding for the button, for example `ctrl+n`.
**requestData** | extra request data included with the AJAX request.
**attributes** | an array of extra HTML attributes to render on the button element.

The delete button above posts the checked row ids to a controller handler:

```php
public function onDelete()
{
    $checkedIds = (array) input('checked');

    User::whereIn('id', $checkedIds)->get()->each->delete();

    return ajax()
        ->flash('success', 'Users deleted.')
        ->redirect('users');
}
```

### Conditional buttons

For simple conditions, point the `visible` option at a controller method. The method receives the
button definition and returns a boolean:

```yaml
    publish:
        label: Publish
        handler: onPublish
        visible: canPublish
```

```php
public function canPublish(): bool
{
    return $this->user->isDraft();
}
```

For logic involving several buttons or request state, implement a `toolbarExtendButtons` override
on the controller. It is called after the buttons are defined and receives the toolbar widget:

```php
public function toolbarExtendButtons($toolbar)
{
    if (!$this->user->isDraft()) {
        $toolbar->removeButton('publish');
    }

    $toolbar->getButton('delete')->confirm('This cannot be undone.');
}
```

The widget exposes `addButtons()`, `getButton()` and `removeButton()` for manipulating the
definitions, and each definition is a `ToolbarButton` object whose options can be changed with
fluent calls, such as `->label('New Label')`.

## Button partials

When a toolbar is mostly conditional markup, skip the definitions and supply a partial name to the
`buttons` config instead - the partial renders through the controller, so the controller needs the
`ViewMaker` trait (see [Installation](installation.md)). Compose the buttons with the
[`Ui` facade](ui.md):

```php
<!-- resources/views/users/_toolbar_buttons.php -->
<div data-control="toolbar">
    <?= Ui::button(
        label: 'New User',
        href: 'users/create',
        primary: true
    ) ?>

    <?= Ui::ajaxButton(
        label: 'Delete Selected',
        handler: 'onDeleteSelected',
        danger: true
    ) ?>
</div>
```

## Search

Pass an array to `search` to configure the built-in search widget:

Option | Description
------------- | -------------
**prompt** | the search placeholder text.
**mode** | the search strategy: `all` (contains all words), `any` (contains any word) or `exact` (contains the phrase). Passed to the list.
**scope** | a custom model query scope method to perform the search. Passed to the list.
**searchOnEnter** | wait for the enter key instead of searching on every keystroke. Default: `false`.
**partial** | render a custom partial instead of the standard search input.

The search term persists in the session, so it survives page reloads until cleared.

### Wiring search to a list

The search widget fires a `search.submit` event when a term is entered. Connect it to the list in your
controller action - apply the active term for the initial render, then bind the event to refresh the
list when the term changes:

```php
$toolbar = Toolbar::make(
    alias: 'toolbar',
    search: ['prompt' => 'Search users...'],
);

$search = $toolbar->getSearchWidget();

// Apply the active term when the page renders
$widget->setSearchTerm($search->getActiveTerm());

// Refresh the list when the term changes
$search->bindEvent('search.submit', function () use ($widget, $search) {
    $widget->setSearchTerm($search->getActiveTerm(), true);
    return $widget->onRefresh();
});
```

The second argument to `setSearchTerm` resets the list to page one for a new search. Only columns
marked `searchable: true` in the list definition are searched.

Amber registers the underlying `searchWhere` query methods as Eloquent builder macros, so searching
works against plain `Illuminate` models as well as October Rain models.

## Next steps

- [Lists](lists.md) - the record list this toolbar controls
- [UI Elements](ui.md) - the buttons rendered inside the toolbar
