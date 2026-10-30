---
subtitle: Render sortable, paginated record lists from YAML.
---
# Lists

The List widget renders a table of model records from a `columns.yaml` definition - with sorting,
searching, pagination and row selection wired up through AJAX automatically. You describe the columns;
Amber builds the query and the markup. A [tree and reorderable variant](#tree-and-reorderable-lists)
is available for hierarchical data.

## Rendering a list

Describe the columns in a YAML file:

```yaml
# resources/amber/user/columns.yaml
columns:
    id:
        label: ID
        sortable: true

    name:
        label: Name
        searchable: true
        sortable: true

    email:
        label: Email Address
        searchable: true

    created_at:
        label: Created
        type: datetime
        sortable: true
```

Build the widget in a controller action with `Lists::make(...)`, bind it to a model, and pass it to the
view. The `columns` value may be a YAML file path or an inline array of definitions:

```php
namespace App\Controllers;

use App\Models\User;
use Larajax\Ui\Widgets\Lists;

class UserController extends ControllerBase
{
    public function index()
    {
        $widget = Lists::make(
            alias: 'list',
            model: new User,
            columns: '~/resources/amber/user/columns.yaml',
            recordsPerPage: 10,
            recordUrl: 'users/edit/:id',
        );

        return view('users.index', ['widget' => $widget]);
    }
}
```

Render it in the view:

```blade
{{ $widget }}
```

Sorting, pagination and search post back through [Larajax](https://larajax.org) AJAX handlers on the
widget (`onSort`, `onPaginate`, `onFilter`), so **the route must also accept POST requests**:

```php
Route::match(['get', 'post'], '/users', [UserController::class, 'index']);
```

## Widget configuration

Option | Description
------------- | -------------
**columns** | column definitions: a YAML file path or an array.
**model** | the model instance the list reads from.
**recordsPerPage** | maximum rows per page. Pagination is enabled automatically when set.
**recordUrl** | link for each row. Model attributes are substituted, e.g. `users/edit/:id`.
**recordOnClick** | JavaScript to run when a row is clicked, with the same `:id` substitution. Use `popup` (or `popup@context`) to open the record in a popup instead, see below.
**noRecordsMessage** | message shown when the list is empty. Default: `No records found.`
**defaultSort** | column to sort by initially: a column name, or `{column: name, direction: desc}`.
**showSorting** | display sorting links on column headers. Default: `true`.
**showCheckboxes** | display a selection checkbox next to each row. Default: `false`.
**showPagination** | display pagination. Default `auto`, enabled when `recordsPerPage` is set.
**showPageNumbers** | render numbered pagination; disable for better performance on huge tables. Default: `true`.
**showSetup** | enables the list setup popup for column visibility and ordering. Default: `false`.
**perPageOptions** | selectable page sizes in the setup popup. Default: `[20, 40, 80, 100, 120]`.
**expandLastColumn** | squeeze extra room from the last column. Default: `false`.
**customPageName** | the URL parameter used for the page number. Default: `page`.

## Defining columns

Every column supports these properties, where applicable:

Property | Description
------------- | -------------
**label** | a name when displaying the list column to the user.
**type** | defines how this column should be rendered (see [column types](#column-types) below).
**default** | specifies the default value for the column if value is empty.
**searchable** | include this column in the list search results. Default: `false`.
**invisible** | specifies if this column is hidden by default. Default: `false`.
**sortable** | specifies if this column can be sorted. Default: `true`.
**sortableDefault** | specifies if this column is sorted by default. This should only be used on a single sortable column. Supported values: `asc`, `desc`.
**clickable** | if set to false, disables the default click behavior when the column is clicked. Default: `true`.
**select** | defines a custom SQL select statement to use for the value. If a `relation` is specified, this refers to a column on the related database table.
**valueFrom** | defines a model attribute to use for the source value. If a `relation` is specified, this refers to the attribute of the relation and eager loads the relation.
**displayFrom** | defines a model attribute to use for the display value.
**relation** | defines a model relationship name as a source, used with `select` or `valueFrom`.
**relationCount** | display the number of related records as the column value. Must be used with the `relation` option. Default: `false`.
**relationWith** | eager load the specified relation definition with the list query.
**cssClass** | assigns a CSS class to the column container.
**headCssClass** | assigns a CSS class to the column header container.
**width** | sets the column width, in percents (10%) or pixels (50px). A single column without a width will stretch to take the available space.
**align** | specifies the column alignment. Possible values are `left`, `right` and `center`.
**permissions** | gate abilities the current user must pass for the column to be shown. A string for a single ability, or an array where any one grants access. Checked with Laravel's `Gate`.
**order** | a numerical weight when determining the display order, default value increments at 100 points per column.
**after** | place this column after another existing column name using the display order (+1).
**before** | place this column before another existing column name using the display order (-1).
**tooltip** | adds an icon with a tooltip after the column label.

### Value selection

Source the column value from another attribute with `valueFrom`, or keep the source value (for sorting
and searching) while displaying something else with `displayFrom` - useful with a model accessor:

```yaml
status_code:
    label: Status
    displayFrom: status_label
```

Use a custom SQL select statement with `select`:

```yaml
full_name:
    label: Full Name
    select: concat(first_name, ' ', last_name)
```

Display related data as part of the database query - so it stays searchable and sortable - by naming
the relationship in `relation`:

```yaml
group_name:
    label: Group
    relation: groups
    select: name
```

Count related records with `relationCount`:

```yaml
users_count:
    label: Users
    type: number
    relation: users
    relationCount: true
```

Retrieve a value from nested data (a loaded relation or a jsonable array) with bracket syntax - the
PHP equivalent of `$record->content->title`. Nested columns cannot be searched or sorted:

```yaml
content[title]:
    label: Title
```

### Tooltips

Add an informational icon after the column header with `tooltip`. Pass a string, or an array with
`title`, `icon`, `placement` (`top`, `right`, `bottom`, `left`) and `isHtml`:

```yaml
count:
    label: Count
    type: number
    tooltip: Number of users in the group
```

## Column types

All columns are identified by their **type** property; `text` is the default.

**text** - displays the value as escaped text. An optional `format` is applied with `sprintf`.

**number** - same as text with right-aligned styling.

```yaml
age:
    label: Age
    type: number
```

**datetime / date / time** - formats a date value. Without a `format`, sensible long-form defaults are
used (e.g. *Sat, Aug 8, 2026 2:00 PM*). Datetime columns show the value in the user's timezone once a
[timezone resolver](installation.md#timezones) is set; `date` and `time` columns only do so with
`useTimezone: true`, and `useTimezone: false` keeps a datetime column as stored.

```yaml
created_at:
    label: Created
    type: datetime
    format: d/m/Y
```

**timesince** - human readable difference: *10 minutes ago*.

**timetense** - day with grammatical tense: *Today at 12:49*.

**switch** - renders a boolean as a yes/no indicator. Override the labels with `options`:

```yaml
is_enabled:
    label: Enabled
    type: switch
    options: [Inactive, Active]
```

**summary** - strips HTML and truncates the value. Configure with `limitChars` (default 40),
`limitWords` and `endChars` (default `...`).

**image** - displays image thumbnails from a file attachment, path or URL. Configure `width`, `height`
and `limit` (maximum images shown, default 3).

```yaml
avatar:
    label: Avatar
    type: image
    width: 48
    height: 48
```

**file** - displays icons for file attachments, with `limit` (default 3).

**selectable** - looks up the display value from a defined options set, the same way a dropdown form
field does. Uses the column's `options` array or an options method on the model.

```yaml
status:
    label: Status
    type: selectable
    options:
        pending: Pending
        active: Active
```

**linkage** - renders the value as a hyperlink. Use `linkUrl` (with `:attribute` substitution from the
record) and `linkText`, or supply an array value of `[$url, $text]`.

```yaml
website:
    label: Website
    type: linkage
```

**partial** - renders custom markup from a view. `path` accepts a namespaced Laravel view
(`myviews::users.actions`) or a path symbol resolved against the controller (`~/resources/views/users/_actions.php`);
the partial receives `$record`, `$column` and `$value`.

```yaml
actions:
    label: Actions
    type: partial
    path: ~/resources/views/users/_actions.php
    clickable: false
```

**colorpicker** - displays the value as a color swatch.

## Row selection

Enable `showCheckboxes` to render a selection checkbox on each row. Checked IDs are submitted with AJAX
requests made inside the list, and survive pagination via a hidden data locker. Server-side, read them
with:

```php
$ids = $widget->getAllCheckedIds();
```

## Editing records in a popup

Set `recordOnClick` to `popup` and a row click opens that record in a popup, loaded from an
`onLoadPopupForm` handler on your controller. The handler receives `form_record_id`, and
`form_context` when the list uses `popup@context`. It returns the popup markup: a header, body and
footer, usually around a form widget:

```php
public function onLoadPopupForm()
{
    $group = UserGroup::findOrFail(input('form_record_id'));

    $this->formWidget = Form::make(model: $group, fields: '~/resources/ui/user_group/fields.yaml');

    return view('user_groups._popup_form', ['group' => $group, 'formWidget' => $this->formWidget])->render();
}
```

Inside the popup, a form posting to its own handler with `data-popup-load-indicator` closes the
popup once the request succeeds; return the list's `onRefresh()` from that handler to update the
rows. The shorthand calls the global `jax` object, which the browser bundle provides; Vite
applications that import `jax` should also assign it to `window.jax`.

## List setup and user preferences

Enable `showSetup` to let users choose visible columns, column order and page size through a setup
popup. Choices persist per list.

Preferences are kept in the **session** by default. To store them durably (e.g. per user in your database), subclass
the widget and override `getPreferenceStorage()` to return your own store with `get`/`set`/`reset`
methods.

## Extending the list

Subclass the widget and override its extension points, declared in `HasListEvents`:

```php
use Larajax\Ui\Widgets\Lists;

class UserList extends Lists
{
    protected function eventExtendQuery($query)
    {
        $query->whereNull('deleted_at');
    }

    protected function eventExtendColumns(): void
    {
        $this->addColumns([
            'birthday' => ['label' => 'Birthday'],
        ]);

        $this->removeColumn('surname');
    }
}
```

Available overrides: `eventExtendColumns`, `eventExtendQueryBefore`, `eventExtendSearchQuery`,
`eventExtendSortColumn`, `eventExtendQuery`, `eventExtendRecords`, `eventRefresh`,
`eventOverrideRecordAction`, `eventOverrideHeaderValue`, `eventOverrideColumnValueRaw`,
`eventOverrideColumnValue`, `eventInjectRowClass`.

You can also constrain the query without subclassing:

```php
$widget->addFilter(function ($query) {
    $query->where('is_active', true);
});
```

## Tree and reorderable lists

The **ListStructure** widget is a drop-in variant of the List widget that displays parent/child
relationships as an expandable tree and lets users reorder records. Build it the same way - every list
option above applies - plus the structure options:

```php
use Larajax\Ui\Widgets\ListStructure;

$widget = ListStructure::make(
    alias: 'list',
    model: new Category,
    columns: '~/resources/amber/category/columns.yaml',
);
```

Option | Description
------------- | -------------
**showTree** | displays the expandable tree hierarchy. Default: `true`.
**treeExpanded** | expands tree nodes by default; each node's state persists in the session. Default: `true`.
**showReorder** | displays a reorder handle on each row. Default: `true`.
**maxDepth** | the maximum nesting depth allowed when reordering.
**dragRow** | allows dragging the entire row, not just the handle. Default: `true`.
**includeSortOrders** | include the `sort_orders` values in the reorder postback.
**sortOrderColumn** | the column used to apply the default structure ordering when no explicit sort is active. Default: `sort_order`.
**permissions** | gate abilities required to modify the structure; reordering hides without them.

### Model requirements

- **Tree display** (`showTree`) requires a model implementing October's `TreeInterface` - an October
  Rain model using the nested-tree or simple-tree traits. For flat reorderable lists on any model, set
  `showTree: false`.
- **Reorder persistence** uses whichever the model supports: nested-set moves, a `parent` relation, or
  a sortable implementation. Models supporting none of these render the reorder UI but ignore reorder
  requests. On plain Eloquent, implement `October\Contracts\Database\SortableInterface` with a
  `sort_order` column:

```php
class User extends Authenticatable implements \October\Contracts\Database\SortableInterface
{
    public function setSortableOrder($itemIds, $itemOrders = null)
    {
        foreach (array_values((array) $itemIds) as $index => $id) {
            static::query()->whereKey($id)->update(['sort_order' => $index + 1]);
        }
    }
}
```

### Structure and sorting

Structure display and column sorting are mutually exclusive. Clicking a sortable column header hides
the tree and sorts flat; clicking the same column a third time restores the structure view. Entering a
search term also switches to flat results until cleared.

## Checked-state buttons

Buttons linked to a list - inside a container carrying `data-list-linkage="<list id>"`, such as a
[Toolbar](toolbar.md) - can react to row selection:

- `data-list-checked-trigger` - the button stays disabled until rows are checked. A nested element
  with `data-list-checked-counter` displays the selected count.
- `data-list-checked-request` - AJAX requests from the button include the checked row ids as
  `checked`, readable server-side with `$widget->getAllCheckedIds()`.

Checkboxes also support **shift-click** to select a range of rows.

## Not yet supported

- Custom column types (`registerListColumnTypes`), including the `currency` column.
- Drag-scrollable headers - wide tables scroll natively.
- Tree structures on plain Eloquent models (the tree contracts come from October Rain).

## Next steps

- [Filters](filters.md) - add scope-based filtering to a list
- [Forms](forms.md) - build the edit form the list links to
