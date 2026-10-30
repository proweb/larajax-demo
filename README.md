# Amber

A sample Laravel application demonstrating [`larajax/ui`](https://github.com/larajax/ui),
the Form, List and UI widget engine (the same one behind the October CMS admin panel),
packaged for any Laravel app. No CMS dependency, no JavaScript framework, no build step.

The app is a small user directory: it manages **Users** and **User Groups** using stock
Illuminate Eloquent models, driving every screen from YAML configuration.

## What it demonstrates

| Screen | URL | Widgets |
| --- | --- | --- |
| Users list | `/users` | Toolbar (declarative buttons + search), Filter (checkbox, dropdown and popover scopes), List (sortable, paginated) |
| Users reorder | `/users/structure` | ListStructure (drag-to-reorder, persisted via `SortableInterface`) |
| Create / edit user | `/users/create`, `/users/{id}/edit` | Form (tabs, contexts, relation fields, repeater, validation, AJAX save/delete) |
| Groups list | `/user-groups` | Toolbar, List |
| Create / edit group | `/user-groups/create`, `/user-groups/{id}/edit` | Form |

The widget definitions live in [`resources/ui`](resources/ui) and the controllers that
build them in [`app/Controllers`](app/Controllers).

## Requirements

- PHP 8.3+
- Composer
- SQLite (default), or any database Laravel supports

## Getting started

```bash
git clone https://github.com/larajax/amber.git
cd amber

# Install dependencies, copy .env, generate a key, create the SQLite
# database and run migrations + seeders.
composer setup

# Serve the app. There is no build step; assets load from a CDN and the
# published vendor/larajax files.
composer dev
```

Then open <http://localhost:8000>. The seeder creates five user groups and fifty users
(each wired to a primary and secondary groups) plus a stable `demo@example.com` account.

> The default password for every seeded user is `password`.

### Manual setup

If you prefer to run the steps yourself:

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

## How it fits together

A controller extends `App\Classes\ControllerBase` (itself a `Larajax\LarajaxController`),
builds one or more widgets from YAML, and passes them to a Blade view that echoes them;
widgets render as safe HTML, so `{{ $widget }}` works directly. Because the widgets are
Larajax view components, their AJAX handlers (`onStore`, `onUpdate`, `onDestroy`, sorting,
pagination, filtering) route automatically back to the controller that created them.

```php
// app/Controllers/UsersController.php
$list = Lists::make(
    model: new User,
    columns: '~/resources/ui/user/columns.yaml',
    recordsPerPage: 10,
);

return view('users.index', ['list' => $list]);
```

```blade
{{-- resources/views/users/index.blade.php --}}
{{ $list }}
```

For the full documentation see the [`docs/`](docs) directory (a VitePress site) or the
[`larajax/ui`](https://github.com/larajax/ui) package.

## License

MIT
