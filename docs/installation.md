---
subtitle: Get Amber installed in a Laravel application.
---
# Installation

Amber is a standalone library that renders forms, lists and filters from YAML configuration. It is the
same widget engine that powers the October CMS admin panel, packaged so it can run in any Laravel
application. This page gets it installed and wired into a layout.

## Requirements

- PHP 8.2 or higher
- Laravel 12 or higher
- [october/rain](https://github.com/octobercms/library) - provides the underlying database, validation
  and HTML helpers Amber builds on
- [larajax/larajax](https://larajax.org) - provides the view component interface that Amber widgets
  implement, and the `window.jax` browser API their controls register against
- [Bootstrap 6](https://getbootstrap.com) - Amber's styles layer over Bootstrap's, and its menus use
  Bootstrap's data API. Load the Bootstrap CSS and its JavaScript module in your layout before
  Amber's assets (see [Bootstrap 6](#bootstrap-6) below).

You do not need October CMS itself. Amber runs against a plain Laravel install.

### Bootstrap 6

Amber targets Bootstrap `6.0.0-alpha.1`. Bootstrap 6 is still an alpha release, so its class names
and tokens may change before the stable version, and a few things follow from it:

- **Modern browsers only.** Bootstrap 6 needs Chrome or Edge 130, Firefox 132 or Safari 18 and
  newer, with no fallbacks below that.
- **ES modules only.** Bootstrap's JavaScript loads with `<script type="module">` or an `import` in
  your bundle. There is no `window.bootstrap` global, and Amber does not need one.
- **Amber owns its popups.** A popup is Amber's own native `<dialog>`, styled with Bootstrap's
  `dialog-*` classes, so it needs no Bootstrap JavaScript. Menus open through Bootstrap's
  `data-bs-toggle="menu"` data API, which the Bootstrap module provides.
- **Set the colour mode on `<html>`.** Bootstrap follows the operating system's light or dark
  preference when no `data-bs-theme` attribute is set, but Amber's palette follows the attribute. Set
  `data-bs-theme="light"` or `data-bs-theme="dark"` on `<html>` before the page paints, from a saved
  choice or the system preference, as in Bootstrap's
  [color modes guide](https://getbootstrap.com/docs/6.0/customize/color-modes/).

## Install the package

Require the package with Composer:

```bash
composer require larajax/ui
```

The package registers its `UiServiceProvider` automatically through Laravel's package discovery, so
there is no provider to add by hand.

## Publish the assets

Amber widgets load a small CSS + JS bundle in the browser. It is **plain CSS and ES modules - there is
no build step**. The package's `resources/assets` directory is the browser-consumable root; publishing
copies it verbatim, so the published paths always match the source paths. Publish once after
installing, and again after upgrading:

```bash
php artisan vendor:publish --tag=larajax-ui-assets
```

The files are copied into `public/vendor/larajax/ui/`.

## Include the assets in your layout

Add the stylesheets and scripts to your Blade layout. Bootstrap's stylesheet comes before Amber's, so
Amber's rules layer over it. Larajax must load **before** Amber's script, because Amber widgets
register their controls against the `window.jax` API it exposes. Use the larajax **bundle** build - the
plain `framework.js` build does not include the control API (`jax.registerControl`) that Amber
requires.

```blade
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="{{ asset('vendor/larajax/ui/ui.css') }}">
<script type="module" src="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('vendor/larajax/framework-bundle.js') }}"></script>
<script type="module" src="{{ asset('vendor/larajax/ui/ui.js') }}"></script>
```

That is everything the browser needs. Beyond Bootstrap and Larajax, Amber ships no framework
dependency - the CSS and JS are self-contained.

If you write your own widgets that load extra files with `addJs()` or `addCss()` in `loadAssets()`,
print them at the end of `<head>`. AJAX responses carry the same files when a widget is created
during a request, and the browser skips files the page head already has.

```blade
{{ \Larajax\Ui\Facades\Ui::renderAssets() }}
```

### Using with Vite

If your application already runs a [Vite](https://laravel.com/docs/vite) pipeline, you can skip
publishing entirely and import the entries straight from the vendor directory - the source tree
resolves the same way the published tree does:

```js
// resources/js/app.js
import '../../vendor/larajax/ui/resources/assets/ui.js';
```

```css
/* resources/css/app.css */
@import '../../vendor/larajax/ui/resources/assets/ui.css';
```

Vite bundles Amber into your application build. Larajax must still load before your bundle runs, and
Bootstrap's stylesheet before `ui.css`.

## Prepare your controller

Amber widgets are [Larajax view components](https://larajax.org/guide/defining-components.html), so their
AJAX handlers (uploads, validation, partial reloads) are wired up automatically when the controller
extends the Larajax controller. Extend `Larajax\LarajaxController` - directly, or through a base class of
your own - and include Amber's `ViewMaker` trait, which lets widgets resolve partials through the
controller (toolbar buttons, hint fields, partial columns):

```php
namespace App\Controllers;

use Larajax\LarajaxController;

abstract class ControllerBase extends LarajaxController
{
    use \Larajax\Ui\Traits\ViewMaker;
}
```

## Render your first form

Describe the form in a YAML file. Paths beginning with `~/` resolve from the application root:

```yaml
# resources/amber/user/fields.yaml
fields:
    name:
        label: Name
        type: text
    email:
        label: Email Address
        type: email
```

Build the widget inline in a controller action with `Form::make(...)`, bind it to a model, and pass it to
the view:

```php
namespace App\Controllers;

use App\Models\User;
use Larajax\Ui\Widgets\Form;

class UserController extends ControllerBase
{
    public function edit($id)
    {
        $widget = Form::make(
            alias: 'form',
            model: User::findOrFail($id),
            fields: '~/resources/amber/user/fields.yaml',
        );

        return view('users.edit', ['widget' => $widget]);
    }
}
```

Render it in the view. Wrap it in a `<form>` element and add your own submit control:

```blade
<form>
    {{ $widget }}

    <button type="submit">Save</button>
</form>
```

That is a complete, working Amber form: the fields render from YAML, bind to the model's attributes, and
post back through Larajax.

## Timezones

Dates are stored in the application timezone (`app.timezone`). To show them in each person's own
timezone, tell Amber how to find it, usually in a service provider's `boot` method:

```php
use Larajax\Ui\Facades\Ui;

Ui::resolveTimezoneUsing(fn () => auth()->user()?->timezone);
```

Datetime list columns, the datepicker form widget and the date filter then convert between the two:
values are shown in the user's timezone and saved back in the application timezone. A resolver that
returns `null` or an unknown timezone keeps the application timezone. `Ui::timezone()` returns the
resolved timezone for your own views.

Set `useTimezone: false` on a field, column or scope to show the stored value as it is. Date-only and
time-only pickers are never converted, and `date` and `time` list columns only convert when they set
`useTimezone: true`.

## Next steps

- [Forms](forms.md) - every field, UI element and widget type available in a form
- [Lists](lists.md) - render sortable, paginated record lists
- [Filters](filters.md) - add scope-based filtering to a list
- [Using Eloquent models](eloquent.md) - drive Amber from plain `Illuminate` models
