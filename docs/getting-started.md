---
subtitle: What Amber is and when to reach for it.
---
# Getting Started

Amber builds data-driven interfaces from configuration. You describe a form's fields or a list's
columns in YAML, bind a model, and the widget renders working markup with AJAX behavior - sorting,
pagination, searching, validation, uploads - wired up automatically.

It is the same widget engine that powers the [October CMS](https://octobercms.com) admin panel,
packaged as a standalone library for any Laravel application. There is no CMS dependency, no
JavaScript framework and no build step: the browser assets are plain CSS and ES modules, styled on
top of Bootstrap 6.

## The sixty second version

Define the columns:

```yaml
# resources/amber/user/columns.yaml
columns:
    name:
        label: Name
        searchable: true
    email:
        label: Email Address
    created_at:
        label: Created
        type: datetime
```

Build the widget in a controller and render it in a view:

```php
$widget = Lists::make(
    alias: 'list',
    model: new User,
    columns: '~/resources/amber/user/columns.yaml',
    recordsPerPage: 10,
);
```

```blade
{{ $widget }}
```

That renders a sortable, searchable, paginated table of users. Forms work the same way from a
`fields.yaml`, and filters from a `scopes.yaml`.

Each widget declares its common options as `make()` parameters, so your editor can suggest and
document them as you type; less common options pass through the same call and are resolved by name.
A configuration array built dynamically is passed with `Lists::makeWithConfig($config)`.

Widgets are `Htmlable`, so Blade's `{{ }}` renders their markup directly - calling
`{!! $widget->render() !!}` remains equivalent.

## The widgets

- **[Form](forms.md)** - data-editing forms with field types, tabs and contexts
- **[Lists](lists.md)** - record tables with column types, sorting and pagination, plus a tree and
  reorderable variant
- **[Filters](filters.md)** - scope-based filtering that constrains a list query
- **[Toolbar](toolbar.md)** - action buttons and a search bar above a list
- **[UI Elements](ui.md)** - buttons, inputs and callouts via the `Ui` facade

Widgets compose: a typical index page is a Toolbar (buttons + search), a Filter, and a List all bound
to the same model, refreshing each other through AJAX.

## How it fits together

Every widget is a [Larajax](https://larajax.org) view component. Building a widget with `::make()`
inside a controller action binds it to that controller, which is what routes its AJAX handlers -
see [AJAX & Larajax](ajax.md) for the lifecycle.

Configuration is declarative but never a cage: every widget can also be configured with plain PHP
arrays, extended by subclassing, and driven by any model - including plain Eloquent models, see
[Using Eloquent models](eloquent.md).

## Next steps

- [Installation](installation.md) - get Amber into your application
