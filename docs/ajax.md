---
subtitle: How Amber widgets behave as Larajax view components.
---
# AJAX & Larajax

Every Amber widget is a [Larajax view component](https://larajax.org/guide/defining-components).
Building a widget with `::make()` inside a controller action binds it to that controller, and from
then on the widget's AJAX handlers - sorting, pagination, filter updates, uploads - are routed
automatically. This page explains the lifecycle so you can reason about it and hook into it.

## The request lifecycle

1. The page renders normally: the action builds the widgets, the view renders them.
2. A widget control in the browser fires an AJAX request naming a handler, e.g. `list::onPaginate` -
   the widget alias, then the handler method.
3. Larajax runs the **same controller action** first, so the widgets are rebuilt exactly as they were
   for the page, then dispatches to the named handler on the bound widget.
4. The handler returns an array of partial updates (`['#element-id' => '<html>']`), which the
   framework applies to the page.

Two practical consequences:

- **Routes must accept POST.** AJAX handlers arrive as POST requests to the same URL:

```php
Route::match(['get', 'post'], '/users', [UserController::class, 'index']);
```

- **Build widgets the same way on every request.** Because the action re-runs for each AJAX call,
  construct widgets in the action (or a method it calls) rather than conditionally - otherwise a
  handler may target a widget that was never built.

## Handler naming

Widget handlers are addressed as `alias::onHandler`. The alias comes from the widget configuration:

```php
$widget = Lists::make(alias: 'list', /* ... */);
// exposes list::onSort, list::onPaginate, list::onRefresh, ...
```

Controllers can also define their own plain handlers (`public function onSave()`) addressed without a
prefix - see [saving form data](forms.md#saving-submitted-data).

In markup, handlers bind through data attributes rendered by the widgets, e.g.
`data-request="list::onSort"`, or through the `Ui` facade's
[`ajaxButton`](ui.md#ajax-button).

## Guarding side effects

The controller action runs for both page loads and AJAX requests. Keep side effects (marking
notifications read, logging views) out of the AJAX path by guarding them:

```php
public function index()
{
    if (!request()->ajax()) {
        // page-load-only side effects
    }

    // widget construction runs for both
}
```

## Widget events

Widgets fire local events you can bind to from the controller - the toolbar's search wiring is the
canonical example:

```php
$search->bindEvent('search.submit', function () use ($widget, $search) {
    $widget->setSearchTerm($search->getActiveTerm(), true);
    return $widget->onRefresh();
});
```

Returning an array from a bound event merges it into the AJAX response, letting one widget's handler
refresh another widget's markup.

For deeper customization, subclass a widget and override its `event*` extension points - each widget
guide lists them.

## Further reading

The action lifecycle, `request()->ajax()` guards, flash messages, loading indicators and the
`window.jax` browser API are Larajax features shared by all view components - see the
[Larajax documentation](https://larajax.org) for the full picture.
